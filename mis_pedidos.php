<?php
// Lista los pedidos donde el usuario es comprador
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('conexion.php');
require_once('funciones.php');

pedir_login();
$id_usuario = $_SESSION['usuario_id'];

// Cogemos todos los pedidos del usuario, ordenados por fecha mas reciente
$sql = "SELECT p.*, o.titulo AS titulo_oferta, v.usuario AS nombre_vendedor
        FROM pedidos p
        JOIN ofertas o ON p.oferta_id = o.id
        JOIN info_clientes v ON p.vendedor_id = v.id
        WHERE p.comprador_id = ?
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
    <title>Mis pedidos</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<section class="catalogo">
    <h2>Mis pedidos</h2>

    <?php if (mysqli_num_rows($resultado) === 0): ?>
        <p>Todavia no tienes pedidos. <a href="marketplace.php">Ir al marketplace</a></p>
    <?php else: ?>
        <?php while ($p = mysqli_fetch_assoc($resultado)): ?>
            <div class="cajascatalogo">
                <nav class="textocatalogo">
                    <p><strong>#<?php echo $p['id']; ?> - <?php echo htmlspecialchars($p['titulo_oferta']); ?></strong></p>
                    <p>Vendedor: <?php echo htmlspecialchars($p['nombre_vendedor']); ?></p>
                    <p>Cantidad: <?php echo $p['cantidad']; ?></p>
                    <p>Total: <?php echo formatear_precio($p['precio_total']); ?></p>
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
