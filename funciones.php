<?php
// Funciones comunes que se usan en varias paginas
// Cosas como: comprobar si esta logueado, comprobar el rol, formatear precios...


// Devuelve true si el usuario tiene la sesion iniciada
function esta_logueado() {
    return isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] > 0;
}


// Si el usuario no esta logueado lo manda al login
function pedir_login() {
    if (!esta_logueado()) {
        header("Location: login.php");
        exit();
    }
}


// Devuelve el rol del usuario logueado leyendolo de la base de datos
// Lo cacheamos en la sesion para no consultar cada vez
function obtener_rol($conexion) {
    if (!esta_logueado()) {
        return null;
    }
    if (isset($_SESSION['rol'])) {
        return $_SESSION['rol'];
    }
    $sql = "SELECT rol FROM info_clientes WHERE id = ?";
    $proceso = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($proceso, "i", $_SESSION['usuario_id']);
    mysqli_stmt_execute($proceso);
    $resultado = mysqli_stmt_get_result($proceso);
    if ($fila = mysqli_fetch_assoc($resultado)) {
        $_SESSION['rol'] = $fila['rol'];
        return $fila['rol'];
    }
    return null;
}


// Devuelve true si el usuario es vendedor (o admin)
function es_vendedor($conexion) {
    $rol = obtener_rol($conexion);
    return $rol === 'vendedor' || $rol === 'admin';
}


// Devuelve true si el usuario es admin
function es_admin($conexion) {
    return obtener_rol($conexion) === 'admin';
}


// Devuelve el saldo actual del usuario logueado
function obtener_saldo($conexion, $usuario_id) {
    $sql = "SELECT saldo, saldo_holding FROM info_clientes WHERE id = ?";
    $proceso = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($proceso, "i", $usuario_id);
    mysqli_stmt_execute($proceso);
    $resultado = mysqli_stmt_get_result($proceso);
    if ($fila = mysqli_fetch_assoc($resultado)) {
        return $fila;
    }
    return array('saldo' => 0, 'saldo_holding' => 0);
}


// Formatea un numero como precio en euros (ejemplo: 5.40 -> "5.40 EUR")
function formatear_precio($precio) {
    return number_format($precio, 2, '.', '') . ' EUR';
}


// Detecta el id del gamepass dentro de una URL de Roblox
// Ejemplos validos:
//   https://www.roblox.com/game-pass/123456/Mi-Gamepass
//   https://www.roblox.com/es/game-pass/123456789
// Devuelve el id como string o null si no se encuentra
function detectar_id_gamepass($url) {
    if (preg_match('/game-pass\/(\d+)/i', $url, $coincidencias)) {
        return $coincidencias[1];
    }
    return null;
}


// Llama a la API publica de Roblox para obtener el precio en robux de un gamepass
// Devuelve un array con datos o null si falla
function consultar_gamepass($id_gamepass) {
    $url = "https://apis.roblox.com/game-passes/v1/game-passes/" . intval($id_gamepass) . "/product-info";

    // Usamos cURL para hacer la peticion HTTP
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $respuesta = curl_exec($ch);
    $codigo_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($codigo_http !== 200 || !$respuesta) {
        return null;
    }
    $datos = json_decode($respuesta, true);
    if (!$datos) {
        return null;
    }
    return $datos;
}


// Genera un mensaje "de sistema" automatico dentro del chat de un pedido
// Lo usamos para avisar de cambios de estado (ej: "El comprador confirmo recepcion")
function mensaje_sistema($conexion, $pedido_id, $texto) {
    $sql = "INSERT INTO mensajes (pedido_id, emisor_id, mensaje, tipo) VALUES (?, 0, ?, 'sistema')";
    $proceso = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($proceso, "is", $pedido_id, $texto);
    mysqli_stmt_execute($proceso);
    mysqli_stmt_close($proceso);
}


// Inserta un movimiento de saldo en la tabla transacciones
function registrar_transaccion($conexion, $usuario_id, $tipo, $importe, $pedido_id, $descripcion) {
    $sql = "INSERT INTO transacciones (usuario_id, tipo, importe, pedido_id, descripcion) VALUES (?, ?, ?, ?, ?)";
    $proceso = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($proceso, "isdis", $usuario_id, $tipo, $importe, $pedido_id, $descripcion);
    mysqli_stmt_execute($proceso);
    mysqli_stmt_close($proceso);
}
?>
