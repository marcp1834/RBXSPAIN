<?php
// Pagina para que el usuario deposite saldo eligiendo importe y crypto
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('conexion.php');
require_once('funciones.php');
require_once('nowpayments.php');

pedir_login();

$id_usuario = $_SESSION['usuario_id'];
$errores = array();
$mensaje = '';

// Mostrar mensaje si vuelve del flujo de NOWPayments
if (isset($_GET['ok'])) $mensaje = "Pago iniciado. Cuando se confirme la transaccion en la blockchain veras el saldo en tu cuenta.";
if (isset($_GET['cancelado'])) $mensaje = "Has cancelado el pago.";

// Lista de monedas que aceptamos
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

    if ($importe < 5) {
        $errores[] = "El importe minimo es 5 EUR";
    }
    if ($importe > 5000) {
        $errores[] = "El importe maximo es 5000 EUR";
    }
    if (!isset($monedas[$moneda])) {
        $errores[] = "Moneda no valida";
    }

    if (empty($errores)) {
        // 1. Insertamos un registro pendiente en pagos_crypto
        $sql = "INSERT INTO pagos_crypto (usuario_id, payment_id, importe_eur, moneda_crypto, estado)
                VALUES (?, '', ?, ?, 'pendiente')";
        $proceso = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($proceso, "ids", $id_usuario, $importe, $moneda);
        mysqli_stmt_execute($proceso);
        $id_pago_local = mysqli_insert_id($conexion);
        mysqli_stmt_close($proceso);

        // 2. Pedimos a NOWPayments que cree la factura
        $datos_np = np_crear_pago($importe, $moneda, $id_pago_local);

        if ($datos_np && isset($datos_np['invoice_url'])) {
            // 3. Guardamos el payment_id que nos dan
            if (isset($datos_np['id'])) {
                $payment_id_str = (string)$datos_np['id'];
                $sql = "UPDATE pagos_crypto SET payment_id = ? WHERE id = ?";
                $proceso = mysqli_prepare($conexion, $sql);
                mysqli_stmt_bind_param($proceso, "si", $payment_id_str, $id_pago_local);
                mysqli_stmt_execute($proceso);
                mysqli_stmt_close($proceso);
            }
            // 4. Redirigimos al usuario a la pagina de pago de NOWPayments
            header("Location: " . $datos_np['invoice_url']);
            exit();
        } else {
            $errores[] = "No se pudo crear la factura. Comprueba que has configurado la clave de NOWPayments en el .env";
        }
    }
}

include('header.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Anadir saldo</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/stylephp.css">
</head>
<body>

<section class="loginsection">
    <h2 class="tituloLogin">Anadir saldo</h2>

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

    <p>Elige importe y moneda. Tras confirmar te redirigiremos a la pasarela segura para realizar el pago.</p>

    <form method="post">
        <div class="grupoLogin">
            <label class="labelUsuario">Importe (EUR):</label>
            <input type="number" name="importe" class="inputLogin" step="0.01" min="5" max="5000" value="20" required>
        </div>

        <div class="grupoLogin">
            <label class="labelUsuario">Moneda:</label>
            <select name="moneda" class="inputLogin" required>
                <?php foreach ($monedas as $codigo => $nombre): ?>
                    <option value="<?php echo $codigo; ?>"><?php echo htmlspecialchars($nombre); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grupoLogin">
            <input type="submit" value="Continuar al pago" class="botonLogin">
        </div>
    </form>
</section>

</body>
</html>
