<?php
// Editar una oferta existente del vendedor logueado
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
$oferta_id = intval($_GET['id'] ?? 0);
if ($oferta_id <= 0) {
    header("Location: mis_ofertas.php");
    exit();
}

$errores = array();

// Buscamos la oferta y comprobamos que pertenece al vendedor logueado
$sql = "SELECT * FROM ofertas WHERE id = ? AND vendedor_id = ?";
$proceso = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($proceso, "ii", $oferta_id, $id_usuario);
mysqli_stmt_execute($proceso);
$resultado = mysqli_stmt_get_result($proceso);
$oferta = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($proceso);

// Si no existe o no es del usuario, lo echamos
if (!$oferta) {
    header("Location: mis_ofertas.php");
    exit();
}

// Cargamos las categorias para el select
$resultado_categorias = mysqli_query($conexion, "SELECT id, nombre FROM categorias WHERE activa = 1 ORDER BY nombre");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $categoria_id = intval($_POST['categoria_id']);
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $instrucciones = trim($_POST['instrucciones']);
    $precio_unitario = floatval($_POST['precio_unitario']);
    $unidad_minima = intval($_POST['unidad_minima']);
    $stock = intval($_POST['stock']);

    if (strlen($titulo) < 5) $errores[] = "Titulo demasiado corto";
    if ($precio_unitario <= 0) $errores[] = "El precio tiene que ser mayor que 0";
    if ($stock < 0) $errores[] = "El stock no puede ser negativo";

    if (empty($errores)) {
        $sql = "UPDATE ofertas
                SET categoria_id = ?, titulo = ?, descripcion = ?, instrucciones = ?,
                    precio_unitario = ?, unidad_minima = ?, stock_disponible = ?
                WHERE id = ? AND vendedor_id = ?";
        $proceso = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($proceso, "isssdiiii",
            $categoria_id, $titulo, $descripcion, $instrucciones,
            $precio_unitario, $unidad_minima, $stock,
            $oferta_id, $id_usuario);
        if (mysqli_stmt_execute($proceso)) {
            mysqli_stmt_close($proceso);
            header("Location: mis_ofertas.php");
            exit();
        }
        $errores[] = "Error al guardar los cambios";
        mysqli_stmt_close($proceso);
    }
}

include('header.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar oferta</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/stylephp.css">
</head>
<body>

<section class="registrosection">
    <h2 class="tituloRegistro">Editar oferta</h2>

    <?php if (!empty($errores)): ?>
        <div class="errores">
            <?php foreach ($errores as $err): ?>
                <p class="error"><?php echo htmlspecialchars($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="grupoRegistro">
            <label class="labelRegistro">Categoria:</label>
            <select name="categoria_id" class="inputRegistro" required>
                <?php while ($cat = mysqli_fetch_assoc($resultado_categorias)): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php if ($cat['id'] == $oferta['categoria_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat['nombre']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="grupoRegistro">
            <label class="labelRegistro">Titulo:</label>
            <input type="text" name="titulo" class="inputRegistro" value="<?php echo htmlspecialchars($oferta['titulo']); ?>" required>
        </div>

        <div class="grupoRegistro">
            <label class="labelRegistro">Descripcion:</label>
            <textarea name="descripcion" class="inputRegistro" rows="4" required><?php echo htmlspecialchars($oferta['descripcion']); ?></textarea>
        </div>

        <div class="grupoRegistro">
            <label class="labelRegistro">Instrucciones:</label>
            <textarea name="instrucciones" class="inputRegistro" rows="3"><?php echo htmlspecialchars($oferta['instrucciones']); ?></textarea>
        </div>

        <div class="grupoRegistro">
            <label class="labelRegistro">Precio unitario (EUR):</label>
            <input type="number" name="precio_unitario" class="inputRegistro" step="0.0001" value="<?php echo $oferta['precio_unitario']; ?>" required>
        </div>

        <div class="grupoRegistro">
            <label class="labelRegistro">Cantidad minima:</label>
            <input type="number" name="unidad_minima" class="inputRegistro" min="1" value="<?php echo $oferta['unidad_minima']; ?>" required>
        </div>

        <div class="grupoRegistro">
            <label class="labelRegistro">Stock:</label>
            <input type="number" name="stock" class="inputRegistro" min="0" value="<?php echo $oferta['stock_disponible']; ?>" required>
        </div>

        <div class="grupoRegistro">
            <input type="submit" value="Guardar cambios" class="botonRegistro">
        </div>
    </form>
</section>

</body>
</html>
