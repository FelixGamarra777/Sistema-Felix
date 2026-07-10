<?php
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['nombre_banco']) || empty(trim($data['nombre_banco']))) {
    echo json_encode(["exito" => false, "mensaje" => "El nombre del banco es obligatorio."]);
    exit;
}

$nombre_banco = trim($data['nombre_banco']);

try {
    $stmt = $pdo->prepare("INSERT INTO bancos (nombre_banco) VALUES (?)");
    $stmt->execute([$nombre_banco]);
    $id = $pdo->lastInsertId();

    echo json_encode(["exito" => true, "id_banco" => $id]);
} catch (Exception $e) {
    echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
}
?>
