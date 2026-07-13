<?php
require_once 'includes/auth.php';
$paginaActiva = 'facturacion';
$tituloPagina = 'Facturación (POS)';
require_once 'includes/header.php';
?>

<section class="card-panel">
    <h2>Datos de la Factura</h2>
    <div id="alert-message" class="alert"></div>
    <div class="pos-grid">
        <div class="form-group">
            <label for="numero-factura">N&deg; de Factura (correlativo)</label>
            <input type="text" id="numero-factura" placeholder="Cargando..." />
        </div>
        <div class="form-group">
            <label for="cliente">Cliente (opcional)</label>
            <div class="input-group">
                <select id="cliente">
                    <option value="">Consumidor final</option>
                </select>
                <button type="button" id="btn-open-modal-cliente" class="btn-icon" title="Agregar Cliente">+</button>
            </div>
        </div>
    </div>
</section>

<section class="card-panel">
    <h2>Agregar Productos / Servicios</h2>
    <div class="pos-grid">
        <div class="form-group buscador-wrapper" style="grid-column: span 2;">
            <label for="buscador-pos">Buscar en el catálogo</label>
            <input type="text" id="buscador-pos" placeholder="Escriba para buscar... (ej: resma, impresión)" autocomplete="off" />
            <div id="sugerencias-pos" class="sugerencias"></div>
        </div>
        <div class="form-group">
            <label for="cantidad-pos">Cantidad</label>
            <input type="number" id="cantidad-pos" min="1" value="1" />
        </div>
    </div>

    <div class="table-responsive" style="margin-top: 1rem;">
        <table>
            <thead>
                <tr>
                    <th>Producto / Servicio</th>
                    <th>Cantidad</th>
                    <th>Precio Unit. ($)</th>
                    <th>Subtotal ($)</th>
                    <th>Subtotal (Bs.)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="cuerpo-carrito">
                <tr><td colspan="6" style="text-align:center;">El carrito está vacío.</td></tr>
            </tbody>
        </table>
    </div>

    <div class="pos-totales">
        <div class="pos-total-box">Total: <span id="total-usd">$0.00</span></div>
        <div class="pos-total-box bs">Total: <span id="total-bs">Bs. 0,00</span></div>
    </div>
</section>

<section class="card-panel">
    <h2>Pagos (puede combinar varios métodos)</h2>
    <div id="lineas-pago"></div>
    <button type="button" id="btn-agregar-pago" class="submit-btn btn-info" style="width:auto; padding: 0.5rem 1.2rem;">+ Agregar Pago</button>
    <div class="pos-totales" style="margin-top: 1rem;">
        <div class="pos-total-box" id="box-restante">Restante: <span id="restante-usd">$0.00</span></div>
    </div>
</section>

<section class="card-panel" style="display:flex; gap: 1rem; flex-wrap: wrap; justify-content: flex-end;">
    <button type="button" id="btn-vaciar" class="submit-btn btn-danger-pos" style="width:auto; padding: 0.8rem 1.5rem;">🗑️ Vaciar Carrito</button>
    <button type="button" id="btn-guardar" class="submit-btn btn-success" style="width:auto; padding: 0.8rem 1.5rem;">💾 Guardar Transacción</button>
    <button type="button" id="btn-guardar-imprimir" class="submit-btn" style="width:auto; padding: 0.8rem 1.5rem;">🖨️ Guardar e Imprimir Factura</button>
</section>

<?php require_once 'includes/footer.php'; ?>
<script src="assets/js/facturacion.js"></script>
<script>
initFacturacion();
</script>
</body>
</html>
