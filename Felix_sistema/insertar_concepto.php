<?php
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['nombre']) || empty(trim($data['nombre']))) {
    echo json_encode(["exito" => false, "mensaje" => "El nombre es obligatorio."]);
    exit;
}

$nombre = trim($data['nombre']);

try {
    // Verificar duplicados para no romper el índice UNIQUE del SQL
    $stmtCheck = $pdo->prepare("SELECT id_concepto FROM conceptos WHERE nombre = ?");
    $stmtCheck->execute([$nombre]);
    if ($stmtCheck->fetch()) {
        echo json_encode(["exito" => false, "mensaje" => "Este concepto ya está registrado."]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO conceptos (nombre) VALUES (?)");
    $stmt->execute([$nombre]);
    $id = $pdo->lastInsertId();

    echo json_encode(["exito" => true, "id_concepto" => $id]);
} catch (Exception $e) {
    echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
}
?>
