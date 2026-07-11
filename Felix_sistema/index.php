<?php
require_once 'includes/auth.php';
$paginaActiva = 'resumen';
$tituloPagina = 'Resumen';
require_once 'includes/header.php';
?>

<div class="summary-cards">
    <div class="summary-card ingresos">
        <h3>Total Ingresos (Filtrado)</h3>
        <span class="value" id="total-ingresos">$0.00</span>
    </div>
    <div class="summary-card egresos">
        <h3>Total Egresos (Filtrado)</h3>
        <span class="value" id="total-egresos">$0.00</span>
    </div>
    <div class="summary-card balance">
        <h3>Balance Neto</h3>
        <span class="value" id="balance-neto">$0.00</span>
    </div>
</div>

<section class="card-panel filter-section">
    <div class="form-group" style="flex-direction:row; align-items:center; gap:0.5rem;">
        <label for="mes-desde">Mes desde:</label>
        <input type="month" id="mes-desde">
    </div>
    <div class="form-group" style="flex-direction:row; align-items:center; gap:0.5rem;">
        <label for="mes-hasta">Mes hasta:</label>
        <input type="month" id="mes-hasta">
    </div>
    <button id="btn-filtrar" class="submit-btn btn-success" style="width: auto; padding: 0.6rem 1.5rem;">Filtrar</button>
    <button id="btn-mes-actual" class="submit-btn btn-info" style="width: auto; padding: 0.6rem 1.5rem;">Mes Actual</button>
    <button id="btn-limpiar" class="submit-btn btn-secondary" style="width: auto; padding: 0.6rem 1.5rem;">Ver Todo</button>
</section>

<section class="card-panel">
    <h2>Historial de Movimientos</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Fecha y Hora</th>
                    <th>Concepto</th>
                    <th>Tipo</th>
                    <th>Cant.</th>
                    <th>Precio Unit.</th>
                    <th>Monto Total</th>
                    <th>Fuente</th>
                    <th>Forma Pago</th>
                    <th>Cliente/Proveedor</th>
                    <th>N&deg; Factura</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla">
                <tr><td colspan="11" style="text-align:center;">Cargando movimientos...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
<script src="assets/js/resumen.js"></script>
<script>
initResumen();
</script>
</body>
</html>
