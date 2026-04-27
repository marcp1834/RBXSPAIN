<?php
// Ficha completa de una oferta + formulario para crear el pedido
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('conexion.php');
require_once('funciones.php');

$oferta_id = intval($_GET['id'] ?? 0);
if ($oferta_id <= 0) {
    header("Location: marketplace.php");
    exit();
}

// Cogemos la oferta + categoria + vendedor
$sql = "SELECT o.*, c.nombre AS nombre_categoria, c.slug AS slug_categoria,
               u.usuario AS nombre_vendedor, u.valoracion_media, u.ventas_completadas
        FROM ofertas o
        JOIN categorias c ON o.categoria_id = c.id
        JOIN info_clientes u ON o.vendedor_id = u.id
        WHERE o.id = ? AND o.estado = 'activa'";
$proceso = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($proceso, "i", $oferta_id);
mysqli_stmt_execute($proceso);
$resultado = mysqli_stmt_get_result($proceso);
$oferta = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($proceso);

if (!$oferta) {
    header("Location: marketplace.php");
    exit();
}

$errores = array();

// Cuando el comprador pulsa "Comprar"
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    pedir_login();

    $cantidad = intval($_POST['cantidad'] ?? 0);
    $comprador_id = $_SESSION['usuario_id'];

    // El vendedor no puede comprarse a si mismo
    if ($comprador_id == $oferta['vendedor_id']) {
        $errores[] = "No puedes comprar tu propia oferta";
    }

    // Validamos cantidad
    if ($cantidad < $oferta['unidad_minima']) {
        $errores[] = "Tienes que comprar al menos " . $oferta['unidad_minima'] . " unidades";
    }
    if ($cantidad > $oferta['stock_disponible']) {
        $errores[] = "No hay suficiente stock";
    }

    // Calculamos precio total
    $precio_total = round($cantidad * $oferta['precio_unitario'], 2);

    // Comprobamos saldo del comprador
    $datos_saldo = obtener_saldo($conexion, $comprador_id);
    if ($datos_saldo['saldo'] < $precio_total) {
        $errores[] = "No tienes saldo suficiente. Necesitas " . formatear_precio($precio_total) . ". Anade saldo desde tu panel.";
    }

    if (empty($errores)) {
        // Creamos el pedido y descontamos saldo en la misma operacion
        // Si algo falla a mitad, mejor que no quede a medias
        mysqli_begin_transaction($conexion);
        try {
            // Creamos el pedido en estado "esperando_info"
            $sql = "INSERT INTO pedidos (comprador_id, vendedor_id, oferta_id, cantidad, precio_total, estado)
                    VALUES (?, ?, ?, ?, ?, 'esperando_info')";
            $proceso = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($proceso, "iiiid",
                $comprador_id,
                $oferta['vendedor_id'],
                $oferta_id,
                $cantidad,
                $precio_total);
            mysqli_stmt_execute($proceso);
            $pedido_id = mysqli_insert_id($conexion);
            mysqli_stmt_close($proceso);

            // Descontamos saldo del comprador
            $sql = "UPDATE info_clientes SET saldo = saldo - ? WHERE id = ? AND saldo >= ?";
            $proceso = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($proceso, "did", $precio_total, $comprador_id, $precio_total);
            mysqli_stmt_execute($proceso);
            if (mysqli_stmt_affected_rows($proceso) === 0) {
                throw new Exception("No se pudo descontar el saldo");
            }
            mysqli_stmt_close($proceso);

            // Bajamos el stock de la oferta
            $sql = "UPDATE ofertas SET stock_disponible = stock_disponible - ? WHERE id = ?";
            $proceso = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($proceso, "ii", $cantidad, $oferta_id);
            mysqli_stmt_execute($proceso);
            mysqli_stmt_close($proceso);

            // Registramos transaccion
            registrar_transaccion($conexion, $comprador_id, 'compra', -$precio_total, $pedido_id, 'Compra de ' . $oferta['titulo']);

            // Mensaje de bienvenida del sistema dentro del chat del pedido
            mensaje_sistema($conexion, $pedido_id, 'Pedido creado. Sigue las instrucciones del vendedor.');

            // Si la oferta tiene instrucciones del vendedor, las mandamos en el chat tambien
            if (!empty($oferta['instrucciones'])) {
                $sql = "INSERT INTO mensajes (pedido_id, emisor_id, mensaje, tipo) VALUES (?, ?, ?, 'sistema')";
                $proceso = mysqli_prepare($conexion, $sql);
                $texto_inst = "Instrucciones del vendedor:\n" . $oferta['instrucciones'];
                mysqli_stmt_bind_param($proceso, "iis", $pedido_id, $oferta['vendedor_id'], $texto_inst);
                mysqli_stmt_execute($proceso);
                mysqli_stmt_close($proceso);
            }

            mysqli_commit($conexion);

            // Redirigimos al chat del pedido
            header("Location: ver_pedido.php?id=" . $pedido_id);
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conexion);
            $errores[] = "Error al crear el pedido: " . $e->getMessage();
        }
    }
}

include('header.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($oferta['titulo']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/stylephp.css">
</head>
<body>

<section class="loginsection">
    <h2 class="tituloLogin"><?php echo htmlspecialchars($oferta['titulo']); ?></h2>

    <p>Categoria: <?php echo htmlspecialchars($oferta['nombre_categoria']); ?></p>
    <p>Vendedor: <strong><?php echo htmlspecialchars($oferta['nombre_vendedor']); ?></strong>
       (<?php echo number_format($oferta['valoracion_media'], 1); ?> / 5 |
       <?php echo $oferta['ventas_completadas']; ?> ventas)</p>

    <p><strong>Descripcion:</strong></p>
    <p><?php echo nl2br(htmlspecialchars($oferta['descripcion'])); ?></p>

    <?php if (!empty($oferta['instrucciones'])): ?>
        <p><strong>Instrucciones que veras tras la compra:</strong></p>
        <p><?php echo nl2br(htmlspecialchars($oferta['instrucciones'])); ?></p>
    <?php endif; ?>

    <p>Precio: <strong><?php echo number_format($oferta['precio_unitario'], 4); ?> EUR / unidad</strong></p>
    <p>Cantidad minima: <?php echo $oferta['unidad_minima']; ?> | Stock disponible: <?php echo $oferta['stock_disponible']; ?></p>

    <?php if (!empty($errores)): ?>
        <div class="errores">
            <?php foreach ($errores as $err): ?>
                <p class="error"><?php echo htmlspecialchars($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (esta_logueado()): ?>
        <form method="post">
            <div class="grupoLogin">
                <label class="labelUsuario">Cantidad a comprar:</label>
                <input type="number" name="cantidad" class="inputLogin"
                       min="<?php echo $oferta['unidad_minima']; ?>"
                       max="<?php echo $oferta['stock_disponible']; ?>"
                       value="<?php echo $oferta['unidad_minima']; ?>" required>
            </div>
            <div class="grupoLogin">
                <input type="submit" value="Comprar ahora" class="botonLogin">
            </div>
        </form>
    <?php else: ?>
        <p><a href="login.php" class="botonLogin">Inicia sesion para comprar</a></p>
    <?php endif; ?>
</section>

</body>
</html>
