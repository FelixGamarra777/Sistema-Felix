<?php
// Iniciar la sesión de PHP
session_start();

// Incluyes tu archivo de conexión actual
include 'conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    // Buscar al usuario con prepared statement (previene inyección SQL)
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $row = $stmt->fetch();

    if ($row) {
        // Verificar contraseña con el hash de la Base de Datos
        if (password_verify($password, $row['password'])) {
            // Logueado con éxito: Guardamos datos en la sesión
            $_SESSION['usuario'] = $row['usuario'];
            $_SESSION['id_usuario'] = $row['id'];
            
            // Redireccionar al index principal
            header("Location: index.php");
            exit();
        }
    }
    
    // Si falla el usuario o la contraseña
    header("Location: login.php?error=1");
    exit();
}
?>