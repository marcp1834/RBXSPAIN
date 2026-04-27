<?php
// Lista las ofertas que ha subido el vendedor logueado
// Permite pausar, activar o borrar ofertas
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

// Cambio de estado: el vendedor puede pausar o reactivar la oferta
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $oferta_id = intval($_GET['id']);
    $accion = $_GET['accion'];

    // Solo permitimos cambiar las propias ofertas
    if ($accion === 'pausar' || $accion === 'activar' || $accion === 'borrar') {
        if ($accion === 'borrar') {
            $sql = "DELETE FROM ofertas WHERE id = ? AND vendedor_id = ?";
            $proceso = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($proceso, "ii", $oferta_id, $id_usuario);
        } else {
            $nuevo_estado = ($accion === 'pausar') ? 'pausada' : 'activa';
            $sql = "UPDATE ofertas SET estado = ? WHERE id = ? AND vendedor_id = ?";
            $proceso = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($proceso, "sii", $nuevo_estado, $oferta_id, $id_usuario);
        }
        mysqli_stmt_execute($proceso);
        mysqli_stmt_close($proceso);
        header("Location: mis_ofertas.php");
        exit();
    }
}

// Buscamos las ofertas del vendedor con el nombre de la categoria
$sql = "SELECT o.*, c.nombre AS nombre_categoria
        FROM ofertas o
        JOIN categorias c ON o.categoria_id = c.id
        WHERE o.vendedor_id = ?
        ORDER BY o.fecha_publicacion DESC";
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
    <title>Mis ofertas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<section class="catalogo">
    <h2>Mis ofertas</h2>

    <?php if (mysqli_num_rows($resultado) === 0): ?>
        <p>Todavia no tienes ofertas. <a href="subir_oferta.php">Crear la primera</a></p>
    <?php else: ?>
        <?php while ($oferta = mysqli_fetch_assoc($resultado)): ?>
            <div class="cajascatalogo">
                <nav class="textocatalogo">
                    <p><strong><?php echo htmlspecialchars($oferta['titulo']); ?></strong></p>
                    <p>Categoria: <?php echo htmlspecialchars($oferta['nombre_categoria']); ?></p>
                    <p>Precio unitario: <?php echo number_format($oferta['precio_unitario'], 4); ?> EUR</p>
                    <p>Stock: <?php echo $oferta['stock_disponible']; ?></p>
                    <p>Estado: <?php echo htmlspecialchars($oferta['estado']); ?></p>
                </nav>
                <!-- Acciones rapidas -->
                <div>
                    <a href="editar_oferta.php?id=<?php echo $oferta['id']; ?>" class="robux">Editar</a>
                    <?php if ($oferta['estado'] === 'activa'): ?>
                        <a href="?accion=pausar&id=<?php echo $oferta['id']; ?>" class="robux">Pausar</a>
                    <?php else: ?>
                        <a href="?accion=activar&id=<?php echo $oferta['id']; ?>" class="robux">Activar</a>
                    <?php endif; ?>
                    <a href="?accion=borrar&id=<?php echo $oferta['id']; ?>" class="eliminar-btn" onclick="return confirm('Seguro que quieres borrar la oferta?')">Borrar</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</section>

</body>
</html>
