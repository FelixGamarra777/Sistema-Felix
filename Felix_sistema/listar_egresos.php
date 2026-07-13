<?php
require_once 'includes/auth.php';
$paginaActiva = 'listar_egresos';
$tituloPagina = 'Listado de Egresos';
require_once 'includes/header.php';
?>

<section class="card-panel">
    <div class="filtros-rapidos" style="margin-bottom: 1rem;">
        <button class="chip-filtro" data-rango="dia">Hoy</button>
        <button class="chip-filtro" data-rango="semana">Esta Semana</button>
        <button class="chip-filtro" data-rango="mes">Este Mes</button>
        <button class="chip-filtro" data-rango="anio">Este Año</button>
        <button class="chip-filtro" data-rango="todo">Ver Todo</button>
    </div>
    <div class="filter-section" style="justify-content: flex-start;">
        <div class="form-group" style="flex-direction:row; align-items:center; gap:0.5rem;">
            <label for="fecha-desde">Desde:</label>
            <input type="date" id="fecha-desde">
        </div>
        <div class="form-group" style="flex-direction:row; align-items:center; gap:0.5rem;">
            <label for="fecha-hasta">Hasta:</label>
            <input type="date" id="fecha-hasta">
        </div>
        <button id="btn-filtrar" class="submit-btn btn-success" style="width: auto; padding: 0.6rem 1.5rem;">Filtrar</button>
        <div class="form-group" style="flex-direction:row; align-items:center; gap:0.5rem; flex:1; min-width: 220px;">
            <input type="text" id="buscar-texto" placeholder="🔎 Buscar por concepto, proveedor, factura..." style="width:100%;">
        </div>
    </div>
</section>

<section class="card-panel">
    <h2>Todos los Egresos &nbsp;<small style="font-weight:normal; color:#6c757d;">Total del listado: <strong id="total-listado" style="color: var(--danger);">$0.00</strong></small></h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Fecha y Hora</th>
                    <th>Concepto</th>
                    <th>Cant.</th>
                    <th>Precio Unit.</th>
                    <th>Monto Total</th>
                    <th>Monto Bs.</th>
                    <th>Fuente</th>
                    <th>Forma Pago</th>
                    <th>Cliente/Proveedor</th>
                    <th>N&deg; Factura</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla">
                <tr><td colspan="11" style="text-align:center;">Cargando egresos...</td></tr>
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
