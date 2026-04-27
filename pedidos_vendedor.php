<?php
// Lista los pedidos que ha recibido el vendedor logueado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('conexion.php');
require_once('funciones.php');

pedir_login();
if (!es_vendedor($conexion)) {
    header("Location: hacerse_vendedor.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];

$sql = "SELECT p.*, o.titulo AS titulo_oferta, c.usuario AS nombre_comprador
        FROM pedidos p
        JOIN ofertas o ON p.oferta_id = o.id
        JOIN info_clientes c ON p.comprador_id = c.id
        WHERE p.vendedor_id = ?
        ORDER BY p.fecha_creacion DESC";
$proceso = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($proceso, "i", $id_usuario);
mysqli_stmt_execute($proceso);
$resultado = mysqli_stmt_get_result($proceso);

include('header.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedidos recibidos</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<section class="catalogo">
    <h2>Pedidos recibidos</h2>

    <?php if (mysqli_num_rows($resultado) === 0): ?>
        <p>Todavia no has recibido ningun pedido.</p>
    <?php else: ?>
        <?php while ($p = mysqli_fetch_assoc($resultado)): ?>
            <div class="cajascatalogo">
                <nav class="textocatalogo">
                    <p><strong>#<?php echo $p['id']; ?> - <?php echo htmlspecialchars($p['titulo_oferta']); ?></strong></p>
                    <p>Comprador: <?php echo htmlspecialchars($p['nombre_comprador']); ?></p>
                    <p>Cantidad: <?php echo $p['cantidad']; ?></p>
                    <p>Total a recibir: <?php echo formatear_precio($p['precio_total']); ?></p>
                    <p>Estado: <strong><?php echo htmlspecialchars($p['estado']); ?></strong></p>
                    <p><small>Creado el <?php echo $p['fecha_creacion']; ?></small></p>
                </nav>
                <a href="ver_pedido.php?id=<?php echo $p['id']; ?>" class="robux">Abrir pedido</a>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</section>

</body>
</html>
