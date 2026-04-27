<?php
// Endpoint que recibe los avisos (IPN) de NOWPayments cuando un pago cambia de estado
// Esta URL la configuras en tu cuenta de NOWPayments
// IMPORTANTE: este archivo no debe ensenar nada al usuario, solo procesar el webhook

require_once('conexion.php');
require_once('funciones.php');
require_once('nowpayments.php');

// Leemos el cuerpo crudo (JSON) que nos manda NOWPayments
$cuerpo = file_get_contents('php://input');
$firma = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';

// 1. Verificamos que el aviso es legitimo (firma HMAC-SHA512)
if (!np_verificar_firma($cuerpo, $firma)) {
    http_response_code(401);
    echo "firma invalida";
    exit();
}

$datos = json_decode($cuerpo, true);
if (!$datos) {
    http_response_code(400);
    echo "json invalido";
    exit();
}

// El campo "order_id" es el id de nuestra tabla pagos_crypto
$id_pago_local = intval($datos['order_id'] ?? 0);
$payment_status = $datos['payment_status'] ?? '';
$tx_hash = $datos['outcome']['hash'] ?? ($datos['payin_hash'] ?? null);
$importe_pagado = floatval($datos['actually_paid'] ?? 0);

if ($id_pago_local <= 0) {
    http_response_code(400);
    echo "order_id invalido";
    exit();
}

// 2. Cargamos el pago local
$sql = "SELECT * FROM pagos_crypto WHERE id = ?";
$proceso = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($proceso, "i", $id_pago_local);
mysqli_stmt_execute($proceso);
$pago = mysqli_fetch_assoc(mysqli_stmt_get_result($proceso));
mysqli_stmt_close($proceso);

if (!$pago) {
    http_response_code(404);
    echo "pago no encontrado";
    exit();
}

// Si ya estaba confirmado, no hacemos nada (evitar acreditar dos veces)
if ($pago['estado'] === 'confirmado') {
    echo "ya confirmado";
    exit();
}

// 3. Procesamos segun el estado que nos diga NOWPayments
//    Estados que nos interesan: finished/confirmed -> acreditar saldo

if ($payment_status === 'finished' || $payment_status === 'confirmed') {

    mysqli_begin_transaction($conexion);
    try {
        // Marcamos el pago como confirmado
        $sql = "UPDATE pagos_crypto
                SET estado = 'confirmado', tx_hash = ?, fecha_confirmacion = NOW(), importe_crypto = ?
                WHERE id = ?";
        $proceso = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($proceso, "sdi", $tx_hash, $importe_pagado, $id_pago_local);
        mysqli_stmt_execute($proceso);
        mysqli_stmt_close($proceso);

        // Sumamos el saldo al usuario
        $sql = "UPDATE info_clientes SET saldo = saldo + ? WHERE id = ?";
        $proceso = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($proceso, "di", $pago['importe_eur'], $pago['usuario_id']);
        mysqli_stmt_execute($proceso);
        mysqli_stmt_close($proceso);

        // Registramos transaccion
        registrar_transaccion($conexion, $pago['usuario_id'], 'deposito', $pago['importe_eur'], null, 'Deposito en ' . $pago['moneda_crypto']);

        mysqli_commit($conexion);
        echo "ok";
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        http_response_code(500);
        echo "error";
    }

} else if ($payment_status === 'failed' || $payment_status === 'expired') {
    // Pago fallido o caducado
    $sql = "UPDATE pagos_crypto SET estado = ? WHERE id = ?";
    $proceso = mysqli_prepare($conexion, $sql);
    $estado_local = ($payment_status === 'failed') ? 'fallido' : 'expirado';
    mysqli_stmt_bind_param($proceso, "si", $estado_local, $id_pago_local);
    mysqli_stmt_execute($proceso);
    mysqli_stmt_close($proceso);
    echo "actualizado";
} else {
    // Estados intermedios: waiting, confirming, sending...
    $sql = "UPDATE pagos_crypto SET estado = 'esperando_confirmacion' WHERE id = ? AND estado = 'pendiente'";
    $proceso = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($proceso, "i", $id_pago_local);
    mysqli_stmt_execute($proceso);
    mysqli_stmt_close($proceso);
    echo "ok";
}
?>
