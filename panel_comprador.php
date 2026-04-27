<?php
// Panel principal del comprador (cualquier usuario logueado)
// Muestra resumen de saldo, pedidos pendientes y links a las acciones
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('conexion.php');
require_once('funciones.php');

pedir_login();

$id_usuario = $_SESSION['usuario_id'];

// Datos basicos del usuario
$sql = "SELECT usuario, saldo, fecha_registro, rol FROM info_clientes WHERE id = ?";
$proceso = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($proceso, "i", $id_usuario);
mysqli_stmt_execute($proceso);
$datos = mysqli_fetch_assoc(mysqli_stmt_get_result($proceso));
mysqli_stmt_close($proceso);

// Cuantos pedidos tiene en curso
$sql = "SELECT COUNT(*) AS total FROM pedidos WHERE comprador_id = ? AND estado NOT IN ('completado','cancelado')";
$proceso = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($proceso, "i", $id_usuario);
mysqli_stmt_execute($proceso);
$total_pendientes = mysqli_fetch_assoc(mysqli_stmt_get_result($proceso))['total'];
mysqli_stmt_close($proceso);

include('header.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi cuenta</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<section class="loginsection">
    <h2 class="tituloLogin">Hola, <?php echo htmlspecialchars($datos['usuario']); ?></h2>

    <div class="grupoLogin">
        <p>Saldo disponible: <strong><?php echo number_format($datos['saldo'], 2); ?> EUR</strong></p>
        <p>Pedidos en curso: <strong><?php echo $total_pendientes; ?></strong></p>
        <p>Miembro desde: <?php echo $datos['fecha_registro']; ?></p>
    </div>

    <div class="grupoLogin">
        <button class="botonLogin" onclick="location.href='deposito.php'">Anadir saldo (crypto)</button>
    </div>
    <div class="grupoLogin">
        <button class="botonLogin" onclick="location.href='mis_pedidos.php'">Mis pedidos</button>
    </div>
    <div class="grupoLogin">
        <button class="botonLogin" onclick="location.href='marketplace.php'">Ir al marketplace</button>
    </div>

    <!-- Si todavia no es vendedor le mostramos el boton -->
    <?php if ($datos['rol'] === 'comprador'): ?>
        <div class="grupoLogin">
            <button class="botonLogin" onclick="location.href='hacerse_vendedor.php'">Hacerme vendedor</button>
        </div>
    <?php endif; ?>
    <?php if ($datos['rol'] === 'vendedor' || $datos['rol'] === 'admin'): ?>
        <div class="grupoLogin">
            <button class="botonLogin" onclick="location.href='panel_vendedor.php'">Ir al panel de vendedor</button>
        </div>
    <?php endif; ?>
</section>

</body>
</html>
