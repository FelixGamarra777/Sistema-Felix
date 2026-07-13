<?php
require_once 'conexion.php';
require_once 'includes/verificar_sesion_api.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(["exito" => false, "mensaje" => "ID de factura inválido."]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT f.*, c.nombre_empresa AS cliente, c.cedula_rif
        FROM facturas f
        LEFT JOIN clientes c ON f.id_cliente = c.id_cliente
        WHERE f.id_factura = ?
    ");
    $stmt->execute([$id]);
    $factura = $stmt->fetch();

    if (!$factura) {
        echo json_encode(["exito" => false, "mensaje" => "Factura no encontrada."]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT descripcion, cantidad, precio_unitario, monto_total FROM factura_items WHERE id_factura = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT p.forma_pago, p.moneda, p.monto, p.monto_usd, p.referencia, b.nombre_banco
        FROM factura_pagos p
        LEFT JOIN bancos b ON p.id_banco = b.id_banco
        WHERE p.id_factura = ?
    ");
    $stmt->execute([$id]);
    $pagos = $stmt->fetchAll();

    echo json_encode(["exito" => true, "factura" => $factura, "items" => $items, "pagos" => $pagos]);
} catch (Exception $e) {
    echo json_encode(["exito" => false, "mensaje" => $e->getMessage()]);
}
?>
