<?php
session_start();
// Si el usuario no ha iniciado sesión, se le deniega el acceso y se le redirige al login
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Finanzas - Inversiones Compunet Segura. C.A.</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #007bff;
            --primary-hover: #0056b3;
            --success: #28a745;
            --danger: #dc3545;
            --dark: #343a40;
            --light: #f8f9fa;
            --bg-color: #e9ecef;
            --card-bg: #ffffff;
            --border: #ced4da;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: var(--bg-color); color: var(--dark); line-height: 1.6; }
        header { background-color: var(--primary); color: white; padding: 2rem 1rem; text-align: center; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); position: relative; }
        header h1 { font-size: 2rem; margin-bottom: 0.5rem; font-weight: 600; }
        header p { font-size: 1rem; opacity: 0.9; }
        
        /* Estilos añadidos para la barra de usuario en el header */
        .user-bar { position: absolute; top: 10px; right: 20px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; background: rgba(0,0,0,0.15); padding: 5px 15px; border-radius: 20px; }
        .user-bar span { font-weight: bold; }
        .logout-btn { color: #fff; text-decoration: none; background: var(--danger); padding: 3px 10px; border-radius: 5px; font-size: 0.8rem; transition: background 0.3s; }
        .logout-btn:hover { background: #c82333; }

        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .card-panel { background-color: var(--card-bg); padding: 2rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); margin-bottom: 2rem; }
        .card-panel h2 { font-size: 1.4rem; color: var(--dark); margin-bottom: 1.5rem; border-bottom: 2px solid var(--primary); padding-bottom: 0.5rem; display: inline-block; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; align-items: end; }
        .form-group { display: flex; flex-direction: column; }
        label { font-size: 0.9rem; color: #495057; margin-bottom: 0.5rem; font-weight: 600; }
        input, select { padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; font-size: 1rem; transition: border-color 0.3s; width: 100%; }
        input:focus, select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1); }
        .input-group { display: flex; gap: 0.5rem; }
        .btn-icon { background-color: var(--success); color: white; border: none; border-radius: 8px; padding: 0 1rem; font-size: 1.5rem; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; }
        .btn-icon:hover { background-color: #218838; }
        .total-box { background-color: #e8f4fd; border: 1px solid #b8daff; color: #004085; padding: 0.75rem; border-radius: 8px; font-size: 1.2rem; font-weight: bold; text-align: center; }
        .submit-btn { padding: 0.8rem 2rem; border: none; border-radius: 8px; background-color: var(--primary); color: white; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.3s; width: 100%; }
        .submit-btn:hover { background-color: var(--primary-hover); }
        .filter-section { display: flex; justify-content: center; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .summary-card { background-color: var(--card-bg); padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); text-align: center; border-left: 5px solid; }
        .summary-card h3 { font-size: 1rem; color: #6c757d; margin-bottom: 0.5rem; text-transform: uppercase; }
        .summary-card .value { font-size: 2rem; font-weight: bold; }
        .ingresos { border-color: var(--success); }
        .ingresos .value { color: var(--success); }
        .egresos { border-color: var(--danger); }
        .egresos .value { color: var(--danger); }
        .balance { border-color: var(--primary); }
        .balance .value { color: var(--primary); }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; white-space: nowrap; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { background-color: var(--light); font-weight: 600; color: #495057; }
        tbody tr:hover { background-color: rgba(0,0,0,0.02); }
        .badge-ingreso { color: var(--success); font-weight: bold; background: #d4edda; padding: 4px 8px; border-radius: 12px; font-size: 0.85rem;}
        .badge-egreso { color: var(--danger); font-weight: bold; background: #f8d7da; padding: 4px 8px; border-radius: 12px; font-size: 0.85rem;}
        .delete-btn { background-color: var(--danger); color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; transition: 0.3s; }
        .delete-btn:hover { background-color: #c82333; }
        
        /* Modal Estilizado */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
        .modal.show { display: flex; opacity: 1; }
        .modal-content { background-color: var(--card-bg); padding: 2rem; border-radius: 12px; width: 90%; max-width: 400px; position: relative; box-shadow: 0 10px 25px rgba(0,0,0,0.2); transform: translateY(-20px); transition: transform 0.3s ease; }
        .modal.show .modal-content { transform: translateY(0); }
        .close-btn { position: absolute; top: 15px; right: 20px; font-size: 1.5rem; cursor: pointer; color: #aaa; }
        .close-btn:hover { color: var(--dark); }
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; display: none; font-weight: 500; }
        .alert.success { display: block; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        footer { background-color: var(--dark); color: white; text-align: center; padding: 1.5rem; margin-top: 3rem; }
        
        /* Ajuste responsivo para barra superior */
        @media(max-width: 768px) {
            .user-bar { position: static; display: inline-flex; margin-top: 10px; width: auto; }
        }
    </style>
</head>
<body>

    <header>
        <div class="user-bar">
            <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
            <a href="cerrar_sesion.php" class="logout-btn">Cerrar Sesión</a>
        </div>
        
        <h1>Gestión de Finanzas - Inversiones Compunet Segura. C.A.</h1>
        <p>Gestión Inteligente de Ingresos y Egresos - Área de Papelería</p>
    </header>

    <div class="container">
        <section class="card-panel">
            <h2>Registrar Nuevo Movimiento</h2>
            <div id="alert-message" class="alert"></div>
            
            <form id="form-movimiento">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="tipo">Tipo</label>
                        <select id="tipo" name="tipo" required>
                            <option value="ingreso">Ingreso (Venta)</option>
                            <option value="egreso">Egreso (Compra)</option>
                        </select>
                    </div>

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
                        <label>Total Calculado</label>
                        <div class="total-box" id="total-display">$0.00</div>
                    </div>

                    <div class="form-group">
                        <label for="fuente">Fuente/Proveedor</label>
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

                    <div class="form-group" id="grupo-cliente">
                        <label for="cliente">Cliente (opcional)</label>
                        <div class="input-group">
                            <select id="cliente" name="cliente">
                                <option value="">Sin especificar</option>
                            </select>
                            <button type="button" id="btn-open-modal-cliente" class="btn-icon" title="Agregar Cliente">+</button>
                        </div>
                    </div>

                    <div class="form-group" id="grupo-proveedor">
                        <label for="proveedor">Proveedor (opcional)</label>
                        <div class="input-group">
                            <select id="proveedor" name="proveedor">
                                <option value="">Sin especificar</option>
                            </select>
                            <button type="button" id="btn-open-modal-proveedor" class="btn-icon" title="Agregar Proveedor">+</button>
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
                    <button type="submit" class="submit-btn" style="max-width: 250px;">Registrar Movimiento</button>
                </div>
            </form>
        </section>

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
                <label for="fecha-desde">Desde:</label>
                <input type="date" id="fecha-desde">
            </div>
            <div class="form-group" style="flex-direction:row; align-items:center; gap:0.5rem;">
                <label for="fecha-hasta">Hasta:</label>
                <input type="date" id="fecha-hasta">
            </div>
            <button id="btn-filtrar" class="submit-btn" style="width: auto; padding: 0.6rem 1.5rem; background: var(--success);">Filtrar</button>
            <button id="btn-estadisticas" class="submit-btn" style="width: auto; padding: 0.6rem 1.5rem; background: #17a2b8;">Ver Estadísticas</button>
            <button id="btn-limpiar-filtro" class="submit-btn" style="width: auto; padding: 0.6rem 1.5rem; background: #6c757d;">Hoy</button>
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

        <section class="card-panel" id="seccion-graficos" style="display: none;">
            <h2>Análisis Estadístico del Periodo</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; align-items: center;">
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="graficoTorta"></canvas>
                </div>
                <div style="position: relative; height: 300px; width: 100%;">
                    <canvas id="graficoBarras"></canvas>
                </div>
            </div>
        </section>
    </div>

    <footer>
        <p>&copy; Gestión de Finanzas - Inversiones Compunet Segura. C.A.- Gestión de Papelería</p>
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

<script>
    // --- CONTROLADORES GRÁFICOS ---
let chartTorta = null;
let chartBarras = null;

// --- MAPEO DE ELEMENTOS ---
const formMovimiento = document.getElementById("form-movimiento");
const selectConcepto = document.getElementById("concepto");
const cuerpoTabla = document.getElementById("cuerpo-tabla");
const inputCantidad = document.getElementById("cantidad");
const inputPrecio = document.getElementById("precio");
const displayTotal = document.getElementById("total-display");
const inputDesde = document.getElementById("fecha-desde");
const inputHasta = document.getElementById("fecha-hasta");
const alertBox = document.getElementById("alert-message");

// --- NUEVOS CAMPOS (forma de pago / banco / cliente / proveedor / factura / referencia) ---
const selectTipo = document.getElementById("tipo");
const selectFormaPago = document.getElementById("forma_pago");
const selectBanco = document.getElementById("banco");
const selectCliente = document.getElementById("cliente");
const selectProveedor = document.getElementById("proveedor");
const grupoCliente = document.getElementById("grupo-cliente");
const grupoProveedor = document.getElementById("grupo-proveedor");
const inputNumeroFactura = document.getElementById("numero_factura");
const inputFuenteReferencia = document.getElementById("fuente_referencia");

const modal = document.getElementById("modal-concepto");
const btnOpenModal = document.getElementById("btn-open-modal");
const btnCloseModal = document.getElementById("close-modal");
const btnGuardarConcepto = document.getElementById("btn-guardar-concepto");
const inputNuevoConcepto = document.getElementById("nuevo-concepto");

// --- Modal Banco ---
const modalBanco = document.getElementById("modal-banco");
const btnOpenModalBanco = document.getElementById("btn-open-modal-banco");
const btnCloseModalBanco = document.getElementById("close-modal-banco");
const btnGuardarBanco = document.getElementById("btn-guardar-banco");
const inputNuevoBanco = document.getElementById("nuevo-banco");

// --- Modal Cliente ---
const modalCliente = document.getElementById("modal-cliente");
const btnOpenModalCliente = document.getElementById("btn-open-modal-cliente");
const btnCloseModalCliente = document.getElementById("close-modal-cliente");
const btnGuardarCliente = document.getElementById("btn-guardar-cliente");
const inputNuevoClienteNombre = document.getElementById("nuevo-cliente-nombre");
const inputNuevoClienteRif = document.getElementById("nuevo-cliente-rif");
const inputNuevoClienteTipo = document.getElementById("nuevo-cliente-tipo");

// --- Modal Proveedor ---
const modalProveedor = document.getElementById("modal-proveedor");
const btnOpenModalProveedor = document.getElementById("btn-open-modal-proveedor");
const btnCloseModalProveedor = document.getElementById("close-modal-proveedor");
const btnGuardarProveedor = document.getElementById("btn-guardar-proveedor");
const inputNuevoProveedorNombre = document.getElementById("nuevo-proveedor-nombre");
const inputNuevoProveedorRif = document.getElementById("nuevo-proveedor-rif");
const inputNuevoProveedorTipo = document.getElementById("nuevo-proveedor-tipo");

// --- UTILS ---
function formatearUSD(valor) {
    return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(valor);
}

function calcularTotal() {
    const cant = parseInt(inputCantidad.value) || 0;
    const prec = parseFloat(inputPrecio.value) || 0;
    const total = cant * prec;
    displayTotal.textContent = formatearUSD(total);
    return total;
}

inputCantidad.addEventListener("input", calcularTotal);
inputPrecio.addEventListener("input", calcularTotal);

// --- MANEJO INTERFAZ MODAL ---
btnOpenModal.onclick = () => { modal.classList.add("show"); inputNuevoConcepto.focus(); };
btnCloseModal.onclick = () => { modal.classList.remove("show"); };

btnOpenModalBanco.onclick = () => { modalBanco.classList.add("show"); inputNuevoBanco.focus(); };
btnCloseModalBanco.onclick = () => { modalBanco.classList.remove("show"); };

btnOpenModalCliente.onclick = () => { modalCliente.classList.add("show"); inputNuevoClienteNombre.focus(); };
btnCloseModalCliente.onclick = () => { modalCliente.classList.remove("show"); };

btnOpenModalProveedor.onclick = () => { modalProveedor.classList.add("show"); inputNuevoProveedorNombre.focus(); };
btnCloseModalProveedor.onclick = () => { modalProveedor.classList.remove("show"); };

window.onclick = (e) => {
    if (e.target === modal) modal.classList.remove("show");
    if (e.target === modalBanco) modalBanco.classList.remove("show");
    if (e.target === modalCliente) modalCliente.classList.remove("show");
    if (e.target === modalProveedor) modalProveedor.classList.remove("show");
};

// --- CARGA DE CONCEPTOS ---
async function cargarConceptos() {
    try {
        const respuesta = await fetch('obtener_conceptos.php');
        const resultado = await respuesta.json();
        if(resultado.exito) {
            selectConcepto.innerHTML = '<option value="" disabled selected>Seleccione un concepto...</option>';
            resultado.datos.forEach(c => {
                const opt = document.createElement("option");
                opt.value = c.id_concepto;
                opt.textContent = c.nombre;
                selectConcepto.appendChild(opt);
            });
        }
    } catch (error) {
        console.error("Error cargando conceptos:", error);
    }
}

// --- CARGA DE FORMAS DE PAGO ---
async function cargarFormasPago() {
    try {
        const respuesta = await fetch('obtener_formas_pago.php');
        const resultado = await respuesta.json();
        if (resultado.exito) {
            selectFormaPago.innerHTML = '<option value="" disabled selected>Seleccione...</option>';
            resultado.datos.forEach(valor => {
                const opt = document.createElement("option");
                opt.value = valor;
                opt.textContent = valor;
                selectFormaPago.appendChild(opt);
            });
        }
    } catch (error) {
        console.error("Error cargando formas de pago:", error);
    }
}

// --- CARGA DE BANCOS ---
async function cargarBancos() {
    try {
        const respuesta = await fetch('obtener_bancos.php');
        const resultado = await respuesta.json();
        if (resultado.exito) {
            selectBanco.innerHTML = '<option value="">Sin especificar</option>';
            resultado.datos.forEach(b => {
                const opt = document.createElement("option");
                opt.value = b.id_banco;
                opt.textContent = b.nombre_banco;
                selectBanco.appendChild(opt);
            });
        }
    } catch (error) {
        console.error("Error cargando bancos:", error);
    }
}

// --- CARGA DE CLIENTES ---
async function cargarClientes() {
    try {
        const respuesta = await fetch('obtener_clientes.php');
        const resultado = await respuesta.json();
        if (resultado.exito) {
            selectCliente.innerHTML = '<option value="">Sin especificar</option>';
            resultado.datos.forEach(c => {
                const opt = document.createElement("option");
                opt.value = c.id_cliente;
                opt.textContent = `${c.nombre_empresa} (${c.cedula_rif})`;
                selectCliente.appendChild(opt);
            });
        }
    } catch (error) {
        console.error("Error cargando clientes:", error);
    }
}

// --- CARGA DE PROVEEDORES ---
async function cargarProveedores() {
    try {
        const respuesta = await fetch('obtener_proveedores.php');
        const resultado = await respuesta.json();
        if (resultado.exito) {
            selectProveedor.innerHTML = '<option value="">Sin especificar</option>';
            resultado.datos.forEach(p => {
                const opt = document.createElement("option");
                opt.value = p.id_proveedor;
                opt.textContent = `${p.nombre_empresa} (${p.cedula_rif})`;
                selectProveedor.appendChild(opt);
            });
        }
    } catch (error) {
        console.error("Error cargando proveedores:", error);
    }
}

// --- MOSTRAR CLIENTE SI ES INGRESO / PROVEEDOR SI ES EGRESO ---
function actualizarVisibilidadClienteProveedor() {
    if (selectTipo.value === "ingreso") {
        grupoCliente.style.display = "";
        grupoProveedor.style.display = "none";
        selectProveedor.value = "";
    } else {
        grupoProveedor.style.display = "";
        grupoCliente.style.display = "none";
        selectCliente.value = "";
    }
}
selectTipo.addEventListener("change", actualizarVisibilidadClienteProveedor);

// --- GUARDADO DE CONCEPTOS ---
btnGuardarConcepto.onclick = async () => {
    const nuevo = inputNuevoConcepto.value.trim();
    if (!nuevo) return;

    try {
        btnGuardarConcepto.disabled = true;
        const respuesta = await fetch('insertar_concepto.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre: nuevo })
        });
        const resultado = await respuesta.json();

        if (resultado.exito) {
            await cargarConceptos();
            selectConcepto.value = resultado.id_concepto;
            inputNuevoConcepto.value = "";
            modal.classList.remove("show");
        } else {
            alert(resultado.mensaje);
        }
    } catch (error) {
        console.error(error);
    } finally {
        btnGuardarConcepto.disabled = false;
    }
};

// --- GUARDADO DE BANCOS ---
btnGuardarBanco.onclick = async () => {
    const nuevo = inputNuevoBanco.value.trim();
    if (!nuevo) return;

    try {
        btnGuardarBanco.disabled = true;
        const respuesta = await fetch('insertar_banco.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre_banco: nuevo })
        });
        const resultado = await respuesta.json();

        if (resultado.exito) {
            await cargarBancos();
            selectBanco.value = resultado.id_banco;
            inputNuevoBanco.value = "";
            modalBanco.classList.remove("show");
        } else {
            alert(resultado.mensaje);
        }
    } catch (error) {
        console.error(error);
    } finally {
        btnGuardarBanco.disabled = false;
    }
};

// --- GUARDADO DE CLIENTES ---
btnGuardarCliente.onclick = async () => {
    const nombre = inputNuevoClienteNombre.value.trim();
    const rif = inputNuevoClienteRif.value.trim();
    if (!nombre || !rif) { alert("Nombre y Cédula/RIF son obligatorios."); return; }

    try {
        btnGuardarCliente.disabled = true;
        const respuesta = await fetch('insertar_cliente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombre_empresa: nombre,
                cedula_rif: rif,
                tipo_persona: inputNuevoClienteTipo.value
            })
        });
        const resultado = await respuesta.json();

        if (resultado.exito) {
            await cargarClientes();
            selectCliente.value = resultado.id_cliente;
            inputNuevoClienteNombre.value = "";
            inputNuevoClienteRif.value = "";
            modalCliente.classList.remove("show");
        } else {
            alert(resultado.mensaje);
        }
    } catch (error) {
        console.error(error);
    } finally {
        btnGuardarCliente.disabled = false;
    }
};

// --- GUARDADO DE PROVEEDORES ---
btnGuardarProveedor.onclick = async () => {
    const nombre = inputNuevoProveedorNombre.value.trim();
    const rif = inputNuevoProveedorRif.value.trim();
    if (!nombre || !rif) { alert("Nombre y Cédula/RIF son obligatorios."); return; }

    try {
        btnGuardarProveedor.disabled = true;
        const respuesta = await fetch('insertar_proveedor.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombre_empresa: nombre,
                cedula_rif: rif,
                tipo_persona: inputNuevoProveedorTipo.value
            })
        });
        const resultado = await respuesta.json();

        if (resultado.exito) {
            await cargarProveedores();
            selectProveedor.value = resultado.id_proveedor;
            inputNuevoProveedorNombre.value = "";
            inputNuevoProveedorRif.value = "";
            modalProveedor.classList.remove("show");
        } else {
            alert(resultado.mensaje);
        }
    } catch (error) {
        console.error(error);
    } finally {
        btnGuardarProveedor.disabled = false;
    }
};

