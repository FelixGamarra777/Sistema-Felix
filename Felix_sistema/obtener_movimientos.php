<?php
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
header('Content-Type: application/json');

$desde = isset($_GET['desde']) ? $_GET['desde'] : '';
$hasta = isset($_GET['hasta']) ? $_GET['hasta'] : '';
$tipo  = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$q     = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    $sql = "SELECT 
                m.id_movimiento AS id, 
                m.fecha_movimiento AS fecha, 
                c.nombre AS concepto, 
                m.tipo, 
                m.cantidad, 
                m.precio_unitario AS precio, 
                m.monto_total AS monto, 
                m.fuente,
                m.forma_pago,
                b.nombre_banco AS banco,
                cl.nombre_empresa AS cliente,
                p.nombre_empresa AS proveedor,
                m.numero_factura,
                m.fuente_referencia,
                m.tasa_bcv,
                m.monto_bs
            FROM movimientos m
            LEFT JOIN conceptos c   ON m.id_concepto  = c.id_concepto
            LEFT JOIN bancos b      ON m.id_banco      = b.id_banco
            LEFT JOIN clientes cl   ON m.id_cliente    = cl.id_cliente
            LEFT JOIN proveedores p ON m.id_proveedor  = p.id_proveedor";

    $conditions = [];
    $params = [];

    if (!empty($desde) && !empty($hasta)) {
        $conditions[] = "DATE(m.fecha_movimiento) BETWEEN ? AND ?";
        $params[] = $desde;
        $params[] = $hasta;
    }

    if (!empty($tipo) && in_array($tipo, ['ingreso', 'egreso'])) {
        $conditions[] = "m.tipo = ?";
        $params[] = $tipo;
    }

    if ($q !== '') {
        $conditions[] = "(c.nombre LIKE ? OR m.fuente LIKE ? OR cl.nombre_empresa LIKE ?
                          OR p.nombre_empresa LIKE ? OR m.numero_factura LIKE ? OR m.fuente_referencia LIKE ?)";
        $like = "%$q%";
        array_push($params, $like, $like, $like, $like, $like, $like);
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY m.fecha_movimiento DESC";

    if (!empty($params)) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->query($sql);
    }

    $movimientos = $stmt->fetchAll();
    echo json_encode(["exito" => true, "datos" => $movimientos]);
} catch (Exception $e) {
    echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
}
?>
