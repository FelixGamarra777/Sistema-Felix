<?php
// conexion.php
$host    = "localhost";
$db      = "cyber_compunet_db"; // <-- 🔴 IMPORTANTE: Faltaba definir esta variable
$user    = "root";       
$pass    = "";           
$charset = "utf8mb4";

// Mantener la conexión mysqli por compatibilidad con scripts antiguos
$conn = mysqli_connect($host, $user, $pass, $db);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // 1. Conectar al servidor y crear la base de datos automáticamente si no existe.
    $pdoServidor = new PDO("mysql:host=$host;charset=$charset", $user, $pass, $options);
    $pdoServidor->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

    // 2. Conectar ya a la base de datos del proyecto.
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, $options);

    // 3. Sistema automatizado: verifica y repara tablas/columnas faltantes.
    require_once __DIR__ . '/instalador.php';
    verificarYRepararBaseDeDatos($pdo);

} catch (\PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(["exito" => false, "mensaje" => "Error de conexión: " . $e->getMessage()]);
    exit;
}
?>