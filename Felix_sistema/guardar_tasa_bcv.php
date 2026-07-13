<?php
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
require_once 'includes/tasa_bcv.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$tasa = isset($data['tasa']) ? floatval($data['tasa']) : 0;

if ($tasa <= 0) {
    echo json_encode(["exito" => false, "mensaje" => "La tasa debe ser mayor a 0."]);
    exit;
}

try {
    guardarTasaManual($pdo, $tasa);
    echo json_encode(["exito" => true, "tasa" => $tasa, "fecha" => date('Y-m-d'), "origen" => "manual"]);
} catch (Exception $e) {
    echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
}
?>