// --- GUARDADO DE MOVIMIENTOS (CORREGIDO) ---
formMovimiento.addEventListener("submit", async (e) => {
    e.preventDefault();
    if(!selectConcepto.value) {
        alert("Por favor, seleccione un concepto válido.");
        return;
    }

    const cant_enviar = Number(inputCantidad.value);
    const prec_enviar = Number(inputPrecio.value);

    const datosMovimiento = {
        id_concepto: selectConcepto.value,
        tipo: selectTipo.value,
        cantidad: cant_enviar,
        precio_unitario: prec_enviar,
        monto_total: Number((cant_enviar * prec_enviar).toFixed(2)),
        fuente: document.getElementById("fuente").value,
        forma_pago: selectFormaPago.value,
        id_banco: selectBanco.value || null,
        id_cliente: selectTipo.value === "ingreso" ? (selectCliente.value || null) : null,
        id_proveedor: selectTipo.value === "egreso" ? (selectProveedor.value || null) : null,
        numero_factura: inputNumeroFactura.value.trim() || null,
        fuente_referencia: inputFuenteReferencia.value.trim() || null
    };

    try {
        const respuesta = await fetch('insertar_movimiento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datosMovimiento)
        });
        const resultado = await respuesta.json();

        if (resultado.exito) {
            formMovimiento.reset();
            displayTotal.textContent = "$0.00";
            actualizarVisibilidadClienteProveedor();
            inputDesde.value = ""; 
            inputHasta.value = "";
            
            await cargarMovimientos('', ''); 
            
            alertBox.textContent = "¡Movimiento registrado con éxito!";
            alertBox.className = "alert success";
            alertBox.style.display = "block";
            setTimeout(() => alertBox.style.display = "none", 3000);
        } else {
            alert("Error en el servidor: " + resultado.mensaje);
        }
    } catch (error) {
        console.error("Error de red:", error);
    }
});

