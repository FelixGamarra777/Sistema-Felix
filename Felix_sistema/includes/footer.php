</div>

<footer>
    <p>&copy; Gestión de Finanzas - Inversiones Compunet Segura. C.A. - Gestión de Papelería</p>
</footer>

<div id="modal-concepto" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="close-modal">&times;</span>
        <h2 style="font-size: 1.2rem; margin-bottom: 1rem;">Agregar Nuevo Concepto</h2>
        <div class="form-group">
            <label for="nuevo-concepto">Nombre del Concepto</label>
            <input type="text" id="nuevo-concepto" placeholder="Ej: Impresión Color A4" style="margin-bottom: 1rem;">
        </div>
        <button id="btn-guardar-concepto" class="submit-btn">Guardar Concepto</button>
    </div>
</div>

<div id="modal-banco" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="close-modal-banco">&times;</span>
        <h2 style="font-size: 1.2rem; margin-bottom: 1rem;">Agregar Nuevo Banco</h2>
        <div class="form-group">
            <label for="nuevo-banco">Nombre del Banco</label>
            <input type="text" id="nuevo-banco" placeholder="Ej: Banco de Venezuela" style="margin-bottom: 1rem;">
        </div>
        <button id="btn-guardar-banco" class="submit-btn">Guardar Banco</button>
    </div>
</div>

<div id="modal-cliente" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="close-modal-cliente">&times;</span>
        <h2 style="font-size: 1.2rem; margin-bottom: 1rem;">Agregar Nuevo Cliente</h2>
        <div class="form-group">
            <label for="nuevo-cliente-nombre">Nombre / Razón Social</label>
            <input type="text" id="nuevo-cliente-nombre" placeholder="Ej: Distribuidora XYZ, C.A." style="margin-bottom: 1rem;">
        </div>
        <div class="form-group">
            <label for="nuevo-cliente-rif">Cédula / RIF</label>
            <input type="text" id="nuevo-cliente-rif" placeholder="Ej: J-12345678-9" style="margin-bottom: 1rem;">
        </div>
        <div class="form-group">
            <label for="nuevo-cliente-tipo">Tipo de Persona</label>
            <select id="nuevo-cliente-tipo" style="margin-bottom: 1rem;">
                <option value="Natural">Natural</option>
                <option value="Juridica">Jurídica</option>
            </select>
        </div>
        <button id="btn-guardar-cliente" class="submit-btn">Guardar Cliente</button>
    </div>
</div>

<div id="modal-proveedor" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="close-modal-proveedor">&times;</span>
        <h2 style="font-size: 1.2rem; margin-bottom: 1rem;">Agregar Nuevo Proveedor</h2>
        <div class="form-group">
            <label for="nuevo-proveedor-nombre">Nombre / Razón Social</label>
            <input type="text" id="nuevo-proveedor-nombre" placeholder="Ej: Papelería Central, C.A." style="margin-bottom: 1rem;">
        </div>
        <div class="form-group">
            <label for="nuevo-proveedor-rif">Cédula / RIF</label>
            <input type="text" id="nuevo-proveedor-rif" placeholder="Ej: J-98765432-1" style="margin-bottom: 1rem;">
        </div>
        <div class="form-group">
            <label for="nuevo-proveedor-tipo">Tipo de Persona</label>
            <select id="nuevo-proveedor-tipo" style="margin-bottom: 1rem;">
                <option value="Natural">Natural</option>
                <option value="Juridica">Jurídica</option>
            </select>
        </div>
        <button id="btn-guardar-proveedor" class="submit-btn">Guardar Proveedor</button>
    </div>
</div>

<div id="modal-tasa" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="close-modal-tasa">&times;</span>
        <h2 style="font-size: 1.2rem; margin-bottom: 1rem;">Fijar Tasa BCV Manual</h2>
        <div class="form-group">
            <label for="nueva-tasa">Tasa del día (Bs. por USD)</label>
            <input type="number" id="nueva-tasa" step="0.0001" min="0.0001" placeholder="Ej: 36.50" style="margin-bottom: 1rem;">
        </div>
        <button id="btn-guardar-tasa" class="submit-btn">Guardar Tasa</button>
    </div>
</div>

<script src="assets/js/filtro-mes.js"></script>
<script src="assets/js/common.js"></script>
