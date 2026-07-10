function formatearUSD(valor) {
    return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(valor);
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

    return `
        <tr>
            <td>${f}</td>
            <td>${m.concepto}</td>
            ${tipoCol}
            <td>${m.cantidad}</td>
            <td>${formatearUSD(precio)}</td>
            <td style="font-weight:bold;">${formatearUSD(monto)}</td>
            <td>${m.fuente}</td>
            <td>${m.forma_pago || '-'}</td>
            <td>${clienteOProveedor}</td>
            <td>${m.numero_factura || '-'}</td>
            <td><button class="delete-btn" onclick="eliminarMovimiento(${m.id}, window.recargarTabla)">Eliminar</button></td>
        </tr>`;
}
