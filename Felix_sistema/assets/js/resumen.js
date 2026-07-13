function initResumen() {
    const cuerpoTabla = document.getElementById('cuerpo-tabla');
    const inputMesDesde = document.getElementById('mes-desde');
    const inputMesHasta = document.getElementById('mes-hasta');

    let ultimoIngresos = 0, ultimoEgresos = 0;

    function pintarTotales() {
        const tasa = window.TASA_BCV.tasa || 0;
        document.getElementById('total-ingresos').textContent = formatearUSD(ultimoIngresos);
        document.getElementById('total-egresos').textContent = formatearUSD(ultimoEgresos);
        document.getElementById('balance-neto').textContent = formatearUSD(ultimoIngresos - ultimoEgresos);
        document.getElementById('total-ingresos-bs').textContent = tasa > 0 ? formatearBs(ultimoIngresos * tasa) : '—';
        document.getElementById('total-egresos-bs').textContent = tasa > 0 ? formatearBs(ultimoEgresos * tasa) : '—';
        document.getElementById('balance-neto-bs').textContent = tasa > 0 ? formatearBs((ultimoIngresos - ultimoEgresos) * tasa) : '—';

        const tasaEl = document.getElementById('tasa-dia');
        const tasaDetalle = document.getElementById('tasa-dia-detalle');
        if (tasaEl) {
            tasaEl.textContent = tasa > 0 ? formatearBs(tasa) : 'Sin tasa';
            tasaDetalle.textContent = tasa > 0
                ? `${window.TASA_BCV.fecha} · origen: ${window.TASA_BCV.origen}`
                : 'Fíjala con ✏️ en la barra superior';
        }
    }

    document.addEventListener('tasa-bcv-lista', pintarTotales);

    async function cargarResumen(desde = '', hasta = '') {
        try {
            const url = construirUrlMovimientos(desde, hasta);
            const respuesta = await fetch(url);
            const resultado = await respuesta.json();

            if (resultado.exito) {
                cuerpoTabla.innerHTML = '';
                let tIngresos = 0, tEgresos = 0;

                if (resultado.datos.length === 0) {
                    cuerpoTabla.innerHTML = '<tr><td colspan="12" style="text-align:center;">No hay registros para este periodo.</td></tr>';
                } else {
                    resultado.datos.forEach(m => {
                        const monto = parseFloat(m.monto);
                        if (m.tipo === 'ingreso') tIngresos += monto;
                        else tEgresos += monto;
                        cuerpoTabla.innerHTML += renderizarFilaMovimiento(m, true);
                    });
                }

                ultimoIngresos = tIngresos;
                ultimoEgresos = tEgresos;
                pintarTotales();
            }
        } catch (error) {
            console.error(error);
            cuerpoTabla.innerHTML = '<tr><td colspan="12" style="text-align:center; color:red;">Error de comunicación con el servidor.</td></tr>';
        }
    }

    window.recargarTabla = () => {
        const rango = obtenerRangoDesdeInputs(inputMesDesde, inputMesHasta);
        if (rango.desde && rango.hasta) {
            cargarResumen(rango.desde, rango.hasta);
        } else {
            cargarResumen();
        }
    };

    document.getElementById('btn-filtrar').addEventListener('click', () => {
        if (!inputMesDesde.value || !inputMesHasta.value) {
            alert('Seleccione el rango de meses.');
            return;
        }
        const rango = obtenerRangoDesdeInputs(inputMesDesde, inputMesHasta);
        cargarResumen(rango.desde, rango.hasta);
    });

    document.getElementById('btn-mes-actual').addEventListener('click', () => {
        establecerMesActual(inputMesDesde, inputMesHasta);
        const rango = obtenerRangoDesdeInputs(inputMesDesde, inputMesHasta);
        cargarResumen(rango.desde, rango.hasta);
    });

    document.getElementById('btn-limpiar').addEventListener('click', () => {
        inputMesDesde.value = '';
        inputMesHasta.value = '';
        cargarResumen();
    });

    establecerMesActual(inputMesDesde, inputMesHasta);
    const rango = obtenerRangoDesdeInputs(inputMesDesde, inputMesHasta);
    cargarResumen(rango.desde, rango.hasta);
}
