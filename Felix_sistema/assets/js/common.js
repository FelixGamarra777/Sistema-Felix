function formatearUSD(valor) {
    return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(valor);
}

function formatearBs(valor) {
    return "Bs. " + new Intl.NumberFormat("es-VE", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(valor);
}

// --- Estado global: tasa BCV del día (disponible en todas las páginas) ---
window.TASA_BCV = { tasa: 0, fecha: "", origen: "" };

async function cargarTasaBCV(refrescar = false) {
    try {
        const respuesta = await fetch("obtener_tasa_bcv.php" + (refrescar ? "?refrescar=1" : ""));
        const resultado = await respuesta.json();
        if (resultado.exito) {
            window.TASA_BCV = resultado;
            actualizarWidgetTasa();
            document.dispatchEvent(new CustomEvent("tasa-bcv-lista", { detail: resultado }));
        }
    } catch (error) {
        console.error("Error cargando tasa BCV:", error);
    }
    return window.TASA_BCV;
}

function actualizarWidgetTasa() {
    const el = document.getElementById("tasa-bcv-valor");
    if (!el) return;
    const info = window.TASA_BCV;
    if (info.tasa > 0) {
        const detalle = info.origen === "manual" ? " (manual)" : (info.origen === "api" ? "" : " (última conocida)");
        el.textContent = `${formatearBs(info.tasa)} / $${detalle}`;
    } else {
        el.textContent = "No disponible — fíjala con ✏️";
    }
}

function initTasaBCV() {
    const modalTasa = initModal("modal-tasa", "btn-editar-tasa", "close-modal-tasa", "nueva-tasa");
    const btnGuardar = document.getElementById("btn-guardar-tasa");
    if (btnGuardar) {
        btnGuardar.onclick = async () => {
            const input = document.getElementById("nueva-tasa");
            const tasa = parseFloat(input.value);
            if (!tasa || tasa <= 0) { alert("Ingrese una tasa válida mayor a 0."); return; }
            try {
                btnGuardar.disabled = true;
                const respuesta = await fetch("guardar_tasa_bcv.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ tasa })
                });
                const resultado = await respuesta.json();
                if (resultado.exito) {
                    input.value = "";
                    modalTasa.close();
                    await cargarTasaBCV();
                } else {
                    alert(resultado.mensaje);
                }
            } finally {
                btnGuardar.disabled = false;
            }
        };
    }
    cargarTasaBCV();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initTasaBCV);
} else {
    initTasaBCV();
}

function mostrarAlerta(mensaje, duracion = 3000) {
    const alertBox = document.getElementById("alert-message");
    if (!alertBox) return;
    alertBox.textContent = mensaje;
    alertBox.className = "alert success";
    alertBox.style.display = "block";
    setTimeout(() => { alertBox.style.display = "none"; }, duracion);
}

async function cargarConceptos(selectElement) {
    if (!selectElement) return;
    try {
        const respuesta = await fetch('obtener_conceptos.php');
        const resultado = await respuesta.json();
        if (resultado.exito) {
            selectElement.innerHTML = '<option value="" disabled selected>Seleccione un concepto...</option>';
            resultado.datos.forEach(c => {
                const opt = document.createElement("option");
                opt.value = c.id_concepto;
                opt.textContent = c.nombre;
                if (c.precio_unitario !== null) opt.dataset.precio = c.precio_unitario;
                opt.dataset.categoria = c.categoria;
                if (c.stock !== null) opt.dataset.stock = c.stock;
                selectElement.appendChild(opt);
            });
        }
    } catch (error) {
        console.error("Error cargando conceptos:", error);
    }
}

async function cargarFormasPago(selectElement) {
    if (!selectElement) return;
    try {
        const respuesta = await fetch('obtener_formas_pago.php');
        const resultado = await respuesta.json();
        if (resultado.exito) {
            selectElement.innerHTML = '<option value="" disabled selected>Seleccione...</option>';
            resultado.datos.forEach(valor => {
                const opt = document.createElement("option");
                opt.value = valor;
                opt.textContent = valor;
                selectElement.appendChild(opt);
            });
        }
    } catch (error) {
        console.error("Error cargando formas de pago:", error);
    }
}

