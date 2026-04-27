<?php
// Pagina para que el vendedor cree una oferta nueva
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

$errores = array();
$exito = false;

// Cargamos las categorias para mostrarlas en el desplegable
$sql_categorias = "SELECT id, nombre FROM categorias WHERE activa = 1 ORDER BY nombre";
$resultado_categorias = mysqli_query($conexion, $sql_categorias);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $categoria_id = intval($_POST['categoria_id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $instrucciones = trim($_POST['instrucciones'] ?? '');
    $precio_unitario = floatval($_POST['precio_unitario'] ?? 0);
    $unidad_minima = intval($_POST['unidad_minima'] ?? 1);
    $stock = intval($_POST['stock'] ?? 0);

    // Validamos los campos
    if ($categoria_id <= 0) {
        $errores[] = "Tienes que escoger una categoria";
    }
    if (strlen($titulo) < 5 || strlen($titulo) > 100) {
        $errores[] = "El titulo debe tener entre 5 y 100 caracteres";
    }
    if (strlen($descripcion) < 10) {
        $errores[] = "La descripcion debe tener al menos 10 caracteres";
    }
    if ($precio_unitario <= 0) {
        $errores[] = "El precio tiene que ser mayor que 0";
    }
    if ($unidad_minima <= 0) {
        $errores[] = "La unidad minima tiene que ser mayor que 0";
    }
    if ($stock <= 0) {
        $errores[] = "El stock tiene que ser mayor que 0";
    }

    if (empty($errores)) {
        $vendedor_id = $_SESSION['usuario_id'];
        $sql = "INSERT INTO ofertas (vendedor_id, categoria_id, titulo, descripcion, instrucciones, precio_unitario, unidad_minima, stock_disponible)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $proceso = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($proceso, "iisssdii",
            $vendedor_id,
            $categoria_id,
            $titulo,
            $descripcion,
            $instrucciones,
            $precio_unitario,
            $unidad_minima,
            $stock
        );

        if (mysqli_stmt_execute($proceso)) {
            $exito = true;
            mysqli_stmt_close($proceso);
            header("Location: mis_ofertas.php");
            exit();
        } else {
            $errores[] = "Error al guardar la oferta";
        }
        mysqli_stmt_close($proceso);
    }
}

include('header.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir oferta</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/stylephp.css">
</head>
<body>

<section class="registrosection">
    <h2 class="tituloRegistro">Subir nueva oferta</h2>

    <?php if (!empty($errores)): ?>
        <div class="errores">
            <?php foreach ($errores as $err): ?>
                <p class="error"><?php echo htmlspecialchars($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="subir_oferta.php">
        <!-- Desplegable con todas las categorias activas -->
        <div class="grupoRegistro">
            <label class="labelRegistro">Categoria:</label>
            <select name="categoria_id" class="inputRegistro" required>
                <option value="">Selecciona una categoria</option>
                <?php while ($cat = mysqli_fetch_assoc($resultado_categorias)): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="grupoRegistro">
            <label class="labelRegistro">Titulo de la oferta:</label>
            <input type="text" name="titulo" class="inputRegistro" maxlength="100" required>
        </div>

        <div class="grupoRegistro">
            <label class="labelRegistro">Descripcion:</label>
            <textarea name="descripcion" class="inputRegistro" rows="4" required></textarea>
        </div>

        <!-- Instrucciones que vera el comprador justo despues de pagar -->
        <div class="grupoRegistro">
            <label class="labelRegistro">Instrucciones para el comprador:</label>
            <textarea name="instrucciones" class="inputRegistro" rows="3" placeholder="Por ejemplo: crea un gamepass con la cantidad exacta y pega la URL aqui"></textarea>
        </div>

        <div class="grupoRegistro">
            <label class="labelRegistro">Precio por unidad (EUR):</label>
            <!-- step 0.0001 porque algunas monedas valen muy poco por unidad -->
            <input type="number" name="precio_unitario" class="inputRegistro" step="0.0001" min="0.0001" required>
        </div>

        <div class="grupoRegistro">
            <label class="labelRegistro">Cantidad minima por compra:</label>
            <input type="number" name="unidad_minima" class="inputRegistro" min="1" value="1" required>
        </div>

        <div class="grupoRegistro">
            <label class="labelRegistro">Stock disponible:</label>
            <input type="number" name="stock" class="inputRegistro" min="1" required>
        </div>

        <div class="grupoRegistro">
            <input type="submit" value="Publicar oferta" class="botonRegistro">
        </div>
    </form>
</section>

</body>
</html>
