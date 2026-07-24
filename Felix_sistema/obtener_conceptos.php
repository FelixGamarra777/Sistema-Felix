<?php
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
header('Content-Type: application/json');

$q         = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

try {
    $sql = "SELECT id_concepto, nombre, categoria, grupo, precio_unitario, stock, factor_mayor FROM conceptos";
    $conditions = [];
    $params = [];

    if ($q !== '') {
        $conditions[] = "nombre LIKE ?";
        $params[] = "%$q%";
    }
    if (in_array($categoria, ['producto', 'servicio'])) {
        $conditions[] = "categoria = ?";
        $params[] = $categoria;
    }
    if ($conditions) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    $sql .= " ORDER BY nombre ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(["exito" => true, "datos" => $stmt->fetchAll()]);
} catch (Exception $e) {
    echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
}
?>