// --- CARGAR HISTORIAL ---
async function cargarMovimientos(fechaDesde = '', fechaHasta = '') {
    try {
        let url = 'obtener_movimientos.php';
        if (fechaDesde && fechaHasta) url += `?desde=${fechaDesde}&hasta=${fechaHasta}`;

        const respuesta = await fetch(url);
        const resultado = await respuesta.json();

        if (resultado.exito) {
            cuerpoTabla.innerHTML = "";
            let tIngresos = 0, tEgresos = 0;

            if (resultado.datos.length === 0) {
                cuerpoTabla.innerHTML = '<tr><td colspan="11" style="text-align:center;">No hay registros para este rango.</td></tr>';
            } else {
                resultado.datos.forEach(m => {
                    const precio = parseFloat(m.precio);
                    const monto = parseFloat(m.monto);
                    
                    if (m.tipo === 'ingreso') tIngresos += monto;
                    else tEgresos += monto;

                    const f = new Date(m.fecha).toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' });
                    const clienteOProveedor = m.cliente || m.proveedor || '-';

                    cuerpoTabla.innerHTML += `
                        <tr>
                            <td>${f}</td>
                            <td>${m.concepto}</td>
                            <td><span class="badge-${m.tipo}">${m.tipo.toUpperCase()}</span></td>
                            <td>${m.cantidad}</td>
                            <td>${formatearUSD(precio)}</td>
                            <td style="font-weight:bold;">${formatearUSD(monto)}</td>
                            <td>${m.fuente}</td>
                            <td>${m.forma_pago || '-'}</td>
                            <td>${clienteOProveedor}</td>
                            <td>${m.numero_factura || '-'}</td>
                            <td><button class="delete-btn" onclick="eliminarMovimiento(${m.id})">Eliminar</button></td>
                        </tr>`;
                });
            }

            document.getElementById("total-ingresos").textContent = formatearUSD(tIngresos);
            document.getElementById("total-egresos").textContent = formatearUSD(tEgresos);
            document.getElementById("balance-neto").textContent = formatearUSD(tIngresos - tEgresos);
            
            if(document.getElementById("seccion-graficos").style.display === "block") {
                cargarEstadisticas(fechaDesde, fechaHasta);
            }
        }
    } catch (error) {
        console.error(error);
        cuerpoTabla.innerHTML = '<tr><td colspan="11" style="text-align:center; color:red;">Error de comunicación con el servidor.</td></tr>';
    }
}

