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

// --- Campos originales ---
$id_concepto     = isset($data['id_concepto']) ? intval($data['id_concepto']) : 0;
$tipo            = isset($data['tipo']) ? trim($data['tipo']) : '';
$cantidad        = isset($data['cantidad']) ? intval($data['cantidad']) : 0;
$precio_unitario = isset($data['precio_unitario']) ? floatval($data['precio_unitario']) : 0;
$fuente          = isset($data['fuente']) ? trim($data['fuente']) : '';

// --- Campos nuevos ---
$forma_pago        = isset($data['forma_pago']) ? trim($data['forma_pago']) : '';
$id_banco          = !empty($data['id_banco'])     ? intval($data['id_banco'])     : null;
$id_cliente        = !empty($data['id_cliente'])   ? intval($data['id_cliente'])   : null;
$id_proveedor      = !empty($data['id_proveedor']) ? intval($data['id_proveedor']) : null;
$numero_factura    = isset($data['numero_factura']) ? trim($data['numero_factura']) : '';
$fuente_referencia = isset($data['fuente_referencia']) ? trim($data['fuente_referencia']) : '';
$numero_factura    = $numero_factura === '' ? null : $numero_factura;
$fuente_referencia = $fuente_referencia === '' ? null : $fuente_referencia;

// --- Validaciones ---
// Los ingresos SOLO entran por Facturación (guardar_factura.php), que es el
// único punto de escritura contable de ventas (transacción ACID completa).
$errores = [];
if ($id_concepto <= 0)                       $errores[] = "Debe seleccionar un concepto válido.";
if ($tipo !== 'egreso')                      $errores[] = "Este endpoint solo registra egresos. Los ingresos se registran desde Facturación (POS).";
if ($cantidad <= 0)                          $errores[] = "La cantidad debe ser mayor a 0.";
if ($precio_unitario <= 0)                   $errores[] = "El precio unitario debe ser mayor a 0.";
if ($fuente === '')                          $errores[] = "La fuente/proveedor es obligatoria.";
if ($forma_pago === '')                      $errores[] = "Debe indicar la forma de pago.";

if (!empty($errores)) {
    echo json_encode(["exito" => false, "mensaje" => implode(" ", $errores)]);
    exit;
}

$monto_total = $cantidad * $precio_unitario;

try {
    // Tasa BCV del día: se calcula en el servidor para que quede registrada
    // con cada transacción (histórico confiable aunque la tasa cambie mañana).
    $infoTasa = obtenerTasaBCV($pdo);
    $tasa_bcv = $infoTasa['tasa'] > 0 ? $infoTasa['tasa'] : null;
    $monto_bs = $tasa_bcv !== null ? round($monto_total * $tasa_bcv, 2) : null;

    // Verificar que el concepto realmente exista (evita datos huérfanos)
    $stmtCheck = $pdo->prepare("SELECT id_concepto FROM conceptos WHERE id_concepto = ?");
    $stmtCheck->execute([$id_concepto]);
    if (!$stmtCheck->fetch()) {
        echo json_encode(["exito" => false, "mensaje" => "El concepto seleccionado no existe."]);
        exit;
    }

    $sql = "INSERT INTO movimientos
                (id_concepto, tipo, cantidad, precio_unitario, monto_total, fuente,
                 forma_pago, id_banco, id_cliente, id_proveedor, numero_factura, fuente_referencia,
                 tasa_bcv, monto_bs)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $id_concepto, $tipo, $cantidad, $precio_unitario, $monto_total, $fuente,
        $forma_pago, $id_banco, $id_cliente, $id_proveedor, $numero_factura, $fuente_referencia,
        $tasa_bcv, $monto_bs
    ]);

    echo json_encode(["exito" => true, "id_movimiento" => $pdo->lastInsertId()]);
} catch (Exception $e) {
    echo json_encode(["exito" => false, "mensaje" => "Error en el servidor: " . $e->getMessage()]);
}
?>
