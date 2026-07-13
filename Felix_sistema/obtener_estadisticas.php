<?php
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
header('Content-Type: application/json');

$desde = isset($_GET['desde']) ? $_GET['desde'] : '';
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : '';

try {
    $whereClause = "";
    $params = [];
    
    if (!empty($desde) && !empty($hasta)) {
        $whereClause = " WHERE DATE(fecha_movimiento) BETWEEN ? AND ? ";
        $params = [$desde, $hasta];
    }

    // 1. Totales agrupados por TIPO (Ingreso / Egreso) para el Gráfico de Torta
    $sqlTipo = "SELECT tipo, SUM(monto_total) AS total FROM movimientos" . $whereClause . " GROUP BY tipo";
    $stmt1 = $pdo->prepare($sqlTipo);
    $stmt1->execute($params);
    $totalesTipo = $stmt1->fetchAll();

    // 2. Totales agrupados por CONCEPTO para el Gráfico de Barras
    //    (antes faltaba un espacio antes de "GROUP BY", lo que rompía la
    //     consulta cuando no había filtro de fechas)
    $sqlConcepto = "SELECT c.nombre AS concepto, m.tipo, SUM(m.monto_total) AS total 
                    FROM movimientos m
                    LEFT JOIN conceptos c ON m.id_concepto = c.id_concepto"
                    . $whereClause .
                    " GROUP BY c.nombre, m.tipo";
    $stmt2 = $pdo->prepare($sqlConcepto);
    $stmt2->execute($params);
    $totalesConcepto = $stmt2->fetchAll();

    // 3. Evolución mensual (últimos 12 meses, independiente del filtro)
    $sqlMes = "SELECT DATE_FORMAT(fecha_movimiento, '%Y-%m') AS mes, tipo, SUM(monto_total) AS total
               FROM movimientos
               WHERE fecha_movimiento >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
               GROUP BY mes, tipo
               ORDER BY mes ASC";
    $totalesMes = $pdo->query($sqlMes)->fetchAll();

    echo json_encode([
        "exito" => true,
        "totales_tipo" => $totalesTipo,
        "totales_concepto" => $totalesConcepto,
        "totales_mes" => $totalesMes
    ]);
} catch (Exception $e) {
    echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
}
?>
