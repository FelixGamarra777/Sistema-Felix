<?php
require_once 'includes/auth.php';
$paginaActiva = 'listar_egresos';
$tituloPagina = 'Listado de Egresos';
require_once 'includes/header.php';
?>

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
    <h2>Todos los Egresos</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Fecha y Hora</th>
                    <th>Concepto</th>
                    <th>Cant.</th>
                    <th>Precio Unit.</th>
                    <th>Monto Total</th>
                    <th>Fuente</th>
                    <th>Forma Pago</th>
                    <th>Proveedor</th>
                    <th>N&deg; Factura</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla">
                <tr><td colspan="10" style="text-align:center;">Cargando egresos...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
<script src="assets/js/listar-movimientos.js"></script>
<script>
initListarMovimientos({
    tipo: 'egreso',
    mostrarTipo: false,
    etiquetaVacio: 'No hay egresos registrados para este periodo.'
});
</script>
</body>
</html>
