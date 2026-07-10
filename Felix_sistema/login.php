<?php
// login.php
session_start();

// Si ya tiene sesión activa, redirigir al index automáticamente
if (isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$error_mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'conexion.php';
    
    $usuario = mysqli_real_escape_string($conn, $_POST['usuario']);
    $password_ingresada = $_POST['password'];
    
    // Consultar el usuario en la base de datos
    $sql = "SELECT * FROM usuarios WHERE usuario = '$usuario'";
    $resultado = mysqli_query($conn, $sql);
    
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $row = mysqli_fetch_assoc($resultado);
        
        // Verificación usando password_verify para la clave encriptada
        if (password_verify($password_ingresada, $row['password'])) {
            $_SESSION['usuario'] = $row['usuario'];
            header("Location: index.php");
            exit();
        } else {
            $error_mensaje = "Contraseña incorrecta.";
        }
    } else {
        $error_mensaje = "El usuario no existe.";
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Control de Acceso - Inversiones Compunet Segura. C.A.</title>
    <style>
        :root {
            --primary: #007bff;
            --primary-hover: #0056b3;
            --danger: #dc3545;
            --dark: #343a40;
            --bg-color: #e9ecef;
            --card-bg: #ffffff;
            --border: #ced4da;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: var(--bg-color); color: var(--dark); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1rem; }
        .login-container { background-color: var(--card-bg); padding: 2.5rem 2rem; border-radius: 12px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1); width: 100%; max-width: 420px; text-align: center; }
        .login-header h1 { font-size: 1.6rem; color: var(--dark); font-weight: 600; margin-bottom: 0.25rem; }
        .login-header p { font-size: 0.9rem; color: #6c757d; margin-bottom: 2rem; }
        .form-group { display: flex; flex-direction: column; text-align: left; margin-bottom: 1.25rem; }
        label { font-size: 0.9rem; color: #495057; margin-bottom: 0.5rem; font-weight: 600; }
        input { padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 8px; font-size: 1rem; transition: border-color 0.3s, box-shadow 0.3s; width: 100%; }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1); }
        .submit-btn { margin-top: 0.5rem; padding: 0.8rem 2rem; border: none; border-radius: 8px; background-color: var(--primary); color: white; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background-color 0.3s; width: 100%; }
        .submit-btn:hover { background-color: var(--primary-hover); }
        .alert-danger { padding: 0.75rem; margin-bottom: 1.2rem; border-radius: 8px; background-color: #f8d7da; color: var(--danger); border: 1px solid #f5c6cb; font-size: 0.9rem; font-weight: 500; text-align: left; }
        .login-footer { margin-top: 2rem; font-size: 0.8rem; color: #6c757d; }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-header">
            <h1>Control de Acceso</h1>
            <p>Inversiones Compunet Segura. C.A.</p>
        </div>

        <?php if (!empty($error_mensaje)): ?>
            <div class="alert-danger">
                <?php echo htmlspecialchars($error_mensaje); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" placeholder="Ingrese su usuario" required autofocus />
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required />
            </div>

            <button type="submit" class="submit-btn">Ingresar al Sistema</button>
        </form>

        <div class="login-footer">
            <p>&copy; Área de Papelería e Informática</p>
        </div>
    </div>

</body>
</html>