<?php
require_once('conexion.php');

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $proceso_contrasenas = $conexion->prepare("SELECT * FROM recuperaciones WHERE token = ? AND expira > NOW()");
    $proceso_contrasenas->bind_param("s", $token);
    $proceso_contrasenas->execute();
    $resultado = $proceso_contrasenas->get_result();

    if ($row = $resultado->fetch_assoc()) {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Restablecer contraseña</title>
            <link rel="stylesheet" href="css/styles.css">
        </head>
        <body>
        <section class="recuperar-section">
            <h2>Restablecer contraseña</h2>
            <form action="restablecer_password.php" method="post">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <label>Nueva contraseña:</label>
                <input type="password" name="nueva_contrasena" required>
                <button type="submit" class="botonRecuperar">Restablecer</button>
            </form>
        </section>
        </body>
        </html>
        <?php
        exit();
    }
    $proceso_contrasenas->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['token']) && isset($_POST['nueva_contrasena'])) {
    $token = $_POST['token'];
    $nueva_contrasena = password_hash($_POST['nueva_contrasena'], PASSWORD_DEFAULT);

    $proceso_contrasenas = $conexion->prepare("SELECT * FROM recuperaciones WHERE token = ? AND expira > NOW()");
    $proceso_contrasenas->bind_param("s", $token);
    $proceso_contrasenas->execute();
    $resultado = $proceso_contrasenas->get_result();

    if ($row = $resultado->fetch_assoc()) {
        $user_id = $row['user_id'];
        $proceso_contrasenas2 = $conexion->prepare("UPDATE info_clientes SET contrasena = ? WHERE id = ?");
        $proceso_contrasenas2->bind_param("si", $nueva_contrasena, $user_id);
        $proceso_contrasenas2->execute();
        $proceso_contrasenas2->close();

        $proceso_contrasenas3 = $conexion->prepare("DELETE FROM recuperaciones WHERE token = ?");
        $proceso_contrasenas3->bind_param("s", $token);
        $proceso_contrasenas3->execute();
        $proceso_contrasenas3->close();
    }
    $proceso_contrasenas->close();
}

header("Location: login.php");
exit();
?>