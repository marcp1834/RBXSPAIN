<?php
session_start();
require_once('conexion.php');
include('header.php');


$usuario_guardado = isset($_COOKIE['usuario_guardado']) ? $_COOKIE['usuario_guardado'] : '';
$contrasena_guardada = isset($_COOKIE['contrasena_guardada']) ? $_COOKIE['contrasena_guardada'] : '';

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
            setcookie('usuario_guardado', $usuario, time() + (86400 * 30), "/");
            setcookie('contrasena_guardada', $contrasena, time() + (86400 * 30), "/");

            $_SESSION['usuario'] = $datos['usuario'];
            $_SESSION['usuario_id'] = $datos['id'];
            header("Location: index.php");
            exit();
        }
    }
    $_SESSION['error_login'] = "Usuario o contraseña incorrectos";
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/stylephp.css" />
    <link rel="stylesheet" href="css/password.css" />
</head>
<body>
    <section class="loginsection">
        <h2 class="tituloLogin">Iniciar Sesión</h2>
        <form action="login.php" method="post">
            <div class="grupoLogin">
                <label for="usuario" class="labelUsuario">Nombre de usuario:</label>
                <input type="text" id="usuario" name="usuario" class="inputLogin" required 
                       value="<?php echo htmlspecialchars($usuario_guardado); ?>">
                <?php if(isset($_SESSION['error_login'])): ?>
                    <span class="mensajeerror"><?php echo $_SESSION['error_login']; ?></span>
                    <?php unset($_SESSION['error_login']); ?>
                <?php endif; ?>
            </div>
            <div class="grupoLogin">
                <label for="contrasena" class="labelContrasena">Contraseña:</label>
                <div class="password-container">
                    <input type="password" id="contrasena" name="contrasena" class="inputLogin" required
                           value="<?php echo htmlspecialchars($contrasena_guardada); ?>">
                    <span class="toggle-password">🔒</span>
                </div>
            </div>
            <div class="grupoLogin">
                <input type="submit" value="Iniciar Sesión" class="botonLogin">
            </div>
            <div class="grupoLogin">
                <a href="enviar_recuperacion.php" class="enlace-password">¿Has olvidado la contraseña?</a>
            </div>
        </form>
    </section>

    <script src="js/main.js"></script>
</body>
</html>

