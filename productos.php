<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('conexion.php');

include('header.php');

$id_usuario_logeado = $_SESSION['usuario_id'] ?? 0;


if ($id_usuario_logeado == 0) {
    header("Location: login.php");
    exit(); 
}

if (isset($_POST['agregar']) && $id_usuario_logeado > 0) {
    $id_producto_a_agregar = intval($_POST['id']);

    $sql_buscar = "SELECT cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?";
    $proceso1_buscar = mysqli_prepare($conexion, $sql_buscar);
    mysqli_stmt_bind_param($proceso1_buscar, "ii", $id_usuario_logeado, $id_producto_a_agregar);
    mysqli_stmt_execute($proceso1_buscar);
    $resultado_buscar = mysqli_stmt_get_result($proceso1_buscar);

    if ($fila = mysqli_fetch_assoc($resultado_buscar)) {
        $cantidad_actual = $fila['cantidad'];
        $nueva_cantidad = $cantidad_actual + 1;

        $sql_actualizar = "UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?";
        $proceso1_actualizar = mysqli_prepare($conexion, $sql_actualizar);
        mysqli_stmt_bind_param($proceso1_actualizar, "iii", $nueva_cantidad, $id_usuario_logeado, $id_producto_a_agregar);
        mysqli_stmt_execute($proceso1_actualizar);
        mysqli_stmt_close($proceso1_actualizar);
    } else {
        $cantidad_inicial = 1;
        $sql_insertar = "INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)";
        $proceso1_insertar = mysqli_prepare($conexion, $sql_insertar);
        mysqli_stmt_bind_param($proceso1_insertar, "iii", $id_usuario_logeado, $id_producto_a_agregar, $cantidad_inicial);
        mysqli_stmt_execute($proceso1_insertar);
        mysqli_stmt_close($proceso1_insertar);
    }
    mysqli_stmt_close($proceso1_buscar);

    header("Location: productos.php");
    exit();
}

if (isset($_GET['eliminar']) && $id_usuario_logeado > 0) {
    $id_producto_a_eliminar = intval($_GET['eliminar']);

    $sql_eliminar = "DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?";
    $proceso1_eliminar = mysqli_prepare($conexion, $sql_eliminar);
    mysqli_stmt_bind_param($proceso1_eliminar, "ii", $id_usuario_logeado, $id_producto_a_eliminar);
    mysqli_stmt_execute($proceso1_eliminar);
    mysqli_stmt_close($proceso1_eliminar);

    header("Location: productos.php");
    exit();
}

$sql_productos = "SELECT * FROM productos";
$resultado_productos = mysqli_query($conexion, $sql_productos);

$productos_en_carrito = array(); 
if ($id_usuario_logeado > 0) {
    $sql_carrito = "SELECT c.producto_id, c.cantidad, p.nombre, p.precio 
                    FROM carrito c 
                    JOIN productos p ON c.producto_id = p.id 
                    WHERE c.usuario_id = ?";
    $proceso1_carrito = mysqli_prepare($conexion, $sql_carrito);
    mysqli_stmt_bind_param($proceso1_carrito, "i", $id_usuario_logeado);
    mysqli_stmt_execute($proceso1_carrito);
    $resultado_carrito = mysqli_stmt_get_result($proceso1_carrito);
    
    while ($fila_carrito = mysqli_fetch_assoc($resultado_carrito)) {
        $productos_en_carrito[] = $fila_carrito;
    }
    mysqli_stmt_close($proceso1_carrito);
}

mysqli_close($conexion);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CATÁLOGO</title> 
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/carrito.css">
</head>
<body>

    <div class="carrito">
        <h3>Tu carrito</h3>
        <?php if (empty($productos_en_carrito)): ?>
            <p>El carrito está vacío</p>
        <?php else: ?>
            <?php foreach ($productos_en_carrito as $item_carrito): ?>
                <div class="carrito-item">
                    <?php echo htmlspecialchars($item_carrito['nombre']); ?> - $<?php echo $item_carrito['precio']; ?> x <?php echo $item_carrito['cantidad']; ?>
                    <a href="?eliminar=<?php echo $item_carrito['producto_id']; ?>" class="eliminar-btn">Eliminar</a>
                </div>
            <?php endforeach; ?>
            <p>
                Total: $
                <?php
                $total_carrito = 0;
                foreach ($productos_en_carrito as $item_carrito) {
                    $total_carrito += $item_carrito['precio'] * $item_carrito['cantidad'];
                }
                echo $total_carrito;
                ?>
            </p>
        <?php endif; ?>
    </div>

    <section class="catalogo">
    <?php while($producto_catalogo = mysqli_fetch_assoc($resultado_productos)): ?>
        <div class="cajascatalogo">
            <div class="imgcatalogo">
                <img src="img/<?php echo $producto_catalogo['cantidad']; ?>.png" alt="<?php echo $producto_catalogo['cantidad']; ?> Robux">
            </div>
            <nav class="textocatalogo">
                <p><?php echo $producto_catalogo['cantidad']; ?> Robux</p>
            </nav>
            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo $producto_catalogo['id']; ?>">
                <button type="submit" name="agregar" class="robux">$<?php echo $producto_catalogo['precio']; ?></button>
            </form>
        </div>
    <?php endwhile; ?>
    </section>

</body>
</html>
