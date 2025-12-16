<?php
session_start();
include('conexion.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    
    $query = "SELECT * FROM info_clientes WHERE usuario = ?";
    $proceso1 = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($proceso1, "s", $usuario);
    mysqli_stmt_execute($proceso1);
    $resultado = mysqli_stmt_get_result($proceso1);

    if ($datos = mysqli_fetch_assoc($resultado)) {
 
        if (password_verify($contrasena, $datos['contrasena'])) {
            $_SESSION['usuario'] = $datos['usuario'];
            $_SESSION['usuario_id'] = $datos['id'];
            error_log("Usuario ID: " . $datos['id']);
            header("Location: index.php");
            exit();
        }
    }
    
    $_SESSION['error_login'] = "Nombre de usuario o contraseña incorrectos";
    header("Location: login.php");
    exit();
}
?> 
