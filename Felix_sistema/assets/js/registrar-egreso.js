// Registro de egresos (compras, gastos, pagos a proveedores).
// Los ingresos ya NO se registran aquí: el único punto de entrada de ventas
// es el módulo de Facturación (POS), que escribe vía guardar_factura.php.
function initRegistrarEgreso() {
    const form = document.getElementById('form-movimiento');
    const selectConcepto = document.getElementById('concepto');
    const selectFormaPago = document.getElementById('forma_pago');
    const selectBanco = document.getElementById('banco');
    const selectProveedor = document.getElementById('proveedor');
    const inputCantidad = document.getElementById('cantidad');
    const inputPrecio = document.getElementById('precio');
    const displayTotal = document.getElementById('total-display');
    const displayTotalBs = document.getElementById('total-display-bs');
    const inputNumeroFactura = document.getElementById('numero_factura');
    const inputFuenteReferencia = document.getElementById('fuente_referencia');

    function calcularTotal() {
        const cant = parseInt(inputCantidad.value) || 0;
        const prec = parseFloat(inputPrecio.value) || 0;
        const total = cant * prec;
        displayTotal.textContent = formatearUSD(total);
        if (displayTotalBs) {
            const tasa = window.TASA_BCV.tasa || 0;
            displayTotalBs.textContent = tasa > 0 ? formatearBs(total * tasa) : 'Sin tasa BCV';
        }
    }

    inputCantidad.addEventListener('input', calcularTotal);
    inputPrecio.addEventListener('input', calcularTotal);
    document.addEventListener('tasa-bcv-lista', calcularTotal);

    // Autocompletado: al elegir un concepto del catálogo se refleja su precio
    selectConcepto.addEventListener('change', () => {
        const opt = selectConcepto.selectedOptions[0];
        if (opt && opt.dataset.precio !== undefined) {
            inputPrecio.value = opt.dataset.precio;
        }
        calcularTotal();
    });

    const modalConcepto = initModal('modal-concepto', 'btn-open-modal', 'close-modal', 'nuevo-concepto');
    const modalBanco = initModal('modal-banco', 'btn-open-modal-banco', 'close-modal-banco', 'nuevo-banco');
    const modalProveedor = initModal('modal-proveedor', 'btn-open-modal-proveedor', 'close-modal-proveedor', 'nuevo-proveedor-nombre');

    document.getElementById('btn-guardar-concepto').onclick = async () => {
        const input = document.getElementById('nuevo-concepto');
        const nombre = input.value.trim();
        if (!nombre) return;

        const btn = document.getElementById('btn-guardar-concepto');
        try {
            btn.disabled = true;
            const respuesta = await fetch('insertar_concepto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nombre })
            });
            const resultado = await respuesta.json();
            if (resultado.exito) {
                await cargarConceptos(selectConcepto);
                selectConcepto.value = resultado.id_concepto;
                input.value = '';
                modalConcepto.close();
            } else {
                alert(resultado.mensaje);
            }
        } finally {
            btn.disabled = false;
        }
    };

    document.getElementById('btn-guardar-banco').onclick = async () => {
        const input = document.getElementById('nuevo-banco');
        const nombre = input.value.trim();
        if (!nombre) return;

        const btn = document.getElementById('btn-guardar-banco');
        try {
            btn.disabled = true;
            const respuesta = await fetch('insertar_banco.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nombre_banco: nombre })
            });
            const resultado = await respuesta.json();
            if (resultado.exito) {
                await cargarBancos(selectBanco);
                selectBanco.value = resultado.id_banco;
                input.value = '';
                modalBanco.close();
            } else {
                alert(resultado.mensaje);
            }
        } finally {
            btn.disabled = false;
        }
    };

    document.getElementById('btn-guardar-proveedor').onclick = async () => {
        const nombre = document.getElementById('nuevo-proveedor-nombre').value.trim();
        const rif = document.getElementById('nuevo-proveedor-rif').value.trim();
        if (!nombre || !rif) { alert('Nombre y Cédula/RIF son obligatorios.'); return; }

        const btn = document.getElementById('btn-guardar-proveedor');
        try {
            btn.disabled = true;
            const respuesta = await fetch('insertar_proveedor.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    nombre_empresa: nombre,
                    cedula_rif: rif,
                    tipo_persona: document.getElementById('nuevo-proveedor-tipo').value
                })
            });
            const resultado = await respuesta.json();
            if (resultado.exito) {
                await cargarProveedores(selectProveedor);
                selectProveedor.value = resultado.id_proveedor;
                document.getElementById('nuevo-proveedor-nombre').value = '';
                document.getElementById('nuevo-proveedor-rif').value = '';
                modalProveedor.close();
            } else {
                alert(resultado.mensaje);
            }
        } finally {
            btn.disabled = false;
        }
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!selectConcepto.value) {
            alert('Por favor, seleccione un concepto válido.');
            return;
        }

        const cantidad = Number(inputCantidad.value);
        const precio = Number(inputPrecio.value);

        const datos = {
            id_concepto: selectConcepto.value,
            tipo: 'egreso',
            cantidad: cantidad,
            precio_unitario: precio,
            monto_total: Number((cantidad * precio).toFixed(2)),
            fuente: document.getElementById('fuente').value,
            forma_pago: selectFormaPago.value,
            id_banco: selectBanco.value || null,
            id_proveedor: selectProveedor.value || null,
            numero_factura: inputNumeroFactura.value.trim() || null,
            fuente_referencia: inputFuenteReferencia.value.trim() || null
        };

        try {
            const respuesta = await fetch('insertar_movimiento.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });
            const resultado = await respuesta.json();

            if (resultado.exito) {
                form.reset();
                inputCantidad.value = '1';
                displayTotal.textContent = '$0.00';
                if (displayTotalBs) displayTotalBs.textContent = 'Bs. 0,00';
                mostrarAlerta('¡Egreso registrado con éxito!');
            } else {
                alert('Error: ' + resultado.mensaje);
            }
        } catch (error) {
            console.error(error);
            alert('Error de comunicación con el servidor.');
        }
    });

    async function initCatalogos() {
        await cargarConceptos(selectConcepto);
        await cargarFormasPago(selectFormaPago);
        await cargarBancos(selectBanco);
        await cargarProveedores(selectProveedor);
        calcularTotal();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCatalogos);
    } else {
        initCatalogos();
    }
}
