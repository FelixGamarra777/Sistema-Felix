<?php
require_once 'includes/auth.php';
$paginaActiva = 'productos';
$tituloPagina = 'Productos y Servicios';
require_once 'includes/header.php';
?>

<section class="card-panel">
    <h2>Catálogo de Productos y Servicios</h2>
    <div id="alert-message" class="alert"></div>

    <div class="filter-section" style="justify-content: space-between; margin-bottom: 1.5rem;">
        <div class="form-group" style="flex-direction:row; align-items:center; gap:0.5rem; flex: 1; max-width: 400px;">
            <input type="text" id="buscar-producto" placeholder="🔎 Buscar por nombre..." style="width:100%;">
        </div>
        <div style="display:flex; gap:0.5rem;">
            <select id="filtro-categoria" style="width:auto;">
                <option value="">Todos</option>
                <option value="producto">Solo Productos</option>
                <option value="servicio">Solo Servicios</option>
            </select>
            <button id="btn-nuevo-producto" class="submit-btn btn-success" style="width:auto; padding: 0.6rem 1.5rem;">+ Nuevo</button>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Categoría POS</th>
                    <th>Precio (USD)</th>
                    <th>Precio (Bs.)</th>
                    <th>Stock</th>
                    <th>Factor mayor</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla">
                <tr><td colspan="8" style="text-align:center;">Cargando catálogo...</td></tr>
            </tbody>
        </table>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<div id="modal-producto" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="close-modal-producto">&times;</span>
        <h2 id="modal-producto-titulo" style="font-size: 1.2rem; margin-bottom: 1rem;">Nuevo Producto / Servicio</h2>
        <input type="hidden" id="prod-id">
        <div class="form-group">
            <label for="prod-nombre">Nombre</label>
            <input type="text" id="prod-nombre" placeholder="Ej: Resma de papel carta" style="margin-bottom: 1rem;">
        </div>
        <div class="form-group">
            <label for="prod-categoria">Tipo</label>
            <select id="prod-categoria" style="margin-bottom: 1rem;">
                <option value="producto">Producto (con inventario)</option>
                <option value="servicio">Servicio (sin inventario)</option>
            </select>
        </div>
        <div class="form-group">
            <label for="prod-grupo">Categoría del panel POS (ej: Cuadernos, Fotocopias)</label>
            <input type="text" id="prod-grupo" list="lista-grupos" placeholder="Opcional — agrupa el botón en Facturación" style="margin-bottom: 1rem;">
            <datalist id="lista-grupos"></datalist>
        </div>
        <div class="form-group">
            <label for="prod-precio">Precio de venta (USD)</label>
            <input type="number" id="prod-precio" step="0.01" min="0" placeholder="0.00" style="margin-bottom: 1rem;">
        </div>
        <div class="form-group" id="grupo-stock">
            <label for="prod-stock">Stock inicial (unidades al detal)</label>
            <input type="number" id="prod-stock" step="1" placeholder="0" style="margin-bottom: 1rem;">
        </div>
        <div class="form-group" id="grupo-factor">
            <label for="prod-factor">Unidades por presentación mayor (factor de compra)</label>
            <input type="number" id="prod-factor" step="1" min="1" value="1" placeholder="1" style="margin-bottom: 0.3rem;">
            <small style="color:#6c757d;">Cuántas unidades al detal repone <strong>una</strong> presentación al comprar en el Egreso. Ej: 1 Resma = 500 hojas &rarr; 500. Si se compra y vende igual, deje 1.</small>
        </div>
        <button id="btn-guardar-producto" class="submit-btn">Guardar</button>
    </div>
</div>

<script src="assets/js/productos.js"></script>
<script>
initProductos();
</script>
</body>
</html>
