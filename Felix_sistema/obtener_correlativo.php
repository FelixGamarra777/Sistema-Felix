<?php
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT COALESCE(MAX(CAST(numero_factura AS UNSIGNED)), 0) + 1 AS siguiente
        FROM facturas
        WHERE numero_factura REGEXP '^[0-9]+$'
    ");
    $siguiente = (int)$stmt->fetchColumn();
    echo json_encode(["exito" => true, "numero_factura" => str_pad($siguiente, 5, '0', STR_PAD_LEFT)]);
} catch (Exception $e) {
    echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
}
?>
