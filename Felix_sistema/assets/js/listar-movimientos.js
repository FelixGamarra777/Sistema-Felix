function initListarMovimientos(config) {
    const {
        tipo,
        cuerpoTablaId = 'cuerpo-tabla',
        mostrarTipo = false,
        etiquetaVacio = 'No hay registros para este periodo.'
    } = config;

    const cuerpoTabla = document.getElementById(cuerpoTablaId);
    const inputDesde = document.getElementById('fecha-desde');
    const inputHasta = document.getElementById('fecha-hasta');
    const inputBuscar = document.getElementById('buscar-texto');
    const colspan = mostrarTipo ? 12 : 11;

    function fechaISO(d) {
        return d.toISOString().slice(0, 10);
    }

    // Rangos de los filtros rápidos
    const rangosRapidos = {
        dia: () => {
            const hoy = new Date();
            return { desde: fechaISO(hoy), hasta: fechaISO(hoy) };
        },
        semana: () => {
            const hoy = new Date();
            const inicio = new Date(hoy);
            const dia = hoy.getDay() === 0 ? 7 : hoy.getDay(); // lunes como inicio
            inicio.setDate(hoy.getDate() - (dia - 1));
            return { desde: fechaISO(inicio), hasta: fechaISO(hoy) };
        },
        mes: () => {
            const hoy = new Date();
            return { desde: fechaISO(new Date(hoy.getFullYear(), hoy.getMonth(), 1)), hasta: fechaISO(hoy) };
        },
        anio: () => {
            const hoy = new Date();
            return { desde: fechaISO(new Date(hoy.getFullYear(), 0, 1)), hasta: fechaISO(hoy) };
        },
        todo: () => ({ desde: '', hasta: '' })
    };

    async function cargarTabla() {
        try {
            const params = new URLSearchParams();
            if (inputDesde.value && inputHasta.value) {
                params.set('desde', inputDesde.value);
                params.set('hasta', inputHasta.value);
            }
            if (tipo) params.set('tipo', tipo);
            const q = inputBuscar.value.trim();
            if (q) params.set('q', q);

            const qs = params.toString();
            const respuesta = await fetch('obtener_movimientos.php' + (qs ? `?${qs}` : ''));
            const resultado = await respuesta.json();

            if (resultado.exito) {
                cuerpoTabla.innerHTML = '';
                if (resultado.datos.length === 0) {
                    cuerpoTabla.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center;">${etiquetaVacio}</td></tr>`;
                } else {
                    let total = 0;
                    resultado.datos.forEach(m => {
                        total += parseFloat(m.monto);
                        cuerpoTabla.innerHTML += renderizarFilaMovimiento(m, mostrarTipo);
                    });
                    const totalBox = document.getElementById('total-listado');
                    if (totalBox) totalBox.textContent = formatearUSD(total);
                }
            }
        } catch (error) {
            console.error(error);
            cuerpoTabla.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center; color:red;">Error de comunicación con el servidor.</td></tr>`;
        }
    }

    window.recargarTabla = cargarTabla;

    function marcarChip(chipActivo) {
        document.querySelectorAll('.chip-filtro').forEach(c => c.classList.remove('activo'));
        if (chipActivo) chipActivo.classList.add('activo');
    }

    document.querySelectorAll('.chip-filtro[data-rango]').forEach(chip => {
        chip.addEventListener('click', () => {
            const rango = rangosRapidos[chip.dataset.rango]();
            inputDesde.value = rango.desde;
            inputHasta.value = rango.hasta;
            marcarChip(chip);
            cargarTabla();
        });
    });

    document.getElementById('btn-filtrar').addEventListener('click', () => {
        if (!inputDesde.value || !inputHasta.value) {
            alert('Complete el rango de fechas (Desde y Hasta).');
            return;
        }
        marcarChip(null);
        cargarTabla();
    });

    // Búsqueda por texto con pequeño retardo para no saturar el servidor
    let timerBusqueda = null;
    inputBuscar.addEventListener('input', () => {
        clearTimeout(timerBusqueda);
        timerBusqueda = setTimeout(cargarTabla, 350);
    });

    function arrancar() {
        // Vista inicial: el mes en curso
        const rango = rangosRapidos.mes();
        inputDesde.value = rango.desde;
        inputHasta.value = rango.hasta;
        const chipMes = document.querySelector('.chip-filtro[data-rango="mes"]');
        if (chipMes) chipMes.classList.add('activo');
        cargarTabla();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', arrancar);
    } else {
        arrancar();
    }
}
