<?php
require_once 'includes/auth.php';
$paginaActiva = 'estadisticas';
$tituloPagina = 'Estadísticas';
$incluirChartJs = true;
require_once 'includes/header.php';
?>

<section class="card-panel filter-section">
    <div class="form-group" style="flex-direction:row; align-items:center; gap:0.5rem;">
        <label for="mes-desde">Mes desde:</label>
        <input type="month" id="mes-desde">
    </div>
    <div class="form-group" style="flex-direction:row; align-items:center; gap:0.5rem;">
        <label for="mes-hasta">Mes hasta:</label>
        <input type="month" id="mes-hasta">
    </div>
    <button id="btn-filtrar" class="submit-btn btn-success" style="width: auto; padding: 0.6rem 1.5rem;">Filtrar</button>
    <button id="btn-mes-actual" class="submit-btn btn-info" style="width: auto; padding: 0.6rem 1.5rem;">Mes Actual</button>
    <button id="btn-limpiar" class="submit-btn btn-secondary" style="width: auto; padding: 0.6rem 1.5rem;">Ver Todo</button>
</section>

<section class="card-panel">
    <h2>Análisis Estadístico del Periodo</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; align-items: center;">
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="graficoTorta"></canvas>
        </div>
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="graficoBarras"></canvas>
        </div>
    </div>
</section>

<section class="card-panel">
    <h2>Evolución Mensual (últimos 12 meses)</h2>
    <div style="position: relative; height: 320px; width: 100%;">
        <canvas id="graficoMensual"></canvas>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
<script src="assets/js/estadisticas.js"></script>
<script>
initEstadisticas();
</script>
</body>
</html>
