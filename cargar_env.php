<?php
// Funcion para leer el archivo .env y meter las claves en $_ENV
// Se llama una sola vez al principio de los archivos que necesiten claves de API

function cargar_env($ruta) {
    if (!file_exists($ruta)) {
        return;
    }
    // Leemos linea por linea
    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        // Saltamos los comentarios
        if (strpos(trim($linea), '#') === 0) {
            continue;
        }
        // Separamos clave y valor por el primer "="
        if (strpos($linea, '=') !== false) {
            list($clave, $valor) = explode('=', $linea, 2);
            $clave = trim($clave);
            $valor = trim($valor);
            $_ENV[$clave] = $valor;
        }
    }
}

cargar_env(__DIR__ . '/.env');
?>
