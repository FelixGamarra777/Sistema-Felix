<?php
if (!isset($paginaActiva)) $paginaActiva = '';
if (!isset($tituloPagina)) $tituloPagina = 'Gestión de Finanzas';
if (!isset($incluirChartJs)) $incluirChartJs = false;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($tituloPagina); ?> - Inversiones Compunet Segura. C.A.</title>
    <link rel="stylesheet" href="assets/css/estilos.css" />
    <?php if ($incluirChartJs): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
</head>
<body>

<header>
    <div class="user-bar">
        <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
        <a href="cerrar_sesion.php" class="logout-btn">Cerrar Sesión</a>
    </div>

    <h1>Gestión de Finanzas - Inversiones Compunet Segura. C.A.</h1>
    <p>Gestión Inteligente de Ingresos y Egresos - Área de Papelería</p>

    <nav class="main-nav">
        <a href="index.php" class="<?php echo $paginaActiva === 'resumen' ? 'active' : ''; ?>">Resumen</a>
        <a href="registrar_ingreso.php" class="<?php echo $paginaActiva === 'registrar_ingreso' ? 'active' : ''; ?>">Registrar Ingreso</a>
        <a href="registrar_egreso.php" class="<?php echo $paginaActiva === 'registrar_egreso' ? 'active' : ''; ?>">Registrar Egreso</a>
        <a href="listar_ingresos.php" class="<?php echo $paginaActiva === 'listar_ingresos' ? 'active' : ''; ?>">Ver Ingresos</a>
        <a href="listar_egresos.php" class="<?php echo $paginaActiva === 'listar_egresos' ? 'active' : ''; ?>">Ver Egresos</a>
        <a href="estadisticas.php" class="<?php echo $paginaActiva === 'estadisticas' ? 'active' : ''; ?>">Estadísticas</a>
    </nav>
</header>

<div class="container">
