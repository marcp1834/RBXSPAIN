<?php
// Funciones para hablar con la API de NOWPayments
// Documentacion oficial: https://documenter.getpostman.com/view/7907941/S1a32n38

require_once(__DIR__ . '/cargar_env.php');

// URL base de la API
define('NP_BASE', 'https://api.nowpayments.io/v1');


// Crea una factura/invoice en NOWPayments
// Devuelve un array con la URL para redirigir al usuario, o null si hay error
//
// Parametros:
// - importe_eur: cuanto va a depositar el usuario en EUR
// - moneda_crypto: codigo de la cripto (eth, ltc, usdc, usdttrc20, sol)
// - id_pago_local: id de nuestra tabla pagos_crypto, lo mandamos como order_id
function np_crear_pago($importe_eur, $moneda_crypto, $id_pago_local) {
    $clave = $_ENV['NOWPAYMENTS_API_KEY'] ?? '';
    $url_base = $_ENV['URL_BASE'] ?? '';

    if ($clave === '' || $clave === 'PON_AQUI_TU_CLAVE') {
        return null;
    }

    // Datos que enviamos a NOWPayments
    $cuerpo = array(
        'price_amount' => $importe_eur,
        'price_currency' => 'eur',
        'pay_currency' => $moneda_crypto,
        'order_id' => (string)$id_pago_local,
        'order_description' => 'Deposito de saldo',
        // Donde nos avisa NOWPayments cuando el pago se confirma
        'ipn_callback_url' => $url_base . '/webhook_nowpayments.php',
        // A donde mandamos al usuario despues
        'success_url' => $url_base . '/deposito.php?ok=1',
        'cancel_url' => $url_base . '/deposito.php?cancelado=1'
    );

    $ch = curl_init(NP_BASE . '/invoice');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cuerpo));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'x-api-key: ' . $clave,
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $respuesta = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($codigo !== 200 && $codigo !== 201) {
        return null;
    }
    $datos = json_decode($respuesta, true);
    return $datos;
}


// Comprueba el estado de un pago consultando a NOWPayments por su payment_id
// Lo usamos como respaldo si el webhook se pierde
function np_consultar_pago($payment_id) {
    $clave = $_ENV['NOWPAYMENTS_API_KEY'] ?? '';
    if ($clave === '') return null;

    $ch = curl_init(NP_BASE . '/payment/' . urlencode($payment_id));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: ' . $clave));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $respuesta = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($codigo !== 200) return null;
    return json_decode($respuesta, true);
}


// Verifica que la firma del webhook (IPN) viene realmente de NOWPayments
// La firma se manda en la cabecera "x-nowpayments-sig"
// Se calcula como HMAC-SHA512 del JSON ordenado alfabeticamente con el IPN_SECRET
function np_verificar_firma($cuerpo_json, $firma_recibida) {
    $secreto = $_ENV['NOWPAYMENTS_IPN_SECRET'] ?? '';
    if ($secreto === '' || $secreto === 'PON_AQUI_TU_SECRETO_IPN') {
        return false;
    }

    // Decodificamos el JSON, lo ordenamos por clave y lo volvemos a codificar
    // (NOWPayments lo firma asi)
    $datos = json_decode($cuerpo_json, true);
    if (!is_array($datos)) return false;
    ksort($datos);
    $cuerpo_ordenado = json_encode($datos);

    $firma_calculada = hash_hmac('sha512', $cuerpo_ordenado, $secreto);
    return hash_equals($firma_calculada, $firma_recibida);
}
?>
