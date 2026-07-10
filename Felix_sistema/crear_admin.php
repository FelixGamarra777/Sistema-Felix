<?php
include 'conexion.php';

$usuario = 'admin';
$password_plana = 'mi_clave_secreta'; // Cambia esto por la contraseña que quieras usar

// 🔴 Aquí generas el hash seguro
$password_encriptada = password_hash($password_plana, PASSWORD_BCRYPT);

// Se guarda en la base de datos
$sql = "INSERT INTO usuarios (usuario, password) VALUES ('$usuario', '$password_encriptada')";

if (mysqli_query($conn, $sql)) {
    echo "¡Usuario administrador creado con éxito!";
} else {
    echo "Error al crear: " . mysqli_error($conn);
}
?>