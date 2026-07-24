<?php
require_once 'includes/auth.php';
require_once 'conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $pdo->prepare("
    SELECT f.*,
           c.nombre_empresa AS cliente,   c.cedula_rif AS cliente_rif,
           pr.nombre_empresa AS proveedor, pr.cedula_rif AS proveedor_rif
    FROM facturas f
    LEFT JOIN clientes c     ON f.id_cliente   = c.id_cliente
    LEFT JOIN proveedores pr ON f.id_proveedor = pr.id_proveedor
    WHERE f.id_factura = ?
");
$stmt->execute([$id]);
$factura = $stmt->fetch();

if (!$factura) {
    http_response_code(404);
    echo "Comprobante no encontrado.";
    exit;
}

// El mismo ticket sirve para ventas (ingreso) y compras al mayor (egreso).
$esEgreso   = (($factura['tipo'] ?? 'ingreso') === 'egreso');
$tituloDoc  = $esEgreso ? 'COMPROBANTE DE EGRESO' : 'FACTURA';
$etiquetaNumero = $esEgreso ? 'COMPROBANTE N&deg;' : 'FACTURA N&deg;';
$etiquetaPersona = $esEgreso ? 'PROVEEDOR' : 'CLIENTE';
$personaNombre = $esEgreso ? ($factura['proveedor'] ?: 'Sin proveedor') : ($factura['cliente'] ?: 'Consumidor final');
$personaRif = $esEgreso ? $factura['proveedor_rif'] : $factura['cliente_rif'];
$piePagina  = $esEgreso ? 'Comprobante de compra al mayor' : '¡Gracias por su compra!';

$stmt = $pdo->prepare("SELECT descripcion, cantidad, precio_unitario, monto_total FROM factura_items WHERE id_factura = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT p.forma_pago, p.moneda, p.monto, b.nombre_banco, p.referencia
    FROM factura_pagos p
    LEFT JOIN bancos b ON p.id_banco = b.id_banco
    WHERE p.id_factura = ?
");
$stmt->execute([$id]);
$pagos = $stmt->fetchAll();

function usd($v) { return '$' . number_format((float)$v, 2, '.', ','); }
function bs($v)  { return 'Bs. ' . number_format((float)$v, 2, ',', '.'); }
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title><?php echo $esEgreso ? 'Comprobante' : 'Factura'; ?> <?php echo htmlspecialchars($factura['numero_factura']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 80mm;
            margin: 0 auto;
            padding: 4mm;
            color: #000;
            background: #fff;
        }
        .centro { text-align: center; }
        .negrita { font-weight: bold; }
        .sep { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 2px 0; font-size: 11px; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        .totales td { font-size: 12px; }
        .gran-total { font-size: 14px; font-weight: bold; }
        .pie { margin-top: 10px; font-size: 10px; }
        .no-print { text-align: center; margin: 12px 0; }
        .no-print button { padding: 8px 16px; font-size: 13px; cursor: pointer; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            @page { size: 80mm auto; margin: 2mm; }
        }
    </style>
</head>
<body>
    <div class="centro negrita">INVERSIONES COMPUNET SEGURA, C.A.</div>
    <div class="centro">Gestión de Papelería</div>
    <div class="centro negrita"><?php echo $tituloDoc; ?></div>
    <div class="sep"></div>

    <div><?php echo $etiquetaNumero; ?>: <span class="negrita"><?php echo htmlspecialchars($factura['numero_factura']); ?></span></div>
    <div>FECHA: <?php echo date('d/m/Y h:i A', strtotime($factura['fecha_factura'])); ?></div>
    <div><?php echo $etiquetaPersona; ?>: <?php echo htmlspecialchars($personaNombre); ?></div>
    <?php if ($personaRif): ?>
    <div>C.I./RIF: <?php echo htmlspecialchars($personaRif); ?></div>
    <?php endif; ?>
    <div>ATENDIDO POR: <?php echo htmlspecialchars($factura['usuario'] ?: '-'); ?></div>
    <div class="sep"></div>

    <table>
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="num">Cant</th>
                <th class="num">P.Unit</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['descripcion']); ?></td>
                <td class="num"><?php echo (int)$item['cantidad']; ?></td>
                <td class="num"><?php echo usd($item['precio_unitario']); ?></td>
                <td class="num"><?php echo usd($item['monto_total']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="sep"></div>
    <table class="totales">
        <tr><td>TASA BCV DEL DÍA:</td><td class="num"><?php echo bs($factura['tasa_bcv']); ?> / $</td></tr>
        <tr class="gran-total"><td>TOTAL USD:</td><td class="num gran-total"><?php echo usd($factura['total_usd']); ?></td></tr>
        <tr class="gran-total"><td>TOTAL Bs.:</td><td class="num gran-total"><?php echo bs($factura['total_bs']); ?></td></tr>
    </table>

    <div class="sep"></div>
    <div class="negrita">FORMA(S) DE PAGO:</div>
    <table>
        <?php foreach ($pagos as $pago): ?>
        <tr>
            <td>
                <?php echo htmlspecialchars($pago['forma_pago']); ?>
                <?php if ($pago['nombre_banco']) echo ' - ' . htmlspecialchars($pago['nombre_banco']); ?>
                <?php if ($pago['referencia']) echo ' (Ref: ' . htmlspecialchars($pago['referencia']) . ')'; ?>
            </td>
            <td class="num"><?php echo $pago['moneda'] === 'USD' ? usd($pago['monto']) : bs($pago['monto']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="sep"></div>
    <div class="centro pie"><?php echo htmlspecialchars($piePagina); ?></div>

    <div class="no-print">
        <button onclick="window.print()">🖨️ Imprimir</button>
        <button onclick="window.close()">Cerrar</button>
    </div>

    <script>
        window.addEventListener('load', () => setTimeout(() => window.print(), 300));
    </script>
</body>
</html>
