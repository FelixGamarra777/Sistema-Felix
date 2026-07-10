function initListarMovimientos(config) {
    const {
        tipo,
        cuerpoTablaId = 'cuerpo-tabla',
        inputMesDesdeId = 'mes-desde',
        inputMesHastaId = 'mes-hasta',
        mostrarTipo = false,
        etiquetaVacio = 'No hay registros para este periodo.'
    } = config;

    const cuerpoTabla = document.getElementById(cuerpoTablaId);
    const inputMesDesde = document.getElementById(inputMesDesdeId);
    const inputMesHasta = document.getElementById(inputMesHastaId);
    const colspan = mostrarTipo ? 11 : 10;

    async function cargarTabla(desde = '', hasta = '') {
        try {
            const url = construirUrlMovimientos(desde, hasta, tipo);
            const respuesta = await fetch(url);
            const resultado = await respuesta.json();

            if (resultado.exito) {
                cuerpoTabla.innerHTML = '';
                if (resultado.datos.length === 0) {
                    cuerpoTabla.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center;">${etiquetaVacio}</td></tr>`;
                } else {
                    resultado.datos.forEach(m => {
                        cuerpoTabla.innerHTML += renderizarFilaMovimiento(m, mostrarTipo);
                    });
                }
            }
        } catch (error) {
            console.error(error);
            cuerpoTabla.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center; color:red;">Error de comunicación con el servidor.</td></tr>`;
        }
    }

    window.recargarTabla = () => {
        const rango = obtenerRangoDesdeInputs(inputMesDesde, inputMesHasta);
        if (rango.desde && rango.hasta) {
            cargarTabla(rango.desde, rango.hasta);
        } else {
            cargarTabla();
        }
    };

    document.getElementById('btn-filtrar').addEventListener('click', () => {
        if (!inputMesDesde.value || !inputMesHasta.value) {
            alert('Seleccione el rango de meses.');
            return;
        }
        const rango = obtenerRangoDesdeInputs(inputMesDesde, inputMesHasta);
        cargarTabla(rango.desde, rango.hasta);
    });

    document.getElementById('btn-mes-actual').addEventListener('click', () => {
        establecerMesActual(inputMesDesde, inputMesHasta);
        const rango = obtenerRangoDesdeInputs(inputMesDesde, inputMesHasta);
        cargarTabla(rango.desde, rango.hasta);
    });

    document.getElementById('btn-limpiar').addEventListener('click', () => {
        inputMesDesde.value = '';
        inputMesHasta.value = '';
        cargarTabla();
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            establecerMesActual(inputMesDesde, inputMesHasta);
            const rango = obtenerRangoDesdeInputs(inputMesDesde, inputMesHasta);
            cargarTabla(rango.desde, rango.hasta);
        });
    } else {
        establecerMesActual(inputMesDesde, inputMesHasta);
        const rango = obtenerRangoDesdeInputs(inputMesDesde, inputMesHasta);
        cargarTabla(rango.desde, rango.hasta);
    }
}
