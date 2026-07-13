<?php
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id_concepto']) ? intval($data['id_concepto']) : 0;

if ($id <= 0) {
    echo json_encode(["exito" => false, "mensaje" => "ID inválido."]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM conceptos WHERE id_concepto = ?");
    $stmt->execute([$id]);
    echo json_encode(["exito" => true]);
} catch (PDOException $e) {
    // FK RESTRICT: el concepto tiene movimientos o facturas asociadas
    if ($e->getCode() == '23000') {
        echo json_encode(["exito" => false, "mensaje" => "No se puede eliminar: este producto/servicio tiene movimientos o facturas registradas."]);
    } else {
        echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
    }
}
?>
