function initProductos() {
    const cuerpoTabla = document.getElementById('cuerpo-tabla');
    const inputBuscar = document.getElementById('buscar-producto');
    const filtroCategoria = document.getElementById('filtro-categoria');
    const modalProducto = initModal('modal-producto', 'btn-nuevo-producto', 'close-modal-producto', 'prod-nombre');
    const btnNuevo = document.getElementById('btn-nuevo-producto');
    const btnGuardar = document.getElementById('btn-guardar-producto');
    const inputId = document.getElementById('prod-id');
    const inputNombre = document.getElementById('prod-nombre');
    const selectCategoria = document.getElementById('prod-categoria');
    const inputGrupo = document.getElementById('prod-grupo');
    const listaGrupos = document.getElementById('lista-grupos');
    const inputPrecio = document.getElementById('prod-precio');
    const inputStock = document.getElementById('prod-stock');
    const inputFactor = document.getElementById('prod-factor');
    const grupoStock = document.getElementById('grupo-stock');
    const grupoFactor = document.getElementById('grupo-factor');
    const tituloModal = document.getElementById('modal-producto-titulo');

    let catalogo = [];

    // El stock y el factor de conversión mayorista solo aplican a productos.
    function alternarCamposInventario() {
        const esProducto = selectCategoria.value === 'producto';
        grupoStock.style.display = esProducto ? '' : 'none';
        grupoFactor.style.display = esProducto ? '' : 'none';
    }
    selectCategoria.addEventListener('change', alternarCamposInventario);

    btnNuevo.addEventListener('click', () => {
        tituloModal.textContent = 'Nuevo Producto / Servicio';
        inputId.value = '';
        inputNombre.value = '';
        selectCategoria.value = 'producto';
        inputGrupo.value = '';
        inputPrecio.value = '';
        inputStock.value = '0';
        inputFactor.value = '1';
        alternarCamposInventario();
    });

    async function cargarCatalogo() {
        try {
            const respuesta = await fetch('obtener_conceptos.php');
            const resultado = await respuesta.json();
            if (resultado.exito) {
                catalogo = resultado.datos;
                renderizarGrupos();
                renderizar();
            }
        } catch (error) {
            console.error(error);
            cuerpoTabla.innerHTML = '<tr><td colspan="8" style="text-align:center; color:red;">Error de comunicación con el servidor.</td></tr>';
        }
    }

    function renderizarGrupos() {
        const grupos = [...new Set(catalogo.map(p => p.grupo).filter(g => g))].sort();
        listaGrupos.innerHTML = grupos.map(g => `<option value="${g}">`).join('');
    }

    function renderizar() {
        const q = inputBuscar.value.trim().toLowerCase();
        const cat = filtroCategoria.value;
        const tasa = window.TASA_BCV.tasa || 0;

        const filtrados = catalogo.filter(p =>
            (!q || p.nombre.toLowerCase().includes(q)) &&
            (!cat || p.categoria === cat)
        );

        if (filtrados.length === 0) {
            cuerpoTabla.innerHTML = '<tr><td colspan="8" style="text-align:center;">No hay productos/servicios que coincidan.</td></tr>';
            return;
        }

        cuerpoTabla.innerHTML = '';
        filtrados.forEach(p => {
            const precio = p.precio_unitario !== null ? parseFloat(p.precio_unitario) : null;
            const precioUsd = precio !== null ? formatearUSD(precio) : '—';
            const precioBs = (precio !== null && tasa > 0) ? formatearBs(precio * tasa) : '—';
            const esProducto = p.categoria === 'producto';
            const stock = esProducto
                ? `<span class="${parseInt(p.stock) <= 0 ? 'stock-bajo' : ''}">${p.stock}</span>`
                : 'N/A';
            const factor = parseFloat(p.factor_mayor) || 1;
            const factorTxt = (esProducto && factor > 1) ? `1 pres. = ${factor} u` : (esProducto ? '1 : 1' : 'N/A');

            cuerpoTabla.innerHTML += `
                <tr>
                    <td>${p.nombre}</td>
                    <td><span class="badge-${p.categoria}">${esProducto ? 'PRODUCTO' : 'SERVICIO'}</span></td>
                    <td>${p.grupo || '—'}</td>
                    <td>${precioUsd}</td>
                    <td>${precioBs}</td>
                    <td>${stock}</td>
                    <td>${factorTxt}</td>
                    <td>
                        <button class="submit-btn btn-info" style="width:auto; padding: 6px 12px;" onclick="editarProducto(${p.id_concepto})">Editar</button>
                        <button class="delete-btn" onclick="eliminarProducto(${p.id_concepto})">Eliminar</button>
                    </td>
                </tr>`;
        });
    }

    window.editarProducto = (id) => {
        const p = catalogo.find(x => parseInt(x.id_concepto) === id);
        if (!p) return;
        tituloModal.textContent = 'Editar: ' + p.nombre;
        inputId.value = p.id_concepto;
        inputNombre.value = p.nombre;
        selectCategoria.value = p.categoria;
        inputGrupo.value = p.grupo || '';
        inputPrecio.value = p.precio_unitario !== null ? p.precio_unitario : '';
        inputStock.value = p.stock !== null ? p.stock : '0';
        inputFactor.value = p.factor_mayor !== null && p.factor_mayor !== undefined ? p.factor_mayor : '1';
        alternarCamposInventario();
        modalProducto.modal.classList.add('show');
        inputNombre.focus();
    };

    window.eliminarProducto = async (id) => {
        if (!confirm('¿Eliminar este producto/servicio del catálogo?')) return;
        try {
            const respuesta = await fetch('eliminar_concepto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_concepto: id })
            });
            const resultado = await respuesta.json();
            if (resultado.exito) {
                mostrarAlerta('Eliminado del catálogo.');
                cargarCatalogo();
            } else {
                alert(resultado.mensaje);
            }
        } catch (error) {
            console.error(error);
        }
    };

    btnGuardar.addEventListener('click', async () => {
        const nombre = inputNombre.value.trim();
        if (!nombre) { alert('El nombre es obligatorio.'); return; }

        const datos = {
            nombre,
            categoria: selectCategoria.value,
            grupo: inputGrupo.value.trim() || null,
            precio_unitario: inputPrecio.value !== '' ? Number(inputPrecio.value) : null,
            stock: inputStock.value !== '' ? Number(inputStock.value) : 0,
            factor_mayor: inputFactor.value !== '' ? Number(inputFactor.value) : 1
        };

        const esEdicion = inputId.value !== '';
        if (esEdicion) datos.id_concepto = Number(inputId.value);

        try {
            btnGuardar.disabled = true;
            const respuesta = await fetch(esEdicion ? 'actualizar_concepto.php' : 'insertar_concepto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });
            const resultado = await respuesta.json();
            if (resultado.exito) {
                modalProducto.close();
                mostrarAlerta(esEdicion ? 'Actualizado con éxito.' : 'Agregado al catálogo.');
                cargarCatalogo();
            } else {
                alert(resultado.mensaje);
            }
        } finally {
            btnGuardar.disabled = false;
        }
    });

    inputBuscar.addEventListener('input', renderizar);
    filtroCategoria.addEventListener('change', renderizar);
    document.addEventListener('tasa-bcv-lista', renderizar);

    cargarCatalogo();
}
