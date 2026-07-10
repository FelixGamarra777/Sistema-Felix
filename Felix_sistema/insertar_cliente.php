<?php
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$nombre_empresa = isset($data['nombre_empresa']) ? trim($data['nombre_empresa']) : '';
$cedula_rif     = isset($data['cedula_rif']) ? trim($data['cedula_rif']) : '';
$tipo_persona   = isset($data['tipo_persona']) ? trim($data['tipo_persona']) : '';

if ($nombre_empresa === '' || $cedula_rif === '') {
    echo json_encode(["exito" => false, "mensaje" => "Nombre y Cédula/RIF son obligatorios."]);
    exit;
}

if (!in_array($tipo_persona, ['Natural', 'Juridica'])) {
    $tipo_persona = 'Natural';
}

try {
    // Verificar duplicados por RIF (índice único en la tabla)
    $stmtCheck = $pdo->prepare("SELECT id_cliente FROM clientes WHERE cedula_rif = ?");
    $stmtCheck->execute([$cedula_rif]);
    if ($stmtCheck->fetch()) {
        echo json_encode(["exito" => false, "mensaje" => "Ya existe un cliente registrado con esa Cédula/RIF."]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO clientes (nombre_empresa, cedula_rif, tipo_persona) VALUES (?, ?, ?)");
    $stmt->execute([$nombre_empresa, $cedula_rif, $tipo_persona]);
    $id = $pdo->lastInsertId();

    echo json_encode(["exito" => true, "id_cliente" => $id]);
} catch (Exception $e) {
    echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
}
?>
