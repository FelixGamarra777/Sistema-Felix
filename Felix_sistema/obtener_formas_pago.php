<?php
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM movimientos LIKE 'forma_pago'");
    $columna = $stmt->fetch();

    $valores = [];
    if ($columna && preg_match("/^enum\((.*)\)$/i", $columna['Type'], $coincidencias)) {
        $valores = str_getcsv($coincidencias[1], ',', "'");
    }

    echo json_encode(["exito" => true, "datos" => $valores]);
} catch (Exception $e) {
    echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
}
?>
