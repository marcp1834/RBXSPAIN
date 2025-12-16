<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexion.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
if ($usuario_id == 0) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_SESSION['usuario']; 
    $comentario = trim($_POST['comentario']);
    $valoracion = isset($_POST['valoracion']) ? (int)$_POST['valoracion'] : 1;
    
    if (!empty($comentario) && strlen($comentario) <= 200) {
        $sql = "INSERT INTO comentarios (usuario, comentario, valoracion) VALUES (?, ?, ?)";
        $proceso1 = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($proceso1, "ssi", $usuario, $comentario, $valoracion);
        mysqli_stmt_execute($proceso1);
        mysqli_stmt_close($proceso1);
        header("Location: comentarios.php");
        exit();
    }
}

$sql = "SELECT * FROM comentarios ORDER BY fecha DESC";
$proceso1 = mysqli_prepare($conexion, $sql);
mysqli_stmt_execute($proceso1);
$comentarios_resultado = mysqli_stmt_get_result($proceso1);

$comentarios = [];
while($com = mysqli_fetch_assoc($comentarios_resultado)) {
    $comentarios[] = $com;
}
mysqli_stmt_close($proceso1);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comentarios</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/stylephp.css">
    <link rel="stylesheet" href="css/comentarios.css">
</head>
<body>
    <?php include ('header.php'); ?>
    <div class="contenido">
        <h2 class="titulocomentarios">Comentarios</h2>
        
        <div class="formulario-comentario">
            <form method="POST">
                <p>
                    <textarea name="comentario" id="comentario" rows="4" placeholder="Escribe tu comentario" maxlength="200"><?= isset($_POST['comentario']) ? htmlspecialchars($_POST['comentario']) : '' ?></textarea>
                    <span id="contador" class="contador">0/200</span>
                </p>
                
                <p>
                    <label class="valoracionlabel">Tu valoración:</label>
                    <div class="estrellas">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <label class="estrella">
                                ★
                                <input type="radio" name="valoracion" value="<?= $i ?>" 
                                    <?= (isset($_POST['valoracion']) && $_POST['valoracion'] == $i) ? 'checked' : '' ?>
                                    style="display: none"
                                    onclick="document.querySelectorAll('.estrella').forEach(e => e.style.color = e.querySelector('input').value <= this.value ? 'gold' : '#ddd')">
                            </label>
                        <?php endfor; ?>
                    </div>
                </p>
                
                <p>
                    <button type="submit" class="boton">Enviar Comentario</button>
                </p>
            </form>
        </div>

        <div class="lista-comentarios">
            <?php if (empty($comentarios)): ?>
                <p style="text-align: center; color: #555;">Aún no hay comentarios.</p>
            <?php else: ?>
                <?php foreach ($comentarios as $com) { ?>
                    <div class="comentario">
                        <p class="comentario-usuario">
                            <strong><?php echo htmlspecialchars($com['usuario']); ?></strong>
                            <span class="fecha"><?php echo date('d/m/Y', strtotime($com['fecha'])); ?></span>
                        </p>
                        <p class="valoracion">
                            <?php
                            for($i = 1; $i <= 5; $i++) {
                                echo '<span style="color: ' . ($i <= $com['valoracion'] ? '#FFD600' : '#888') . '">★</span>';
                            }
                            ?>
                        </p>
                        <p class="texto-comentario">
                            <?php echo htmlspecialchars($com['comentario']); ?>
                        </p>
                    </div>
                <?php } ?>
            <?php endif; ?>
        </div>
    </div>

<script>
document.getElementById('comentario').addEventListener('input', function() {
    document.getElementById('contador').textContent = this.value.length + '/200';
});
</script>
</body>
</html> 