<?php
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
$id_cliente     = !empty($data['id_cliente']) ? intval($data['id_cliente']) : null;
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

    // --- Total de la factura ---
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
            "mensaje" => "Los pagos (" . number_format($pagado_usd, 2) . " USD) no cuadran con el total de la factura (" . number_format($total_usd, 2) . " USD)."
        ]);
        exit;
    }

    $pdo->beginTransaction();

    // --- Correlativo: automático si no se indicó uno manual ---
    if ($numero_factura === '') {
        $stmt = $pdo->query("
            SELECT COALESCE(MAX(CAST(numero_factura AS UNSIGNED)), 0) + 1
            FROM facturas
            WHERE numero_factura REGEXP '^[0-9]+$'
            FOR UPDATE
        ");
        $numero_factura = str_pad((int)$stmt->fetchColumn(), 5, '0', STR_PAD_LEFT);
    } else {
        $stmt = $pdo->prepare("SELECT id_factura FROM facturas WHERE numero_factura = ?");
        $stmt->execute([$numero_factura]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            echo json_encode(["exito" => false, "mensaje" => "Ya existe una factura con el número $numero_factura."]);
            exit;
        }
    }

    // --- Cabecera ---
    $stmt = $pdo->prepare("
        INSERT INTO facturas (numero_factura, tipo, id_cliente, referencia, total_usd, total_bs, tasa_bcv, usuario)
        VALUES (?, 'ingreso', ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$numero_factura, $id_cliente, $referencia, $total_usd, $total_bs, $tasa, $_SESSION['usuario'] ?? null]);
    $id_factura = $pdo->lastInsertId();

    // --- Forma de pago "principal" para los movimientos (la de mayor monto) ---
    $pagoPrincipal = $pagos[0]['forma_pago'];
    $mayor = -1;
    foreach ($pagos as $pago) {
        $usd = $pago['moneda'] === 'USD' ? floatval($pago['monto']) : floatval($pago['monto']) / $tasa;
        if ($usd > $mayor) { $mayor = $usd; $pagoPrincipal = $pago['forma_pago']; }
    }

    // --- Ítems + inventario + movimientos ---
    $advertencias = [];
    $stmtConcepto = $pdo->prepare("SELECT nombre, categoria, stock, modulo_destino FROM conceptos WHERE id_concepto = ? FOR UPDATE");
    $stmtItem = $pdo->prepare("
        INSERT INTO factura_items (id_factura, id_concepto, descripcion, cantidad, precio_unitario, monto_total)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmtStock = $pdo->prepare("UPDATE conceptos SET stock = stock - ? WHERE id_concepto = ?");
    $stmtMov = $pdo->prepare("
        INSERT INTO movimientos
            (id_concepto, tipo, cantidad, precio_unitario, monto_total, fuente,
             forma_pago, id_cliente, numero_factura, fuente_referencia, tasa_bcv, monto_bs, id_factura)
        VALUES (?, 'ingreso', ?, ?, ?, 'Factura POS', ?, ?, ?, ?, ?, ?, ?)
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

        // Catálogos independientes: una factura de venta NO puede incluir un
        // insumo mayorista de compra (evita mezclar dominios).
        if (!in_array($concepto['modulo_destino'], ['venta', 'ambos'])) {
            $pdo->rollBack();
            echo json_encode(["exito" => false, "mensaje" => "\"{$concepto['nombre']}\" es un insumo de compra mayorista y no puede venderse en el POS."]);
            exit;
        }

        $stmtItem->execute([$id_factura, $id_concepto, $concepto['nombre'], $cantidad, $precio, $monto]);

        // Inventario: solo productos con stock gestionado. Se permite quedar
        // en negativo, pero se devuelve una advertencia (decisión de negocio).
        if ($concepto['categoria'] === 'producto' && $concepto['stock'] !== null) {
            $stmtStock->execute([$cantidad, $id_concepto]);
            $stockRestante = intval($concepto['stock']) - $cantidad;
            if ($stockRestante < 0) {
                $advertencias[] = "⚠️ \"{$concepto['nombre']}\" quedó con stock negativo ($stockRestante).";
            } elseif ($stockRestante === 0) {
                $advertencias[] = "\"{$concepto['nombre']}\" quedó sin stock (0).";
            }
        }

        $monto_bs = $tasa > 0 ? round($monto * $tasa, 2) : null;
        $stmtMov->execute([
            $id_concepto, $cantidad, $precio, $monto,
            $pagoPrincipal, $id_cliente, $numero_factura, $referencia,
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
        "advertencias" => $advertencias
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["exito" => false, "mensaje" => "Error en el servidor: " . $e->getMessage()]);
}
?>
