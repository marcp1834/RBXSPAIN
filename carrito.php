<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('conexion.php');



$usuario_id = $_SESSION['usuario_id'] ?? 0;
if ($usuario_id == 0) {
    echo "Debes iniciar sesión.";
    exit();
}


if (isset($_POST['agregar'])) {
    $producto_id = intval($_POST['id']);

    $sql = "SELECT cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?";
    $proceso1 = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($proceso1, "ii", $usuario_id, $producto_id);
    mysqli_stmt_execute($proceso1);
    $res = mysqli_stmt_get_result($proceso1);

    if ($row = mysqli_fetch_assoc($res)) {

        $nueva = $row['cantidad'] + 1;
        $sql2 = "UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?";
        $proceso2 = mysqli_prepare($conexion, $sql2);
        mysqli_stmt_bind_param($proceso2, "iii", $nueva, $usuario_id, $producto_id);
        mysqli_stmt_execute($proceso2);
    } else {
        $sql2 = "INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, 1)";
        $proceso2 = mysqli_prepare($conexion, $sql2);
        mysqli_stmt_bind_param($proceso2, "ii", $usuario_id, $producto_id);
        mysqli_stmt_execute($proceso2);
    }
}


if (isset($_GET['eliminar'])) {
    $producto_id = intval($_GET['eliminar']);
    $sql = "DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?";
    $proceso1 = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($proceso1, "ii", $usuario_id, $producto_id);
    mysqli_stmt_execute($proceso1);
}


$sql = "SELECT c.producto_id, c.cantidad, p.nombre, p.precio 
        FROM carrito c 
        JOIN productos p ON c.producto_id = p.id 
        WHERE c.usuario_id = ?";
$proceso1 = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($proceso1, "i", $usuario_id);
mysqli_stmt_execute($proceso1);
$res = mysqli_stmt_get_result($proceso1);

$carrito = [];
while ($row = mysqli_fetch_assoc($res)) {
    $carrito[] = $row;
}
?>

<div class="carrito">
    <h3>Tu carrito</h3>
    <?php if (empty($carrito)): ?>
        <p>El carrito está vacío</p>
    <?php else: ?>
        <?php foreach ($carrito as $item): ?>
            <div>
                <?php echo $item['nombre']; ?> - $<?php echo $item['precio']; ?> x <?php echo $item['cantidad']; ?>
                <a href="?eliminar=<?php echo $item['producto_id']; ?>">Eliminar</a>
            </div>
        <?php endforeach; ?>
        <p>
            Total: $
            <?php
            $total = 0;
            foreach ($carrito as $item) {
                $total += $item['precio'] * $item['cantidad'];
            }
            echo $total;
            ?>
        </p>
    <?php endif; ?>
</div>
