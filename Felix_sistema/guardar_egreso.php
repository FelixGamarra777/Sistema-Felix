<?php
// guardar_egreso.php
// -----------------------------------------------------------------------
// Punto ÚNICO de entrada de egresos (compras al mayor) desde el POS de egresos.
// Es el espejo de guardar_factura.php: misma potencia transaccional (ACID con
// PDO: beginTransaction / commit / rollBack) y bloqueos de exclusividad, pero:
//   - marca la factura como tipo='egreso' (comprobante de compra),
//   - la persona es el Proveedor (no el Cliente),
//   - el inventario AUMENTA (reposición) en vez de descontarse.
// Reutiliza las tablas existentes: facturas, factura_items, factura_pagos y
// movimientos (retrocompatible, sin migraciones destructivas).
// -----------------------------------------------------------------------
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
require_once 'includes/tasa_bcv.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(["exito" => false, "mensaje" => "No se recibieron datos."]);
    exit;
}

$numero_factura = isset($data['numero_factura']) ? trim($data['numero_factura']) : '';
$id_proveedor   = !empty($data['id_proveedor']) ? intval($data['id_proveedor']) : null;
$referencia     = isset($data['referencia']) ? trim($data['referencia']) : '';
$referencia     = $referencia === '' ? null : $referencia;
$items          = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
$pagos          = isset($data['pagos']) && is_array($data['pagos']) ? $data['pagos'] : [];

$formasValidas = ['Efectivo', 'Dolares', 'Pago Movil', 'Transferencia'];

// --- Validaciones de estructura ---
if (empty($items)) {
    echo json_encode(["exito" => false, "mensaje" => "El carrito está vacío."]);
    exit;
}
if (empty($pagos)) {
    echo json_encode(["exito" => false, "mensaje" => "Debe registrar al menos una forma de pago."]);
    exit;
}

foreach ($items as $item) {
    if (empty($item['id_concepto']) || intval($item['cantidad']) <= 0 || floatval($item['precio_unitario']) < 0) {
        echo json_encode(["exito" => false, "mensaje" => "Hay ítems inválidos en el carrito (cantidad o precio)."]);
        exit;
    }
}
foreach ($pagos as $pago) {
    if (!in_array($pago['forma_pago'] ?? '', $formasValidas) ||
        !in_array($pago['moneda'] ?? '', ['USD', 'BS']) ||
        floatval($pago['monto']) <= 0) {
        echo json_encode(["exito" => false, "mensaje" => "Hay pagos inválidos (forma, moneda o monto)."]);
        exit;
    }
}

