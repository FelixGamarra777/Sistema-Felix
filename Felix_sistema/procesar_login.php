<?php
// Iniciar la sesión de PHP
session_start();

// Incluyes tu archivo de conexión actual
include 'conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Limpiar variables para evitar inyecciones básicas (asumiendo que usas mysqli en conexion.php)
    // Nota: Lo ideal en entornos profesionales es usar Prepared Statements (Sentencias preparadas)
    $usuario = mysqli_real_escape_string($conn, $_POST['usuario']);
    $password = $_POST['password'];

    // Buscar al usuario
    $sql = "SELECT * FROM usuarios WHERE usuario = '$usuario'";
    $resultado = mysqli_query($conn, $sql);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $row = mysqli_fetch_assoc(resultado);
        
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