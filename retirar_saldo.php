<?php
// Pagina donde el vendedor solicita retirar su saldo a una wallet crypto
// El retiro queda en estado "pendiente" hasta que el admin lo procese
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
$errores = array();
$mensaje = '';

// Cogemos el saldo del vendedor
$datos_saldo = obtener_saldo($conexion, $id_usuario);
$saldo_disponible = $datos_saldo['saldo'];

// Lista de monedas a las que puede retirar
$monedas = array(
    'eth' => 'Ethereum (ETH)',
    'ltc' => 'Litecoin (LTC)',
    'usdc' => 'USD Coin (USDC ERC-20)',
    'usdttrc20' => 'Tether (USDT TRC-20)',
    'sol' => 'Solana (SOL)'
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $importe = floatval($_POST['importe']);
    $moneda = $_POST['moneda'] ?? '';
    $direccion = trim($_POST['direccion']);

    if ($importe < 10) $errores[] = "El minimo para retirar es 10 EUR";
    if ($importe > $saldo_disponible) $errores[] = "No tienes suficiente saldo disponible";
    if (!isset($monedas[$moneda])) $errores[] = "Moneda no valida";
    if (strlen($direccion) < 10) $errores[] = "La direccion de la wallet no parece valida";

    if (empty($errores)) {
        mysqli_begin_transaction($conexion);
        try {
            // Restamos el saldo y lo dejamos "reservado" mientras se procesa el retiro
            $sql = "UPDATE info_clientes SET saldo = saldo - ? WHERE id = ? AND saldo >= ?";
            $proceso = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($proceso, "did", $importe, $id_usuario, $importe);
            mysqli_stmt_execute($proceso);
            if (mysqli_stmt_affected_rows($proceso) === 0) {
                throw new Exception("No se pudo descontar el saldo");
            }
            mysqli_stmt_close($proceso);

            // Creamos la solicitud de retiro
            $sql = "INSERT INTO retiros (vendedor_id, importe_eur, moneda_crypto, direccion_wallet) VALUES (?, ?, ?, ?)";
            $proceso = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($proceso, "idss", $id_usuario, $importe, $moneda, $direccion);
            mysqli_stmt_execute($proceso);
            mysqli_stmt_close($proceso);

            registrar_transaccion($conexion, $id_usuario, 'retiro', -$importe, null, 'Solicitud de retiro a ' . $moneda);

            mysqli_commit($conexion);
            $mensaje = "Solicitud enviada. Te llegara el dinero en cuanto el administrador la procese.";
            $datos_saldo = obtener_saldo($conexion, $id_usuario);
            $saldo_disponible = $datos_saldo['saldo'];
        } catch (Exception $e) {
            mysqli_rollback($conexion);
            $errores[] = "Error al procesar el retiro";
        }
    }
}

include('header.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Retirar saldo</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/stylephp.css">
</head>
<body>

<section class="loginsection">
    <h2 class="tituloLogin">Retirar saldo</h2>

    <p>Saldo disponible: <strong><?php echo number_format($saldo_disponible, 2); ?> EUR</strong></p>

    <?php if ($mensaje !== ''): ?>
        <p><?php echo htmlspecialchars($mensaje); ?></p>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
        <div class="errores">
            <?php foreach ($errores as $err): ?>
                <p class="error"><?php echo htmlspecialchars($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="grupoLogin">
            <label class="labelUsuario">Importe a retirar (EUR):</label>
            <input type="number" name="importe" class="inputLogin" step="0.01" min="10" max="<?php echo $saldo_disponible; ?>" required>
        </div>

        <div class="grupoLogin">
            <label class="labelUsuario">Moneda:</label>
            <select name="moneda" class="inputLogin" required>
                <?php foreach ($monedas as $cod => $nom): ?>
                    <option value="<?php echo $cod; ?>"><?php echo htmlspecialchars($nom); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grupoLogin">
            <label class="labelUsuario">Direccion de tu wallet:</label>
            <input type="text" name="direccion" class="inputLogin" required>
        </div>

        <div class="grupoLogin">
            <input type="submit" value="Solicitar retiro" class="botonLogin">
        </div>
    </form>
</section>

</body>
</html>
