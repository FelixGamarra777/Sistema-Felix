<?php
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id_cliente, nombre_empresa, cedula_rif FROM clientes ORDER BY nombre_empresa ASC");
    echo json_encode(["exito" => true, "datos" => $stmt->fetchAll()]);
} catch (Exception $e) {
    echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
}
?>