try {
    $infoTasa = obtenerTasaBCV($pdo);
    $tasa = (float)$infoTasa['tasa'];

    // --- Total del egreso ---
    $total_usd = 0;
    foreach ($items as $item) {
        $total_usd += intval($item['cantidad']) * floatval($item['precio_unitario']);
    }
    $total_usd = round($total_usd, 2);
    $total_bs  = $tasa > 0 ? round($total_usd * $tasa, 2) : 0;

    // --- Validar que los pagos cubran el total (con tolerancia por redondeo) ---
    $pagado_usd = 0;
    foreach ($pagos as $pago) {
        $monto = floatval($pago['monto']);
        if ($pago['moneda'] === 'USD') {
            $pagado_usd += $monto;
        } else {
            if ($tasa <= 0) {
                echo json_encode(["exito" => false, "mensaje" => "No hay tasa BCV disponible para aceptar pagos en Bolívares."]);
                exit;
            }
            $pagado_usd += $monto / $tasa;
        }
    }
    if (abs($pagado_usd - $total_usd) > 0.10) {
        echo json_encode([
            "exito" => false,
            "mensaje" => "Los pagos (" . number_format($pagado_usd, 2) . " USD) no cuadran con el total del egreso (" . number_format($total_usd, 2) . " USD)."
        ]);
        exit;
    }

    $pdo->beginTransaction();

    // --- Correlativo de egresos (prefijo EGR-): automático si no se indicó uno ---
    if ($numero_factura === '') {
        $stmt = $pdo->query("
            SELECT COALESCE(MAX(CAST(SUBSTRING(numero_factura, 5) AS UNSIGNED)), 0) + 1
            FROM facturas
            WHERE numero_factura REGEXP '^EGR-[0-9]+$'
            FOR UPDATE
        ");
        $numero_factura = 'EGR-' . str_pad((int)$stmt->fetchColumn(), 5, '0', STR_PAD_LEFT);
    } else {
        $stmt = $pdo->prepare("SELECT id_factura FROM facturas WHERE numero_factura = ?");
        $stmt->execute([$numero_factura]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            echo json_encode(["exito" => false, "mensaje" => "Ya existe un comprobante con el número $numero_factura."]);
            exit;
        }
    }

    // --- Nombre del proveedor (para la columna "Fuente" del histórico) ---
    $fuente = 'Compra al Mayor';
    if ($id_proveedor !== null) {
        $stmtProv = $pdo->prepare("SELECT nombre_empresa FROM proveedores WHERE id_proveedor = ?");
        $stmtProv->execute([$id_proveedor]);
        $nombreProv = $stmtProv->fetchColumn();
        if ($nombreProv) $fuente = $nombreProv;
    }

    // --- Cabecera (comprobante de egreso) ---
    $stmt = $pdo->prepare("
        INSERT INTO facturas (numero_factura, tipo, id_proveedor, referencia, total_usd, total_bs, tasa_bcv, usuario)
        VALUES (?, 'egreso', ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$numero_factura, $id_proveedor, $referencia, $total_usd, $total_bs, $tasa, $_SESSION['usuario'] ?? null]);
    $id_factura = $pdo->lastInsertId();

    // --- Forma de pago "principal" para los movimientos (la de mayor monto) ---
    $pagoPrincipal = $pagos[0]['forma_pago'];
    $mayor = -1;
    foreach ($pagos as $pago) {
        $usd = $pago['moneda'] === 'USD' ? floatval($pago['monto']) : floatval($pago['monto']) / $tasa;
        if ($usd > $mayor) { $mayor = $usd; $pagoPrincipal = $pago['forma_pago']; }
    }

    // --- Ítems + inventario (AUMENTA) + movimientos (tipo egreso) ---
    $stmtConcepto = $pdo->prepare("SELECT nombre, categoria, stock FROM conceptos WHERE id_concepto = ? FOR UPDATE");
    $stmtItem = $pdo->prepare("
        INSERT INTO factura_items (id_factura, id_concepto, descripcion, cantidad, precio_unitario, monto_total)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmtStock = $pdo->prepare("UPDATE conceptos SET stock = stock + ? WHERE id_concepto = ?");
    $stmtMov = $pdo->prepare("
        INSERT INTO movimientos
            (id_concepto, tipo, cantidad, precio_unitario, monto_total, fuente,
             forma_pago, id_proveedor, numero_factura, fuente_referencia, tasa_bcv, monto_bs, id_factura)
        VALUES (?, 'egreso', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($items as $item) {
        $id_concepto = intval($item['id_concepto']);
        $cantidad    = intval($item['cantidad']);
        $precio      = floatval($item['precio_unitario']);
        $monto       = round($cantidad * $precio, 2);

        $stmtConcepto->execute([$id_concepto]);
        $concepto = $stmtConcepto->fetch();
        if (!$concepto) {
            $pdo->rollBack();
            echo json_encode(["exito" => false, "mensaje" => "Un producto del carrito ya no existe en el catálogo."]);
            exit;
        }

        $stmtItem->execute([$id_factura, $id_concepto, $concepto['nombre'], $cantidad, $precio, $monto]);

        // Inventario: una compra al mayor REPONE stock (solo productos gestionados).
        if ($concepto['categoria'] === 'producto' && $concepto['stock'] !== null) {
            $stmtStock->execute([$cantidad, $id_concepto]);
        }

        $monto_bs = $tasa > 0 ? round($monto * $tasa, 2) : null;
        $stmtMov->execute([
            $id_concepto, $cantidad, $precio, $monto, $fuente,
            $pagoPrincipal, $id_proveedor, $numero_factura, $referencia,
            $tasa > 0 ? $tasa : null, $monto_bs, $id_factura
        ]);
    }

    // --- Pagos ---
    $stmtPago = $pdo->prepare("
        INSERT INTO factura_pagos (id_factura, forma_pago, moneda, monto, monto_usd, id_banco, referencia)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($pagos as $pago) {
        $monto = floatval($pago['monto']);
        $monto_usd = $pago['moneda'] === 'USD' ? $monto : round($monto / $tasa, 2);
        $stmtPago->execute([
            $id_factura,
            $pago['forma_pago'],
            $pago['moneda'],
            $monto,
            $monto_usd,
            !empty($pago['id_banco']) ? intval($pago['id_banco']) : null,
            !empty($pago['referencia']) ? trim($pago['referencia']) : null
        ]);
    }

    $pdo->commit();

    echo json_encode([
        "exito" => true,
        "id_factura" => $id_factura,
        "numero_factura" => $numero_factura,
        "total_usd" => $total_usd,
        "total_bs" => $total_bs,
        "advertencias" => []
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["exito" => false, "mensaje" => "Error en el servidor: " . $e->getMessage()]);
}
?>
