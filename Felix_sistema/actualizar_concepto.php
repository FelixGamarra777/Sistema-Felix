<?php
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$id        = isset($data['id_concepto']) ? intval($data['id_concepto']) : 0;
$nombre    = isset($data['nombre']) ? trim($data['nombre']) : '';
$categoria = (isset($data['categoria']) && $data['categoria'] === 'producto') ? 'producto' : 'servicio';
$grupo     = (isset($data['grupo']) && trim($data['grupo']) !== '')
                ? mb_substr(trim($data['grupo']), 0, 50) : null;
$precio    = (isset($data['precio_unitario']) && $data['precio_unitario'] !== '' && $data['precio_unitario'] !== null)
                ? floatval($data['precio_unitario']) : null;
$stock     = ($categoria === 'producto')
                ? (isset($data['stock']) && $data['stock'] !== '' ? intval($data['stock']) : 0)
                : null;
// Factor de conversión mayor→detal (solo aplica a productos). Admite decimales
// para empaques fraccionados; si es inválido o <= 0 se usa 1 (compra/venta 1:1).
$factor    = ($categoria === 'producto' && isset($data['factor_mayor']) && floatval($data['factor_mayor']) > 0)
                ? round(floatval($data['factor_mayor']), 2) : 1;

if ($id <= 0 || $nombre === '') {
    echo json_encode(["exito" => false, "mensaje" => "Datos incompletos: id y nombre son obligatorios."]);
    exit;
}
if ($precio !== null && $precio < 0) {
    echo json_encode(["exito" => false, "mensaje" => "El precio no puede ser negativo."]);
    exit;
}

try {
    // Evitar chocar con otro concepto que ya use el mismo nombre
    $stmtCheck = $pdo->prepare("SELECT id_concepto FROM conceptos WHERE nombre = ? AND id_concepto <> ?");
    $stmtCheck->execute([$nombre, $id]);
    if ($stmtCheck->fetch()) {
        echo json_encode(["exito" => false, "mensaje" => "Ya existe otro concepto con ese nombre."]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE conceptos SET nombre = ?, categoria = ?, grupo = ?, precio_unitario = ?, stock = ?, factor_mayor = ? WHERE id_concepto = ?");
    $stmt->execute([$nombre, $categoria, $grupo, $precio, $stock, $factor, $id]);

    echo json_encode(["exito" => true]);
} catch (Exception $e) {
    echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
}
?>
