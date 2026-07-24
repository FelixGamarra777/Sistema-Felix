<?php
require_once 'includes/auth.php';
$paginaActiva = 'registrar_egreso';
$tituloPagina = 'Registrar Egreso (Compras al Mayor)';
require_once 'includes/header.php';
?>

<section class="card-panel">
    <h2>Datos del Egreso (Compra al Mayor)</h2>
    <div id="alert-message" class="alert"></div>
    <div class="pos-grid">
        <div class="form-group">
            <label for="numero-factura">N&deg; de Comprobante (opcional, correlativo autom&aacute;tico)</label>
            <input type="text" id="numero-factura" placeholder="Cargando..." />
        </div>
        <div class="form-group">
            <label for="cliente">Proveedor (opcional)</label>
            <div class="input-group">
                <select id="cliente">
                    <option value="">Sin proveedor</option>
                </select>
                <button type="button" id="btn-open-modal-proveedor" class="btn-icon" title="Agregar Proveedor">+</button>
            </div>
        </div>
    </div>
</section>

<section class="card-panel">
    <h2>Panel de Compra</h2>
    <div id="pos-chips" class="filtros-rapidos" style="margin-bottom: 1rem;"></div>
    <div class="pos-panel-layout">
        <div id="pos-catalogo" class="pos-catalogo-grid">
            <p style="padding: 1rem; color: #6c757d;">Cargando catálogo...</p>
        </div>
        <aside class="pos-numpad-col">
            <label for="cantidad-pos">Cantidad a agregar</label>
            <input type="number" id="cantidad-pos" min="1" value="1" class="numpad-display" />
            <div class="numpad" id="numpad">
                <button type="button" data-np="7">7</button>
                <button type="button" data-np="8">8</button>
                <button type="button" data-np="9">9</button>
                <button type="button" data-np="4">4</button>
                <button type="button" data-np="5">5</button>
                <button type="button" data-np="6">6</button>
                <button type="button" data-np="1">1</button>
                <button type="button" data-np="2">2</button>
                <button type="button" data-np="3">3</button>
                <button type="button" data-np="." class="np-accion">.</button>
                <button type="button" data-np="0">0</button>
                <button type="button" data-np="borrar" class="np-accion">⌫</button>
                <button type="button" data-np="limpiar" class="np-accion" style="grid-column: span 3;">C — Limpiar</button>
                <button type="button" data-np="listo" class="np-ok">✔ Listo</button>
            </div>
            <div class="numpad-destino" id="numpad-destino">Escribiendo en: <strong>Cantidad</strong></div>
        </aside>
    </div>

    <div class="form-group buscador-wrapper" style="margin-top: 1.5rem;">
        <label for="buscador-pos">¿No encuentra el botón? Busque en el catálogo</label>
        <input type="text" id="buscador-pos" placeholder="Escriba para buscar... (ej: resma, lápices)" autocomplete="off" />
        <div id="sugerencias-pos" class="sugerencias"></div>
    </div>

    <div class="table-responsive" style="margin-top: 1rem;">
        <table>
            <thead>
                <tr>
                    <th>Insumo / Producto</th>
                    <th>Cantidad</th>
                    <th>Costo Unit. ($)</th>
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
    <h2>Pagos al proveedor (puede combinar varios métodos)</h2>
    <div class="fastcash-row">
        <button type="button" class="fastcash exacto" data-exacto="USD">🎯 Monto Exacto $</button>
        <button type="button" class="fastcash exacto" data-exacto="BS">🎯 Monto Exacto Bs.</button>
        <button type="button" class="fastcash" data-billete="1">$1</button>
        <button type="button" class="fastcash" data-billete="5">$5</button>
        <button type="button" class="fastcash" data-billete="10">$10</button>
        <button type="button" class="fastcash" data-billete="20">$20</button>
        <button type="button" class="fastcash" data-billete="50">$50</button>
        <button type="button" class="fastcash" data-billete="100">$100</button>
    </div>
    <div id="vuelto-box" class="vuelto-box" style="display:none;"></div>
    <div id="lineas-pago"></div>
    <button type="button" id="btn-agregar-pago" class="submit-btn btn-info" style="width:auto; padding: 0.5rem 1.2rem;">+ Agregar Pago</button>
    <div class="pos-totales" style="margin-top: 1rem;">
        <div class="pos-total-box" id="box-restante">Restante: <span id="restante-usd">$0.00</span></div>
    </div>
</section>

<section class="card-panel" style="display:flex; gap: 1rem; flex-wrap: wrap; justify-content: flex-end;">
    <button type="button" id="btn-vaciar" class="submit-btn btn-danger-pos" style="width:auto; padding: 0.8rem 1.5rem;">🗑️ Vaciar Carrito</button>
    <button type="button" id="btn-guardar" class="submit-btn btn-success" style="width:auto; padding: 0.8rem 1.5rem;">💾 Guardar Transacción</button>
    <button type="button" id="btn-guardar-imprimir" class="submit-btn" style="width:auto; padding: 0.8rem 1.5rem;">🖨️ Guardar e Imprimir Comprobante</button>
</section>

<?php require_once 'includes/footer.php'; ?>
<script src="assets/js/facturacion.js"></script>
<script src="assets/js/registrar-egreso.js"></script>
<script>
initRegistrarEgreso();
</script>
</body>
</html>
