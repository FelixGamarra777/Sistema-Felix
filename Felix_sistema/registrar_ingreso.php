<?php
require_once 'includes/auth.php';
$paginaActiva = 'registrar_ingreso';
$tituloPagina = 'Registrar Ingreso';
require_once 'includes/header.php';
?>

<section class="card-panel">
    <h2>Registrar Nuevo Ingreso</h2>
    <div id="alert-message" class="alert"></div>

    <form id="form-movimiento">
        <input type="hidden" name="tipo" value="ingreso" />

        <div class="form-grid">
            <div class="form-group">
                <label for="concepto">Concepto</label>
                <div class="input-group">
                    <select id="concepto" name="concepto" required>
                        <option value="" disabled selected>Cargando conceptos...</option>
                    </select>
                    <button type="button" id="btn-open-modal" class="btn-icon" title="Agregar Concepto">+</button>
                </div>
            </div>

            <div class="form-group">
                <label for="cantidad">Cantidad</label>
                <input type="number" id="cantidad" name="cantidad" min="1" value="1" required />
            </div>

            <div class="form-group">
                <label for="precio">Precio Unit. (USD)</label>
                <input type="number" id="precio" name="precio" step="0.01" min="0.01" placeholder="0.00" required />
            </div>

            <div class="form-group">
                <label>Total Calculado (USD)</label>
                <div class="total-box" id="total-display">$0.00</div>
            </div>

            <div class="form-group">
                <label>Total en Bolívares (Bs.)</label>
                <div class="total-box" id="total-display-bs" style="background-color: #fff3cd; border-color: #ffeeba; color: #856404;">Bs. 0,00</div>
            </div>

            <div class="form-group">
                <label for="fuente">Fuente / Cliente</label>
                <input type="text" id="fuente" name="fuente" placeholder="Ej: Cliente General" required />
            </div>

            <div class="form-group">
                <label for="forma_pago">Forma de Pago</label>
                <select id="forma_pago" name="forma_pago" required>
                    <option value="" disabled selected>Cargando...</option>
                </select>
            </div>

            <div class="form-group">
                <label for="banco">Banco (opcional)</label>
                <div class="input-group">
                    <select id="banco" name="banco">
                        <option value="">Sin especificar</option>
                    </select>
                    <button type="button" id="btn-open-modal-banco" class="btn-icon" title="Agregar Banco">+</button>
                </div>
            </div>

            <div class="form-group">
                <label for="cliente">Cliente (opcional)</label>
                <div class="input-group">
                    <select id="cliente" name="cliente">
                        <option value="">Sin especificar</option>
                    </select>
                    <button type="button" id="btn-open-modal-cliente" class="btn-icon" title="Agregar Cliente">+</button>
                </div>
            </div>

            <div class="form-group">
                <label for="numero_factura">N&deg; Factura (opcional)</label>
                <input type="text" id="numero_factura" name="numero_factura" placeholder="Ej: 00123" />
            </div>

            <div class="form-group">
                <label for="fuente_referencia">Referencia (opcional)</label>
                <input type="text" id="fuente_referencia" name="fuente_referencia" placeholder="Ej: N&deg; de transferencia" />
            </div>
        </div>

        <div style="text-align: right;">
            <button type="submit" class="submit-btn btn-success" style="max-width: 250px;">Registrar Ingreso</button>
        </div>
    </form>
</section>

<?php require_once 'includes/footer.php'; ?>
<script src="assets/js/registrar-movimiento.js"></script>
<script>
initRegistrarMovimiento({ tipo: 'ingreso', mostrarCliente: true });
</script>
</body>
</html>
