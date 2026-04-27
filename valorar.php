<?php
// Pagina para que el comprador valore al vendedor despues de un pedido completado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('conexion.php');
require_once('funciones.php');

pedir_login();

$pedido_id = intval($_GET['pedido'] ?? 0);
$id_usuario = $_SESSION['usuario_id'];
$errores = array();

// Cargamos el pedido y comprobamos que esta completado y que es del comprador logueado
$sql = "SELECT * FROM pedidos WHERE id = ? AND comprador_id = ? AND estado = 'completado'";
$proceso = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($proceso, "ii", $pedido_id, $id_usuario);
mysqli_stmt_execute($proceso);
$pedido = mysqli_fetch_assoc(mysqli_stmt_get_result($proceso));
mysqli_stmt_close($proceso);

if (!$pedido) {
    header("Location: mis_pedidos.php");
    exit();
}

// Comprobamos que aun no haya valorado
$sql = "SELECT id FROM valoraciones WHERE pedido_id = ?";
$proceso = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($proceso, "i", $pedido_id);
mysqli_stmt_execute($proceso);
$existe = mysqli_fetch_assoc(mysqli_stmt_get_result($proceso));
mysqli_stmt_close($proceso);

if ($existe) {
    header("Location: ver_pedido.php?id=" . $pedido_id);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $estrellas = intval($_POST['estrellas']);
    $comentario = trim($_POST['comentario']);

    if ($estrellas < 1 || $estrellas > 5) {
        $errores[] = "La valoracion tiene que ser entre 1 y 5 estrellas";
    }

    if (empty($errores)) {
        // Insertamos la valoracion
        $sql = "INSERT INTO valoraciones (pedido_id, comprador_id, vendedor_id, estrellas, comentario) VALUES (?, ?, ?, ?, ?)";
        $proceso = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($proceso, "iiiis",
            $pedido_id, $id_usuario, $pedido['vendedor_id'], $estrellas, $comentario);
        mysqli_stmt_execute($proceso);
        mysqli_stmt_close($proceso);

        // Recalculamos la media de valoraciones del vendedor
        $sql = "UPDATE info_clientes
                SET valoracion_media = (
                    SELECT AVG(estrellas) FROM valoraciones WHERE vendedor_id = ?
                )
                WHERE id = ?";
        $proceso = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($proceso, "ii", $pedido['vendedor_id'], $pedido['vendedor_id']);
        mysqli_stmt_execute($proceso);
        mysqli_stmt_close($proceso);

        header("Location: ver_pedido.php?id=" . $pedido_id);
        exit();
    }
}

include('header.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Valorar vendedor</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/stylephp.css">
</head>
<body>

<section class="loginsection">
    <h2 class="tituloLogin">Valora al vendedor</h2>

    <?php if (!empty($errores)): ?>
        <div class="errores">
            <?php foreach ($errores as $err): ?>
                <p class="error"><?php echo htmlspecialchars($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="grupoLogin">
            <label class="labelUsuario">Estrellas (1-5):</label>
            <select name="estrellas" class="inputLogin" required>
                <option value="5">5 - Excelente</option>
                <option value="4">4 - Bueno</option>
                <option value="3">3 - Normal</option>
                <option value="2">2 - Malo</option>
                <option value="1">1 - Muy malo</option>
            </select>
        </div>

        <div class="grupoLogin">
            <label class="labelUsuario">Comentario:</label>
            <textarea name="comentario" class="inputLogin" rows="4" placeholder="Cuenta tu experiencia con el vendedor"></textarea>
        </div>

        <div class="grupoLogin">
            <input type="submit" value="Enviar valoracion" class="botonLogin">
        </div>
    </form>
</section>

</body>
</html>
