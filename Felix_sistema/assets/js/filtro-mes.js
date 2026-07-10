function mesActual() {
    const hoy = new Date();
    const mes = String(hoy.getMonth() + 1).padStart(2, '0');
    return `${hoy.getFullYear()}-${mes}`;
}

function mesARangoFechas(mesDesde, mesHasta) {
    if (!mesDesde || !mesHasta) return { desde: '', hasta: '' };

    const [anioDesde, mesD] = mesDesde.split('-').map(Number);
    const [anioHasta, mesH] = mesHasta.split('-').map(Number);

    const desde = `${anioDesde}-${String(mesD).padStart(2, '0')}-01`;
    const ultimoDia = new Date(anioHasta, mesH, 0).getDate();
    const hasta = `${anioHasta}-${String(mesH).padStart(2, '0')}-${String(ultimoDia).padStart(2, '0')}`;

    return { desde, hasta };
}

function establecerMesActual(inputDesde, inputHasta) {
    const actual = mesActual();
    if (inputDesde) inputDesde.value = actual;
    if (inputHasta) inputHasta.value = actual;
}

function obtenerRangoDesdeInputs(inputDesde, inputHasta) {
    return mesARangoFechas(inputDesde.value, inputHasta.value);
}
