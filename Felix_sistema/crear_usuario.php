<?php
// crear_usuario.php
include 'conexion.php';

$usuario_nuevo = 'admin';
$password_plana = 'admin123'; // Esta será tu contraseña

// Encriptamos la contraseña de forma segura
$password_encriptada = password_hash($password_plana, PASSWORD_BCRYPT);

// Insertamos en la base de datos
$sql = "INSERT INTO usuarios (usuario, password) VALUES ('$usuario_nuevo', '$password_encriptada')";

if (mysqli_query($conn, $sql)) {
    echo "<h3>¡Usuario creado con éxito!</h3>";
    echo "Puedes entrar con:<br><strong>Usuario:</strong> admin<br><strong>Contraseña:</strong> admin123";
} else {
    echo "Error al crear el usuario: " . mysqli_error($conn);
}
?>