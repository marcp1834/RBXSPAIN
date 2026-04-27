<?php
// Panel principal del vendedor
// Muestra resumen de saldos, valoracion media, ventas, y links a las otras paginas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('conexion.php');
require_once('funciones.php');

pedir_login();

// Solo pueden entrar los vendedores
if (!es_vendedor($conexion)) {
    header("Location: hacerse_vendedor.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];

// Cogemos los datos del vendedor
$sql = "SELECT saldo, saldo_holding, valoracion_media, ventas_completadas FROM info_clientes WHERE id = ?";
$proceso = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($proceso, "i", $id_usuario);
mysqli_stmt_execute($proceso);
$resultado = mysqli_stmt_get_result($proceso);
$datos = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($proceso);

// Contamos ofertas activas del vendedor
$sql_ofertas = "SELECT COUNT(*) AS total FROM ofertas WHERE vendedor_id = ? AND estado = 'activa'";
$proceso = mysqli_prepare($conexion, $sql_ofertas);
mysqli_stmt_bind_param($proceso, "i", $id_usuario);
mysqli_stmt_execute($proceso);
$resultado = mysqli_stmt_get_result($proceso);
$total_ofertas = mysqli_fetch_assoc($resultado)['total'];
mysqli_stmt_close($proceso);

// Contamos pedidos pendientes (todo lo que no este completado o cancelado)
$sql_pedidos = "SELECT COUNT(*) AS total FROM pedidos WHERE vendedor_id = ? AND estado NOT IN ('completado','cancelado')";
$proceso = mysqli_prepare($conexion, $sql_pedidos);
mysqli_stmt_bind_param($proceso, "i", $id_usuario);
mysqli_stmt_execute($proceso);
$resultado = mysqli_stmt_get_result($proceso);
$total_pedidos = mysqli_fetch_assoc($resultado)['total'];
mysqli_stmt_close($proceso);

include('header.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del vendedor</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<section class="loginsection">
    <h2 class="tituloLogin">Panel del vendedor</h2>

    <!-- Resumen del vendedor -->
    <div class="grupoLogin">
        <p>Saldo disponible: <strong><?php echo number_format($datos['saldo'], 2); ?> EUR</strong></p>
        <p>Saldo retenido (holding): <strong><?php echo number_format($datos['saldo_holding'], 2); ?> EUR</strong></p>
        <p>Valoracion media: <strong><?php echo number_format($datos['valoracion_media'], 2); ?> / 5</strong></p>
        <p>Ventas completadas: <strong><?php echo $datos['ventas_completadas']; ?></strong></p>
        <p>Ofertas activas: <strong><?php echo $total_ofertas; ?></strong></p>
        <p>Pedidos pendientes: <strong><?php echo $total_pedidos; ?></strong></p>
    </div>

    <!-- Botones para entrar a las distintas secciones -->
    <div class="grupoLogin">
        <button class="botonLogin" onclick="location.href='subir_oferta.php'">Subir nueva oferta</button>
    </div>
    <div class="grupoLogin">
        <button class="botonLogin" onclick="location.href='mis_ofertas.php'">Mis ofertas</button>
    </div>
    <div class="grupoLogin">
        <button class="botonLogin" onclick="location.href='pedidos_vendedor.php'">Pedidos recibidos</button>
    </div>
    <div class="grupoLogin">
        <button class="botonLogin" onclick="location.href='retirar_saldo.php'">Retirar saldo</button>
    </div>
</section>

</body>
</html>
