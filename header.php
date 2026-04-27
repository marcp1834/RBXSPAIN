<!DOCTYPE html>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si hay usuario logueado, comprobamos rol y saldo para enseniar los botones que toquen
$rol_actual = null;
$saldo_actual = 0;
if (isset($_SESSION['usuario_id'])) {
    // Solo cargamos esto si todavia no esta cargada la conexion
    if (!isset($conexion)) {
        require_once(__DIR__ . '/conexion.php');
    }
    require_once(__DIR__ . '/funciones.php');
    $rol_actual = obtener_rol($conexion);
    $datos_saldo = obtener_saldo($conexion, $_SESSION['usuario_id']);
    $saldo_actual = $datos_saldo['saldo'];
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="menu">
            <div class="logo_header">
                <img src="img/logoweb.png" alt="Logo">
            </div>
            <?php if(isset($_SESSION['usuario'])): ?>
                <nav class="botonescentro">
                    <button class="btn" onclick="location.href='index.php'">HOME</button>
                    <button class="btn" onclick="location.href='marketplace.php'">MARKETPLACE</button>
                    <button class="btn" onclick="location.href='comentarios.php'">VALORANOS</button>
                    <button class="btn" onclick="location.href='masvendido.php'">LO MAS VENDIDO!</button>
                    <!-- Boton solo para los que aun no son vendedores -->
                    <?php if ($rol_actual === 'comprador'): ?>
                        <button class="btn" onclick="location.href='hacerse_vendedor.php'">VENDER</button>
                    <?php endif; ?>
                </nav>
                <div class="loginyregistro">
                    <!-- Mostramos el saldo del usuario -->
                    <button class="btn-login" onclick="location.href='deposito.php'">
                        Saldo: <?php echo number_format($saldo_actual, 2); ?> EUR
                    </button>
                    <!-- Si es vendedor le damos acceso a su panel -->
                    <?php if ($rol_actual === 'vendedor' || $rol_actual === 'admin'): ?>
                        <button class="btn-login" onclick="location.href='panel_vendedor.php'">PANEL VENDEDOR</button>
                    <?php endif; ?>
                    <button class="btn-login" onclick="location.href='panel_comprador.php'"><?php echo htmlspecialchars($_SESSION['usuario']); ?></button>
                    <button class="btn-register" onclick="location.href='cerrar_sesion.php'">CERRAR SESIÓN</button>
                </div>
            <?php else: ?>
                <nav class="botonescentro">
                    <button class="btn" onclick="location.href='index.php'">HOME</button>
                    <button class="btn" onclick="location.href='marketplace.php'">MARKETPLACE</button>
                </nav>
                <div class="loginyregistro">
                    <button class="btn-login" onclick="location.href='login.php'">LOGIN</button>
                    <button class="btn-register" onclick="location.href='register.php'">REGISTER</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
