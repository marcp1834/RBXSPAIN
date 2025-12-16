<?php
require_once('conexion.php');
$errores = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $contrasena = $_POST['contrasenaRegistro'] ?? '';
    $usuario = $_POST['usuarioRegistro'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $sexo = $_POST['sexo'] ?? '';

    if (strlen($usuario) < 3 || strlen($usuario) > 20) {
        $errores[] = "El nombre de usuario debe tener entre 3 y 20 caracteres de longitud";
    }
    if (strlen($contrasena) < 6 || strlen($contrasena) > 15) {
        $errores[] = "La contraseña debe tener entre 6 y 15 caracteres de longitud";
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo introducido no es válido";
    }
    if (strlen($nombre) < 2) {
        $errores[] = "El nombre debe tener al menos 2 caracteres.";
    }
    if (strlen($apellido) < 2) {
        $errores[] = "El apellido debe tener al menos 2 caracteres.";
    }
    if (strlen($telefono) < 7) {
        $errores[] = "El teléfono debe tener al menos 7 dígitos.";
    }
    if (empty($sexo)) {
        $errores[] = "El campo sexo es obligatorio.";
    }


    if (empty($errores)) {

        $proceso = $conexion->prepare("SELECT id FROM info_clientes WHERE usuario = ?");
        $proceso->bind_param("s", $usuario);
        $proceso->execute();
        $proceso->store_result();
        if ($proceso->num_rows > 0) {
            $errores[] = "El nombre de usuario ya existe.";
        }
        $proceso->close();

        $proceso = $conexion->prepare("SELECT id FROM info_clientes WHERE correo = ?");
        $proceso->bind_param("s", $correo);
        $proceso->execute();
        $proceso->store_result();
        if ($proceso->num_rows > 0) {
            $errores[] = "El correo electrónico ya está registrado.";
        }
        $proceso->close();
    }


    if (empty($errores)) {
        $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $proceso = $conexion->prepare("INSERT INTO info_clientes (nombre, apellido, correo, contrasena, usuario, telefono, sexo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $proceso->bind_param("sssssss", $nombre, $apellido, $correo, $contrasena_hash, $usuario, $telefono, $sexo);
        if ($proceso->execute()) {
            header("Location: login.php");
            exit;
        } else {
            $errores[] = "Error al registrar el usuario: " . $proceso->error;
        }
        $proceso->close();
    }
    $conexion->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Registro</title>
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/stylephp.css" />
    <link rel="stylesheet" href="css/password.css" />
</head>
<body>
    <?php include('header.php'); ?>

    <section class="registrosection">
        <h2 class="tituloRegistro">Registrate</h2>
        <form action="register.php" method="post">
            <div class="grupoRegistro">
                <label for="nombre" class="labelRegistro">Nombre:</label>
                <input type="text" id="nombre" name="nombre" class="inputRegistro" required 
                       value="<?php echo htmlspecialchars($nombre ?? ''); ?>" />
            </div>

            <div class="grupoRegistro">
                <label for="apellido" class="labelRegistro">Apellido:</label>
                <input type="text" id="apellido" name="apellido" class="inputRegistro" required 
                       value="<?php echo htmlspecialchars($apellido ?? ''); ?>" />
            </div>

            <div class="grupoRegistro">
                <label for="correo" class="labelRegistro">Correo:</label>
                <input type="email" id="correo" name="correo" class="inputRegistro" required 
                       value="<?php echo htmlspecialchars($correo ?? ''); ?>" />
            </div>

            <div class="grupoRegistro">
                <label for="contrasenaRegistro" class="labelRegistro">Contraseña:</label>
                <div class="password-container">
                    <input type="password" id="contrasenaRegistro" name="contrasenaRegistro" class="inputRegistro" required />
                    <span class="toggle-password">🔒</span>
                </div>
            </div>

            <div class="grupoRegistro">
                <label for="usuarioRegistro" class="labelRegistro">Nombre de usuario:</label>
                <input type="text" id="usuarioRegistro" name="usuarioRegistro" class="inputRegistro" required value="" />
            </div>

            <div class="grupoRegistro">
                <label for="telefono" class="labelRegistro">Telefono:</label>
                <input type="tel" id="telefono" name="telefono" class="inputRegistro" required 
                       value="<?php echo htmlspecialchars($telefono ?? ''); ?>" />
            </div>

            <div class="grupoRegistro">
                <label for="sexo" class="labelRegistro">Sexo:</label>
                <select id="sexo" name="sexo" class="inputRegistro" required>
                    <option value="">Selecciona tu sexo</option>
                    <option value="hombre" <?php if(isset($sexo) && $sexo=='hombre') echo 'selected'; ?>>Hombre</option>
                    <option value="mujer" <?php if(isset($sexo) && $sexo=='mujer') echo 'selected'; ?>>Mujer</option>
                    <option value="otro" <?php if(isset($sexo) && $sexo=='otro') echo 'selected'; ?>>Otro</option>
                </select>
            </div>

            <?php if (!empty($errores)): ?>
                <div class="errores">
                    <?php foreach ($errores as $error): ?>
                        <p class="error"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="grupoRegistro">
                <input type="submit" value="Registrarse" class="botonRegistro" />
            </div>
        </form>
    </section>

    <script src="js/main.js"></script>
</body>
</html>




