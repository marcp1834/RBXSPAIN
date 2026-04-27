<?php
// Pagina donde un comprador se convierte en vendedor
// Lo unico que hace es cambiar el campo "rol" en la tabla info_clientes
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('conexion.php');
require_once('funciones.php');

// Hay que estar logueado para entrar aqui
pedir_login();

// Si ya es vendedor lo mandamos directamente a su panel
$rol = obtener_rol($conexion);
if ($rol === 'vendedor' || $rol === 'admin') {
    header("Location: panel_vendedor.php");
    exit();
}

$mensaje = '';

// Cuando el usuario confirma el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    $id_usuario = $_SESSION['usuario_id'];

    $sql = "UPDATE info_clientes SET rol = 'vendedor' WHERE id = ?";
    $proceso = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($proceso, "i", $id_usuario);

    if (mysqli_stmt_execute($proceso)) {
        // Actualizamos la sesion para que el header lo vea al instante
        $_SESSION['rol'] = 'vendedor';
        mysqli_stmt_close($proceso);
        header("Location: panel_vendedor.php");
        exit();
    } else {
        $mensaje = "Hubo un error al cambiar el rol, prueba de nuevo";
    }
    mysqli_stmt_close($proceso);
}

include('header.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hacerse vendedor</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/stylephp.css">
</head>
<body>

<section class="loginsection">
    <h2 class="tituloLogin">Conviertete en vendedor</h2>
    <p>Como vendedor podras subir tus propias ofertas de monedas de videojuegos.</p>
    <p>Recuerda que cuando un comprador te pague, los fondos quedaran retenidos durante un periodo de seguridad de hasta 7 dias o hasta que el confirme la entrega.</p>

    <?php if ($mensaje !== ''): ?>
        <p class="mensajeerror"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <form method="post">
        <div class="grupoLogin">
            <input type="submit" name="confirmar" value="Si, quiero ser vendedor" class="botonLogin">
        </div>
    </form>
</section>

</body>
</html>
