<?php
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['nombre']) || empty(trim($data['nombre']))) {
    echo json_encode(["exito" => false, "mensaje" => "El nombre es obligatorio."]);
    exit;
}

$nombre    = trim($data['nombre']);
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
// Catálogo destino: venta (POS), compra (Egreso mayorista) o ambos.
$modulo    = in_array($data['modulo_destino'] ?? '', ['venta', 'compra', 'ambos'])
                ? $data['modulo_destino'] : 'ambos';

if ($precio !== null && $precio < 0) {
    echo json_encode(["exito" => false, "mensaje" => "El precio no puede ser negativo."]);
    exit;
}

try {
    // Verificar duplicados para no romper el índice UNIQUE del SQL
    $stmtCheck = $pdo->prepare("SELECT id_concepto FROM conceptos WHERE nombre = ?");
    $stmtCheck->execute([$nombre]);
    if ($stmtCheck->fetch()) {
        echo json_encode(["exito" => false, "mensaje" => "Este concepto ya está registrado."]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO conceptos (nombre, categoria, grupo, precio_unitario, stock, factor_mayor, modulo_destino) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nombre, $categoria, $grupo, $precio, $stock, $factor, $modulo]);

    echo json_encode(["exito" => true, "id_concepto" => $pdo->lastInsertId()]);
} catch (Exception $e) {
    echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
}
?>
