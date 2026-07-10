function initResumen() {
    const cuerpoTabla = document.getElementById('cuerpo-tabla');
    const inputMesDesde = document.getElementById('mes-desde');
    const inputMesHasta = document.getElementById('mes-hasta');

    async function cargarResumen(desde = '', hasta = '') {
        try {
            const url = construirUrlMovimientos(desde, hasta);
            const respuesta = await fetch(url);
            const resultado = await respuesta.json();

            if (resultado.exito) {
                cuerpoTabla.innerHTML = '';
                let tIngresos = 0, tEgresos = 0;

                if (resultado.datos.length === 0) {
                    cuerpoTabla.innerHTML = '<tr><td colspan="11" style="text-align:center;">No hay registros para este periodo.</td></tr>';
                } else {
                    resultado.datos.forEach(m => {
                        const monto = parseFloat(m.monto);
                        if (m.tipo === 'ingreso') tIngresos += monto;
                        else tEgresos += monto;
                        cuerpoTabla.innerHTML += renderizarFilaMovimiento(m, true);
                    });
                }

                document.getElementById('total-ingresos').textContent = formatearUSD(tIngresos);
                document.getElementById('total-egresos').textContent = formatearUSD(tEgresos);
                document.getElementById('balance-neto').textContent = formatearUSD(tIngresos - tEgresos);
            }
        } catch (error) {
            console.error(error);
            cuerpoTabla.innerHTML = '<tr><td colspan="11" style="text-align:center; color:red;">Error de comunicación con el servidor.</td></tr>';
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
