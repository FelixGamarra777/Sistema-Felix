<?php
// reparar_usuario.php
include 'conexion.php';

// 1. Nos aseguramos de que la columna password de la base de datos soporte la encriptación (mínimo 60 ó 255 caracteres)
$alterQuery = "ALTER TABLE usuarios MODIFY COLUMN password VARCHAR(255) NOT NULL";
mysqli_query($conn, $alterQuery);

// 2. Eliminamos cualquier rastro del usuario "admin" viejo para evitar duplicados
mysqli_query($conn, "DELETE FROM usuarios WHERE usuario = 'admin'");

// 3. Creamos el hash limpio para la clave "admin123"
$usuario_nuevo = 'admin';
$password_encriptada = password_hash('admin123', PASSWORD_BCRYPT);

// 4. Insertamos el usuario limpio
$sql = "INSERT INTO usuarios (usuario, password) VALUES ('$usuario_nuevo', '$password_encriptada')";

if (mysqli_query($conn, $sql)) {
    echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>";
    echo "<h2 style='color: #28a745;'>¡Usuario 'admin' reparado con éxito!</h2>";
    echo "<p>Ya puedes cerrar esta pestaña e intentar loguearte en tu login.</p>";
    echo "<p><strong>Usuario:</strong> admin | <strong>Contraseña:</strong> admin123</p>";
    echo "</div>";
} else {
    echo "Error al reparar: " . mysqli_error($conn);
}
?>