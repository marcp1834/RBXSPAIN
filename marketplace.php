<?php
// Marketplace publico
// Si no hay categoria seleccionada, mostramos las categorias disponibles
// Si hay (?categoria=robux), mostramos las ofertas de esa categoria
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('conexion.php');
require_once('funciones.php');

$slug_categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

// Si no se ha seleccionado categoria, listamos todas
$categoria_actual = null;
$ofertas = null;

if ($slug_categoria === '') {
    $resultado_categorias = mysqli_query($conexion, "SELECT * FROM categorias WHERE activa = 1 ORDER BY nombre");
} else {
    // Primero comprobamos que la categoria existe
    $sql = "SELECT * FROM categorias WHERE slug = ? AND activa = 1";
    $proceso = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($proceso, "s", $slug_categoria);
    mysqli_stmt_execute($proceso);
    $resultado = mysqli_stmt_get_result($proceso);
    $categoria_actual = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($proceso);

    if (!$categoria_actual) {
        // Si la categoria no existe, volvemos al listado
        header("Location: marketplace.php");
        exit();
    }

    // Buscamos las ofertas activas con stock dentro de esa categoria
    // Tambien sacamos el nombre del vendedor y su valoracion
    $sql = "SELECT o.*, u.usuario AS nombre_vendedor, u.valoracion_media, u.ventas_completadas
            FROM ofertas o
            JOIN info_clientes u ON o.vendedor_id = u.id
            WHERE o.categoria_id = ? AND o.estado = 'activa' AND o.stock_disponible > 0
            ORDER BY o.precio_unitario ASC";
    $proceso = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($proceso, "i", $categoria_actual['id']);
    mysqli_stmt_execute($proceso);
    $ofertas = mysqli_stmt_get_result($proceso);
}

include('header.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Marketplace</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php if ($slug_categoria === ''): ?>
    <!-- LISTADO DE CATEGORIAS -->
    <section class="catalogo">
        <h2>Selecciona una categoria</h2>
        <?php while ($cat = mysqli_fetch_assoc($resultado_categorias)): ?>
            <div class="cajascatalogo">
                <div class="imgcatalogo">
                    <img src="img/<?php echo htmlspecialchars($cat['imagen']); ?>" alt="<?php echo htmlspecialchars($cat['nombre']); ?>">
                </div>
                <nav class="textocatalogo">
                    <p><strong><?php echo htmlspecialchars($cat['nombre']); ?></strong></p>
                    <p><?php echo htmlspecialchars($cat['descripcion']); ?></p>
                </nav>
                <a href="marketplace.php?categoria=<?php echo urlencode($cat['slug']); ?>" class="robux">Ver ofertas</a>
            </div>
        <?php endwhile; ?>
    </section>

<?php else: ?>
    <!-- LISTADO DE OFERTAS DE UNA CATEGORIA -->
    <section class="catalogo">
        <h2>Ofertas de <?php echo htmlspecialchars($categoria_actual['nombre']); ?></h2>
        <p><a href="marketplace.php">&larr; Volver a categorias</a></p>

        <?php if (mysqli_num_rows($ofertas) === 0): ?>
            <p>Aun no hay ofertas en esta categoria. Vuelve mas tarde!</p>
        <?php else: ?>
            <?php while ($oferta = mysqli_fetch_assoc($ofertas)): ?>
                <div class="cajascatalogo">
                    <nav class="textocatalogo">
                        <p><strong><?php echo htmlspecialchars($oferta['titulo']); ?></strong></p>
                        <p>Vendedor: <?php echo htmlspecialchars($oferta['nombre_vendedor']); ?>
                           (<?php echo number_format($oferta['valoracion_media'], 1); ?> / 5 |
                           <?php echo $oferta['ventas_completadas']; ?> ventas)</p>
                        <p>Precio: <?php echo number_format($oferta['precio_unitario'], 4); ?> EUR / unidad</p>
                        <p>Minimo: <?php echo $oferta['unidad_minima']; ?> | Stock: <?php echo $oferta['stock_disponible']; ?></p>
                    </nav>
                    <a href="ver_oferta.php?id=<?php echo $oferta['id']; ?>" class="robux">Comprar</a>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </section>
<?php endif; ?>

</body>
</html>
