<?php
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id_concepto, nombre FROM conceptos ORDER BY nombre ASC");
    $conceptos = $stmt->fetchAll();
    echo json_encode(["exito" => true, "datos" => $conceptos]);
} catch (Exception $e) {
    echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
}
?>
