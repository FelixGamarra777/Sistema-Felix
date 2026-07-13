let chartTorta = null;
let chartBarras = null;
let chartMensual = null;

function initEstadisticas() {
    const inputMesDesde = document.getElementById('mes-desde');
    const inputMesHasta = document.getElementById('mes-hasta');

    async function cargarEstadisticas(desde = '', hasta = '') {
        try {
            let url = 'obtener_estadisticas.php';
            if (desde && hasta) url += `?desde=${desde}&hasta=${hasta}`;
            const respuesta = await fetch(url);
            const resultado = await respuesta.json();

            if (resultado.exito) {
                renderizarGraficoTorta(resultado.totales_tipo);
                renderizarGraficoBarras(resultado.totales_concepto);
                renderizarGraficoMensual(resultado.totales_mes || []);
            }
        } catch (error) {
            console.error(error);
        }
    }

    document.getElementById('btn-filtrar').addEventListener('click', () => {
        if (!inputMesDesde.value || !inputMesHasta.value) {
            alert('Seleccione el rango de meses.');
            return;
        }
        const rango = obtenerRangoDesdeInputs(inputMesDesde, inputMesHasta);
        cargarEstadisticas(rango.desde, rango.hasta);
    });

    document.getElementById('btn-mes-actual').addEventListener('click', () => {
        establecerMesActual(inputMesDesde, inputMesHasta);
        const rango = obtenerRangoDesdeInputs(inputMesDesde, inputMesHasta);
        cargarEstadisticas(rango.desde, rango.hasta);
    });

    document.getElementById('btn-limpiar').addEventListener('click', () => {
        inputMesDesde.value = '';
        inputMesHasta.value = '';
        cargarEstadisticas();
    });

    establecerMesActual(inputMesDesde, inputMesHasta);
    const rango = obtenerRangoDesdeInputs(inputMesDesde, inputMesHasta);
    cargarEstadisticas(rango.desde, rango.hasta);
}

function renderizarGraficoTorta(datosTipo) {
    const ctx = document.getElementById('graficoTorta').getContext('2d');
    if (chartTorta) chartTorta.destroy();

    const etiquetas = [], valores = [], colores = [];
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

function renderizarGraficoMensual(datosMes) {
    const canvas = document.getElementById('graficoMensual');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (chartMensual) chartMensual.destroy();

    const meses = [...new Set(datosMes.map(d => d.mes))];
    const ingresos = meses.map(mes => {
        const fila = datosMes.find(d => d.mes === mes && d.tipo === 'ingreso');
        return fila ? parseFloat(fila.total) : 0;
    });
    const egresos = meses.map(mes => {
        const fila = datosMes.find(d => d.mes === mes && d.tipo === 'egreso');
        return fila ? parseFloat(fila.total) : 0;
    });
    const balance = meses.map((_, i) => ingresos[i] - egresos[i]);

    chartMensual = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: meses.length ? meses : ['Sin registros'],
            datasets: [
                { label: 'Ingresos ($)', data: ingresos, backgroundColor: 'rgba(40, 167, 69, 0.7)' },
                { label: 'Egresos ($)', data: egresos, backgroundColor: 'rgba(220, 53, 69, 0.7)' },
                { label: 'Balance ($)', data: balance, type: 'line', borderColor: '#007bff', backgroundColor: '#007bff', tension: 0.3 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { title: { display: true, text: 'Ingresos, Egresos y Balance por Mes' } }
        }
    });
}

function renderizarGraficoBarras(datosConceptos) {
    const ctx = document.getElementById('graficoBarras').getContext('2d');
    if (chartBarras) chartBarras.destroy();

    const conceptosUnicos = [...new Set(datosConceptos.map(item => item.concepto))];
    const ingresos = [], egresos = [];

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
