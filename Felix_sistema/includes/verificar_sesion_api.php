<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(["exito" => false, "mensaje" => "Sesión no válida. Inicie sesión nuevamente."]);
    exit;
}