async function cargarBancos(selectElement) {
    if (!selectElement) return;
    try {
        const respuesta = await fetch('obtener_bancos.php');
        const resultado = await respuesta.json();
        if (resultado.exito) {
            selectElement.innerHTML = '<option value="">Sin especificar</option>';
            resultado.datos.forEach(b => {
                const opt = document.createElement("option");
                opt.value = b.id_banco;
                opt.textContent = b.nombre_banco;
                selectElement.appendChild(opt);
            });
        }
    } catch (error) {
        console.error("Error cargando bancos:", error);
    }
}

async function cargarClientes(selectElement) {
    if (!selectElement) return;
    try {
        const respuesta = await fetch('obtener_clientes.php');
        const resultado = await respuesta.json();
        if (resultado.exito) {
            selectElement.innerHTML = '<option value="">Sin especificar</option>';
            resultado.datos.forEach(c => {
                const opt = document.createElement("option");
                opt.value = c.id_cliente;
                opt.textContent = `${c.nombre_empresa} (${c.cedula_rif})`;
                selectElement.appendChild(opt);
            });
        }
    } catch (error) {
        console.error("Error cargando clientes:", error);
    }
}

async function cargarProveedores(selectElement) {
    if (!selectElement) return;
    try {
        const respuesta = await fetch('obtener_proveedores.php');
        const resultado = await respuesta.json();
        if (resultado.exito) {
            selectElement.innerHTML = '<option value="">Sin especificar</option>';
            resultado.datos.forEach(p => {
                const opt = document.createElement("option");
                opt.value = p.id_proveedor;
                opt.textContent = `${p.nombre_empresa} (${p.cedula_rif})`;
                selectElement.appendChild(opt);
            });
        }
    } catch (error) {
        console.error("Error cargando proveedores:", error);
    }
}

function initModal(modalId, openBtnId, closeBtnId, focusInputId) {
    const modal = document.getElementById(modalId);
    const openBtn = document.getElementById(openBtnId);
    const closeBtn = document.getElementById(closeBtnId);
    const focusInput = focusInputId ? document.getElementById(focusInputId) : null;

    if (!modal || !openBtn || !closeBtn) return { modal, close: () => modal.classList.remove("show") };

    openBtn.onclick = () => { modal.classList.add("show"); if (focusInput) focusInput.focus(); };
    closeBtn.onclick = () => modal.classList.remove("show");
    window.addEventListener("click", (e) => { if (e.target === modal) modal.classList.remove("show"); });

    return { modal, close: () => modal.classList.remove("show") };
}

async function eliminarMovimiento(id, callbackRecarga) {
    if (!confirm("¿Eliminar este registro?")) return;
    try {
        const respuesta = await fetch('eliminar_movimiento.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const resultado = await respuesta.json();
        if (resultado.exito && callbackRecarga) {
            callbackRecarga();
        } else if (!resultado.exito) {
            alert(resultado.mensaje || "Error al eliminar.");
        }
    } catch (error) {
        console.error(error);
    }
}

function construirUrlMovimientos(fechaDesde, fechaHasta, tipo) {
    const params = new URLSearchParams();
    if (fechaDesde && fechaHasta) {
        params.set('desde', fechaDesde);
        params.set('hasta', fechaHasta);
    }
    if (tipo) params.set('tipo', tipo);
    const qs = params.toString();
    return qs ? `obtener_movimientos.php?${qs}` : 'obtener_movimientos.php';
}

function renderizarFilaMovimiento(m, mostrarTipo = true) {
    const precio = parseFloat(m.precio);
    const monto = parseFloat(m.monto);
    const f = new Date(m.fecha).toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' });
    const clienteOProveedor = m.cliente || m.proveedor || '-';
    const tipoCol = mostrarTipo
        ? `<td><span class="badge-${m.tipo}">${m.tipo.toUpperCase()}</span></td>`
        : '';

    const montoBs = m.monto_bs !== null && m.monto_bs !== undefined
        ? formatearBs(parseFloat(m.monto_bs))
        : '-';

    return `
        <tr>
            <td style="font-weight:bold;">${m.numero_factura || '-'}</td>
            <td>${f}</td>
            <td>${m.concepto}</td>
            ${tipoCol}
            <td>${m.cantidad}</td>
            <td>${formatearUSD(precio)}</td>
            <td style="font-weight:bold;">${formatearUSD(monto)}</td>
            <td>${montoBs}</td>
            <td>${m.fuente}</td>
            <td>${m.forma_pago || '-'}</td>
            <td>${clienteOProveedor}</td>
            <td><button class="delete-btn" onclick="eliminarMovimiento(${m.id}, window.recargarTabla)">Eliminar</button></td>
        </tr>`;
}
