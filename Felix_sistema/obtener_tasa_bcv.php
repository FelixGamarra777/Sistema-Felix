<?php
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
require_once 'includes/tasa_bcv.php';
header('Content-Type: application/json');

try {
    $info = obtenerTasaBCV($pdo, isset($_GET['refrescar']));
    echo json_encode(array_merge(["exito" => true], $info));
} catch (Exception $e) {
    echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
}
?>