// --- ELIMINAR MOVIMIENTO ---
async function eliminarMovimiento(id) {
    if (!confirm("¿Eliminar este registro?")) return;
    try {
        const respuesta = await fetch('eliminar_movimiento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const resultado = await respuesta.json();
        if (resultado.exito) {
            cargarMovimientos(inputDesde.value, inputHasta.value);
        }
    } catch (error) {
        console.error(error);
    }
}

// --- FILTROS ---
document.getElementById("btn-filtrar").addEventListener("click", () => {
    if(!inputDesde.value || !inputHasta.value) { alert("Completa el rango de fechas."); return; }
    cargarMovimientos(inputDesde.value, inputHasta.value);
});

document.getElementById("btn-limpiar-filtro").addEventListener("click", () => {
    inputDesde.value = ""; inputHasta.value = "";
    document.getElementById("seccion-graficos").style.display = "none";
    cargarMovimientos();
});

document.getElementById("btn-estadisticas").addEventListener("click", () => {
    document.getElementById("seccion-graficos").style.display = "block";
    cargarEstadisticas(inputDesde.value, inputHasta.value);
    document.getElementById("seccion-graficos").scrollIntoView({ behavior: 'smooth' });
});

// --- LÓGICA DE GRÁFICOS ---
async function cargarEstadisticas(desde = '', hasta = '') {
    try {
        let url = 'obtener_estadisticas.php';
        if (desde && hasta) url += `?desde=${desde}&hasta=${hasta}`;
        const respuesta = await fetch(url);
        const resultado = await respuesta.json();

        if (resultado.exito) {
            renderizarGraficoTorta(resultado.totales_tipo);
            renderizarGraficoBarras(resultado.totales_concepto);
        }
    } catch (error) {
        console.error(error);
    }
}

function renderizarGraficoTorta(datosTipo) {
    const ctx = document.getElementById('graficoTorta').getContext('2d');
    if (chartTorta) chartTorta.destroy();

    let etiquetas = [], valores = [], colores = [];
    datosTipo.forEach(item => {
        etiquetas.push(item.tipo.toUpperCase());
        valores.push(parseFloat(item.total));
        colores.push(item.tipo === 'ingreso' ? '#28a745' : '#dc3545');
    });

    chartTorta = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: etiquetas.length ? etiquetas : ['Sin registros'],
            datasets: [{ data: valores.length ? valores : [1], backgroundColor: valores.length ? colores : ['#e9ecef'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { title: { display: true, text: 'Proporción Ingresos / Egresos' } } }
    });
}

function renderizarGraficoBarras(datosConceptos) {
    const ctx = document.getElementById('graficoBarras').getContext('2d');
    if (chartBarras) chartBarras.destroy();

    const conceptosUnicos = [...new Set(datosConceptos.map(item => item.concepto))];
    let ingresos = [], egresos = [];

    conceptosUnicos.forEach(c => {
        const ing = datosConceptos.find(d => d.concepto === c && d.tipo === 'ingreso');
        const egr = datosConceptos.find(d => d.concepto === c && d.tipo === 'egreso');
        ingresos.push(ing ? parseFloat(ing.total) : 0);
        egresos.push(egr ? parseFloat(egr.total) : 0);
    });

    chartBarras = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: conceptosUnicos.length ? conceptosUnicos : ['Sin registros'],
            datasets: [
                { label: 'Ingresos ($)', data: ingresos, backgroundColor: 'rgba(40, 167, 69, 0.7)' },
                { label: 'Egresos ($)', data: egresos, backgroundColor: 'rgba(220, 53, 69, 0.7)' }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { title: { display: true, text: 'Volumen por Concepto' } } }
    });
}

window.addEventListener("DOMContentLoaded", () => {
    cargarConceptos();
    cargarFormasPago();
    cargarBancos();
    cargarClientes();
    cargarProveedores();
    actualis_v = actualizarVisibilidadClienteProveedor();
    cargarMovimientos();
});
</script>
</body>
</html>