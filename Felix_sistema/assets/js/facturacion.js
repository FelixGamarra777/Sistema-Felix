function initFacturacion() {
    const inputNumero = document.getElementById('numero-factura');
    const selectCliente = document.getElementById('cliente');
    const inputBuscador = document.getElementById('buscador-pos');
    const contSugerencias = document.getElementById('sugerencias-pos');
    const inputCantidad = document.getElementById('cantidad-pos');
    const cuerpoCarrito = document.getElementById('cuerpo-carrito');
    const displayTotalUsd = document.getElementById('total-usd');
    const displayTotalBs = document.getElementById('total-bs');
    const displayRestante = document.getElementById('restante-usd');
    const boxRestante = document.getElementById('box-restante');
    const contPagos = document.getElementById('lineas-pago');
    const contChips = document.getElementById('pos-chips');
    const contCatalogo = document.getElementById('pos-catalogo');
    const numpadDestino = document.getElementById('numpad-destino');
    const vueltoBox = document.getElementById('vuelto-box');

    let catalogo = [];
    let formasPago = [];
    let bancos = [];
    let carrito = [];   // { id_concepto, nombre, categoria, stock, cantidad, precio_unitario }
    let pagos = [];     // { forma_pago, moneda, monto, id_banco, referencia }
    let grupoActivo = '__todos__';

    const tasa = () => window.TASA_BCV.tasa || 0;

    // ---------- Carga inicial ----------
    async function cargarCatalogo() {
        const respuesta = await fetch('obtener_conceptos.php');
        const resultado = await respuesta.json();
        if (resultado.exito) {
            catalogo = resultado.datos;
            renderPanelTactil();
        }
    }

    // ---------- Panel táctil (grid de categorías y productos) ----------
    function grupoDe(p) {
        if (p.grupo && p.grupo.trim() !== '') return p.grupo.trim();
        return p.categoria === 'servicio' ? 'Servicios' : 'Otros';
    }

    function catalogoVendible() {
        return catalogo.filter(p => p.nombre !== 'Sin especificar');
    }

    function renderPanelTactil() {
        const grupos = [...new Set(catalogoVendible().map(grupoDe))].sort((a, b) => a.localeCompare(b, 'es'));
        if (grupoActivo !== '__todos__' && !grupos.includes(grupoActivo)) grupoActivo = '__todos__';

        contChips.innerHTML = '';
        const crearChip = (valor, texto) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'chip-filtro' + (grupoActivo === valor ? ' activo' : '');
            btn.textContent = texto;
            btn.onclick = () => { grupoActivo = valor; renderPanelTactil(); };
            contChips.appendChild(btn);
        };
        crearChip('__todos__', 'Todos');
        grupos.forEach(g => crearChip(g, g));

        const visibles = catalogoVendible().filter(p => grupoActivo === '__todos__' || grupoDe(p) === grupoActivo);
        contCatalogo.innerHTML = '';
        if (visibles.length === 0) {
            contCatalogo.innerHTML = '<p style="padding:1rem; color:#6c757d;">No hay productos en esta categoría. Agréguelos en el módulo Productos.</p>';
            return;
        }
        visibles.forEach(p => {
            const esProducto = p.categoria === 'producto';
            const precio = p.precio_unitario !== null ? formatearUSD(parseFloat(p.precio_unitario)) : 'sin precio';
            const stockTxt = esProducto
                ? `<span class="tile-stock ${parseInt(p.stock) <= 0 ? 'stock-bajo' : ''}">stock: ${p.stock}</span>`
                : '<span class="tile-stock">servicio</span>';
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pos-tile';
            btn.innerHTML = `<span class="tile-nombre">${p.nombre}</span><span class="tile-precio">${precio}</span>${stockTxt}`;
            btn.onclick = () => agregarAlCarrito(p);
            contCatalogo.appendChild(btn);
        });
    }

    async function cargarCorrelativo() {
        try {
            const respuesta = await fetch('obtener_correlativo.php');
            const resultado = await respuesta.json();
            if (resultado.exito) inputNumero.value = resultado.numero_factura;
        } catch (e) { console.error(e); }
    }

    async function cargarCatalogosAuxiliares() {
        await cargarClientes(selectCliente);
        selectCliente.querySelector('option').textContent = 'Consumidor final';

        const rFormas = await (await fetch('obtener_formas_pago.php')).json();
        if (rFormas.exito) formasPago = rFormas.datos;

        const rBancos = await (await fetch('obtener_bancos.php')).json();
        if (rBancos.exito) bancos = rBancos.datos;
    }

    // ---------- Buscador predictivo ----------
    function renderSugerencias() {
        const q = inputBuscador.value.trim().toLowerCase();
        if (!q) { contSugerencias.classList.remove('visible'); return; }

        const coincidencias = catalogo
            .filter(p => p.nombre.toLowerCase().includes(q) && p.nombre !== 'Sin especificar')
            .slice(0, 8);

        if (coincidencias.length === 0) {
            contSugerencias.innerHTML = '<div class="sugerencia-item">Sin resultados. Agréguelo en el módulo Productos.</div>';
            contSugerencias.classList.add('visible');
            return;
        }

        contSugerencias.innerHTML = '';
        coincidencias.forEach(p => {
            const precio = p.precio_unitario !== null ? formatearUSD(parseFloat(p.precio_unitario)) : 'sin precio';
            const esProducto = p.categoria === 'producto';
            const stockTxt = esProducto
                ? `<span class="sug-stock ${parseInt(p.stock) <= 0 ? 'stock-bajo' : ''}">stock: ${p.stock}</span>`
                : '<span class="sug-stock">servicio</span>';
            const div = document.createElement('div');
            div.className = 'sugerencia-item';
            div.innerHTML = `<span>${p.nombre}</span> <span>${stockTxt} <span class="sug-precio">${precio}</span></span>`;
            div.onclick = () => agregarAlCarrito(p, true);
            contSugerencias.appendChild(div);
        });
        contSugerencias.classList.add('visible');
    }

    inputBuscador.addEventListener('input', renderSugerencias);
    inputBuscador.addEventListener('focus', renderSugerencias);
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.buscador-wrapper')) contSugerencias.classList.remove('visible');
    });

    // ---------- Carrito ----------
    function agregarAlCarrito(producto, desdeBuscador = false) {
        const cantidad = parseInt(inputCantidad.value) || 1;
        const esProducto = producto.categoria === 'producto';

        if (esProducto && producto.stock !== null) {
            const enCarrito = carrito.find(i => i.id_concepto === producto.id_concepto);
            const totalPedido = cantidad + (enCarrito ? enCarrito.cantidad : 0);
            if (parseInt(producto.stock) - totalPedido < 0) {
                if (!confirm(`⚠️ "${producto.nombre}" solo tiene ${producto.stock} unidades en inventario y está vendiendo ${totalPedido}.\n\nEl stock quedará NEGATIVO. ¿Continuar de todas formas?`)) {
                    return;
                }
            }
        }

        const existente = carrito.find(i => i.id_concepto === producto.id_concepto);
        if (existente) {
            existente.cantidad += cantidad;
        } else {
            carrito.push({
                id_concepto: producto.id_concepto,
                nombre: producto.nombre,
                categoria: producto.categoria,
                stock: producto.stock,
                cantidad: cantidad,
                precio_unitario: producto.precio_unitario !== null ? parseFloat(producto.precio_unitario) : 0
            });
        }

        inputBuscador.value = '';
        inputCantidad.value = '1';
        contSugerencias.classList.remove('visible');
        ocultarVuelto();
        renderCarrito();
        if (desdeBuscador) inputBuscador.focus();
    }

    function renderCarrito() {
        if (carrito.length === 0) {
            cuerpoCarrito.innerHTML = '<tr><td colspan="6" style="text-align:center;">El carrito está vacío.</td></tr>';
        } else {
            cuerpoCarrito.innerHTML = '';
            carrito.forEach((item, idx) => {
                const subtotal = item.cantidad * item.precio_unitario;
                const subtotalBs = tasa() > 0 ? formatearBs(subtotal * tasa()) : '—';
                cuerpoCarrito.innerHTML += `
                    <tr>
                        <td>${item.nombre} <span class="badge-${item.categoria}">${item.categoria === 'producto' ? 'P' : 'S'}</span></td>
                        <td><input type="number" min="1" value="${item.cantidad}" style="width:80px;" onchange="actualizarItemPos(${idx}, 'cantidad', this.value)"></td>
                        <td><input type="number" min="0" step="0.01" value="${item.precio_unitario}" style="width:100px;" onchange="actualizarItemPos(${idx}, 'precio', this.value)"></td>
                        <td style="font-weight:bold;">${formatearUSD(subtotal)}</td>
                        <td>${subtotalBs}</td>
                        <td><button class="delete-btn" onclick="quitarItemPos(${idx})">✖</button></td>
                    </tr>`;
            });
        }
        renderTotales();
    }

    window.actualizarItemPos = (idx, campo, valor) => {
        const item = carrito[idx];
        if (!item) return;
        if (campo === 'cantidad') item.cantidad = Math.max(1, parseInt(valor) || 1);
        if (campo === 'precio') item.precio_unitario = Math.max(0, parseFloat(valor) || 0);
        ocultarVuelto();
        renderCarrito();
    };

    window.quitarItemPos = (idx) => {
        carrito.splice(idx, 1);
        ocultarVuelto();
        renderCarrito();
    };

    function totalCarritoUsd() {
        return carrito.reduce((acc, i) => acc + i.cantidad * i.precio_unitario, 0);
    }

    function renderTotales() {
        const total = totalCarritoUsd();
        displayTotalUsd.textContent = formatearUSD(total);
        displayTotalBs.textContent = tasa() > 0 ? formatearBs(total * tasa()) : 'Sin tasa BCV';
        renderRestante();
    }

    // ---------- Pagos mixtos ----------
    function pagadoUsd() {
        return pagos.reduce((acc, p) => {
            const monto = parseFloat(p.monto) || 0;
            return acc + (p.moneda === 'USD' ? monto : (tasa() > 0 ? monto / tasa() : 0));
        }, 0);
    }

    function renderRestante() {
        const restante = totalCarritoUsd() - pagadoUsd();
        displayRestante.textContent = formatearUSD(Math.abs(restante) < 0.005 ? 0 : restante);
        boxRestante.style.backgroundColor = Math.abs(restante) <= 0.10 ? '#d4edda' : '#f8d7da';
        boxRestante.style.borderColor = Math.abs(restante) <= 0.10 ? '#c3e6cb' : '#f5c6cb';
        boxRestante.style.color = Math.abs(restante) <= 0.10 ? '#155724' : '#721c24';
    }

    function agregarLineaPago(montoInicial = null) {
        const restante = totalCarritoUsd() - pagadoUsd();
        pagos.push({
            forma_pago: formasPago[0] || 'Efectivo',
            moneda: 'USD',
            monto: montoInicial !== null ? montoInicial : Math.max(0, Number(restante.toFixed(2))),
            id_banco: '',
            referencia: ''
        });
        renderPagos();
    }

    function renderPagos() {
        contPagos.innerHTML = '';
        pagos.forEach((pago, idx) => {
            const optsFormas = formasPago.map(f => `<option value="${f}" ${pago.forma_pago === f ? 'selected' : ''}>${f}</option>`).join('');
            const optsBancos = ['<option value="">Banco (opcional)</option>']
                .concat(bancos.map(b => `<option value="${b.id_banco}" ${String(pago.id_banco) === String(b.id_banco) ? 'selected' : ''}>${b.nombre_banco}</option>`))
                .join('');
            const div = document.createElement('div');
            div.className = 'pago-linea';
            div.innerHTML = `
                <select onchange="actualizarPagoPos(${idx}, 'forma_pago', this.value)">${optsFormas}</select>
                <select onchange="actualizarPagoPos(${idx}, 'moneda', this.value)">
                    <option value="USD" ${pago.moneda === 'USD' ? 'selected' : ''}>USD ($)</option>
                    <option value="BS" ${pago.moneda === 'BS' ? 'selected' : ''}>Bolívares (Bs.)</option>
                </select>
                <input type="number" min="0" step="0.01" placeholder="Monto" value="${pago.monto}" class="pago-monto" data-idx="${idx}" onfocus="apuntarNumpadAPago(this)" onchange="actualizarPagoPos(${idx}, 'monto', this.value)">
                <select onchange="actualizarPagoPos(${idx}, 'id_banco', this.value)">${optsBancos}</select>
                <input type="text" placeholder="Referencia (opcional)" value="${pago.referencia}" onchange="actualizarPagoPos(${idx}, 'referencia', this.value)">
                <button class="delete-btn" onclick="quitarPagoPos(${idx})">✖</button>`;
            contPagos.appendChild(div);
        });
        renderRestante();
    }

    window.actualizarPagoPos = (idx, campo, valor) => {
        const pago = pagos[idx];
        if (!pago) return;
        if (campo === 'moneda' && valor !== pago.moneda) {
            // Convertir el monto al cambiar de moneda para mantener el equivalente
            const monto = parseFloat(pago.monto) || 0;
            if (tasa() > 0 && monto > 0) {
                pago.monto = valor === 'BS' ? Number((monto * tasa()).toFixed(2)) : Number((monto / tasa()).toFixed(2));
            }
        }
        pago[campo] = campo === 'monto' ? (parseFloat(valor) || 0) : valor;
        if (campo === 'monto') ocultarVuelto();
        if (campo !== 'monto') renderPagos(); else renderRestante();
    };

    window.quitarPagoPos = (idx) => {
        pagos.splice(idx, 1);
        ocultarVuelto();
        renderPagos();
    };

    document.getElementById('btn-agregar-pago').addEventListener('click', () => agregarLineaPago());

    // ---------- Teclado numérico en pantalla ----------
    // Se usa un buffer de texto porque los inputs type="number" descartan
    // valores intermedios como "5." mientras se teclea el punto decimal.
    let numpadTarget = inputCantidad;
    let numpadBuffer = '1';

    function fijarNumpadEn(input, etiqueta) {
        document.querySelectorAll('.numpad-activo').forEach(el => el.classList.remove('numpad-activo'));
        numpadTarget = input;
        numpadBuffer = String(input.value || '');
        input.classList.add('numpad-activo');
        numpadDestino.innerHTML = `Escribiendo en: <strong>${etiqueta}</strong>`;
    }

    window.apuntarNumpadAPago = (input) => {
        fijarNumpadEn(input, `Monto del pago #${parseInt(input.dataset.idx) + 1}`);
    };

    // Mantener el buffer al día si se teclea con el teclado físico
    document.addEventListener('input', (e) => {
        if (e.target === numpadTarget) numpadBuffer = String(e.target.value);
    });

    function aplicarValorNumpad() {
        numpadTarget.value = numpadBuffer.endsWith('.') ? numpadBuffer.slice(0, -1) : numpadBuffer;
        if (numpadTarget === inputCantidad) return;
        const idx = parseInt(numpadTarget.dataset.idx);
        if (pagos[idx]) {
            pagos[idx].monto = parseFloat(numpadBuffer) || 0;
            ocultarVuelto();
            renderRestante();
        }
    }

    document.getElementById('numpad').addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-np]');
        if (!btn) return;
        // Si la línea de pago que se estaba editando ya no existe, volver a Cantidad
        if (!numpadTarget.isConnected) fijarNumpadEn(inputCantidad, 'Cantidad');

        const tecla = btn.dataset.np;
        const esCantidad = numpadTarget === inputCantidad;

        if (tecla === 'listo') {
            aplicarValorNumpad();
            fijarNumpadEn(inputCantidad, 'Cantidad');
            return;
        }
        if (tecla === 'limpiar') {
            numpadBuffer = esCantidad ? '1' : '0';
        } else if (tecla === 'borrar') {
            numpadBuffer = numpadBuffer.slice(0, -1);
        } else if (tecla === '.') {
            if (esCantidad || numpadBuffer.includes('.')) return;
            numpadBuffer = (numpadBuffer || '0') + '.';
        } else {
            // Reemplazar los valores por defecto (1 en cantidad, 0 en montos) al empezar a teclear
            numpadBuffer = ((esCantidad && numpadBuffer === '1') || numpadBuffer === '0' ? '' : numpadBuffer) + tecla;
        }
        aplicarValorNumpad();
    });

    // ---------- Billetes rápidos y vuelto ----------
    function ocultarVuelto() {
        vueltoBox.style.display = 'none';
    }

    function mostrarVuelto(html, clase) {
        vueltoBox.className = 'vuelto-box ' + clase;
        vueltoBox.innerHTML = html;
        vueltoBox.style.display = 'block';
    }

    function lineaPagoDisponible(forma, moneda) {
        // Reutiliza una línea sin monto; si no hay, crea una nueva
        let idx = pagos.findIndex(p => !(parseFloat(p.monto) > 0));
        if (idx === -1) {
            pagos.push({ forma_pago: forma, moneda: moneda, monto: 0, id_banco: '', referencia: '' });
            idx = pagos.length - 1;
        }
        pagos[idx].forma_pago = forma;
        pagos[idx].moneda = moneda;
        return pagos[idx];
    }

    function recibirBillete(valor) {
        const restante = totalCarritoUsd() - pagadoUsd();
        if (totalCarritoUsd() <= 0) { alert('El carrito está vacío.'); return; }
        if (restante <= 0.005) {
            mostrarVuelto('La factura ya está pagada por completo.', 'ok');
            return;
        }
        const formaUsd = formasPago.includes('Dolares') ? 'Dolares' : (formasPago[0] || 'Efectivo');
        const pago = lineaPagoDisponible(formaUsd, 'USD');

        if (valor >= restante - 0.005) {
            // El billete cubre lo que falta: se registra el neto y se informa el vuelto
            pago.monto = Number(restante.toFixed(2));
            const vuelto = valor - restante;
            const vueltoBs = tasa() > 0 ? ` (${formatearBs(vuelto * tasa())})` : '';
            renderPagos();
            mostrarVuelto(`Recibido: ${formatearUSD(valor)} &nbsp;→&nbsp; Vuelto: <span style="font-size:1.3em;">${formatearUSD(vuelto)}</span>${vueltoBs}`, 'ok');
        } else {
            // Pago parcial: se abona el billete completo y se indica lo que falta
            pago.monto = valor;
            renderPagos();
            mostrarVuelto(`Recibido: ${formatearUSD(valor)} &nbsp;·&nbsp; Falta: ${formatearUSD(restante - valor)}`, 'falta');
        }
    }

    function montoExacto(moneda) {
        const restante = totalCarritoUsd() - pagadoUsd();
        if (totalCarritoUsd() <= 0) { alert('El carrito está vacío.'); return; }
        if (restante <= 0.005) {
            mostrarVuelto('La factura ya está pagada por completo.', 'ok');
            return;
        }
        if (moneda === 'BS' && tasa() <= 0) { alert('No hay tasa BCV disponible para calcular el monto en Bolívares.'); return; }

        const forma = moneda === 'USD'
            ? (formasPago.includes('Dolares') ? 'Dolares' : (formasPago[0] || 'Efectivo'))
            : (formasPago.includes('Efectivo') ? 'Efectivo' : (formasPago[0] || 'Efectivo'));
        const pago = lineaPagoDisponible(forma, moneda);
        pago.monto = moneda === 'USD'
            ? Number(restante.toFixed(2))
            : Number((restante * tasa()).toFixed(2));
        ocultarVuelto();
        renderPagos();
    }

    document.querySelectorAll('.fastcash[data-billete]').forEach(btn => {
        btn.addEventListener('click', () => recibirBillete(parseFloat(btn.dataset.billete)));
    });
    document.querySelectorAll('.fastcash[data-exacto]').forEach(btn => {
        btn.addEventListener('click', () => montoExacto(btn.dataset.exacto));
    });

    // ---------- Acciones ----------
    document.getElementById('btn-vaciar').addEventListener('click', () => {
        if (carrito.length === 0 && pagos.length === 0) return;
        if (!confirm('¿Vaciar el carrito y los pagos?')) return;
        carrito = [];
        pagos = [];
        ocultarVuelto();
        renderCarrito();
        renderPagos();
    });

    async function guardarFactura(imprimir) {
        if (carrito.length === 0) { alert('El carrito está vacío.'); return; }
        if (pagos.length === 0) {
            // Facilidad: si no registró pagos, se crea uno automático por el total en USD
            agregarLineaPago();
        }
        const restante = totalCarritoUsd() - pagadoUsd();
        if (Math.abs(restante) > 0.10) {
            alert(`Los pagos no cuadran con el total. Restante: ${formatearUSD(restante)}.\nAjuste los montos de pago.`);
            return;
        }

        const datos = {
            numero_factura: inputNumero.value.trim(),
            id_cliente: selectCliente.value || null,
            items: carrito.map(i => ({
                id_concepto: i.id_concepto,
                cantidad: i.cantidad,
                precio_unitario: i.precio_unitario
            })),
            pagos: pagos.map(p => ({
                forma_pago: p.forma_pago,
                moneda: p.moneda,
                monto: p.monto,
                id_banco: p.id_banco || null,
                referencia: p.referencia || null
            }))
        };

        const btnG = document.getElementById('btn-guardar');
        const btnGI = document.getElementById('btn-guardar-imprimir');
        try {
            btnG.disabled = true; btnGI.disabled = true;
            const respuesta = await fetch('guardar_factura.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });
            const resultado = await respuesta.json();

            if (resultado.exito) {
                if (resultado.advertencias && resultado.advertencias.length > 0) {
                    alert('Factura guardada.\n\n' + resultado.advertencias.join('\n'));
                }
                mostrarAlerta(`✅ Factura ${resultado.numero_factura} guardada: ${formatearUSD(resultado.total_usd)} / ${formatearBs(resultado.total_bs)}`, 5000);
                if (imprimir) {
                    window.open('factura_ticket.php?id=' + resultado.id_factura, '_blank');
                }
                carrito = [];
                pagos = [];
                ocultarVuelto();
                renderCarrito();
                renderPagos();
                await cargarCatalogo();      // refresca stocks y panel táctil
                await cargarCorrelativo();   // siguiente número
            } else {
                alert('Error: ' + resultado.mensaje);
            }
        } catch (error) {
            console.error(error);
            alert('Error de comunicación con el servidor.');
        } finally {
            btnG.disabled = false; btnGI.disabled = false;
        }
    }

    document.getElementById('btn-guardar').addEventListener('click', () => guardarFactura(false));
    document.getElementById('btn-guardar-imprimir').addEventListener('click', () => guardarFactura(true));

    // ---------- Modal cliente (reutiliza el del footer) ----------
    const modalCliente = initModal('modal-cliente', 'btn-open-modal-cliente', 'close-modal-cliente', 'nuevo-cliente-nombre');
    document.getElementById('btn-guardar-cliente').onclick = async () => {
        const nombre = document.getElementById('nuevo-cliente-nombre').value.trim();
        const rif = document.getElementById('nuevo-cliente-rif').value.trim();
        if (!nombre || !rif) { alert('Nombre y Cédula/RIF son obligatorios.'); return; }
        const respuesta = await fetch('insertar_cliente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombre_empresa: nombre,
                cedula_rif: rif,
                tipo_persona: document.getElementById('nuevo-cliente-tipo').value
            })
        });
        const resultado = await respuesta.json();
        if (resultado.exito) {
            await cargarClientes(selectCliente);
            selectCliente.querySelector('option').textContent = 'Consumidor final';
            selectCliente.value = resultado.id_cliente;
            document.getElementById('nuevo-cliente-nombre').value = '';
            document.getElementById('nuevo-cliente-rif').value = '';
            modalCliente.close();
        } else {
            alert(resultado.mensaje);
        }
    };

    // ---------- Reactividad con la tasa ----------
    document.addEventListener('tasa-bcv-lista', () => { renderCarrito(); renderPagos(); });

    // ---------- Arranque ----------
    (async () => {
        await Promise.all([cargarCatalogo(), cargarCorrelativo(), cargarCatalogosAuxiliares()]);
        agregarLineaPago(0);
        renderCarrito();
    })();
}
