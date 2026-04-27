<?php
// Script de tarea programada (cron) que se ejecuta cada hora
// Libera automaticamente los fondos en holding cuando se cumple el plazo
// Ejemplo de cron: 0 * * * * /usr/bin/php /var/www/html/cron_liberar_holding.php

require_once(__DIR__ . '/conexion.php');
require_once(__DIR__ . '/funciones.php');

// Buscamos los pedidos que estan en "entregado" y cuya fecha de liberacion ya paso
$sql = "SELECT * FROM pedidos
        WHERE estado = 'entregado'
        AND fecha_liberacion IS NOT NULL
        AND fecha_liberacion <= NOW()";
$resultado = mysqli_query($conexion, $sql);

$liberados = 0;

while ($pedido = mysqli_fetch_assoc($resultado)) {
    mysqli_begin_transaction($conexion);
    try {
        // 1. Marcamos el pedido como completado
        $sql_upd = "UPDATE pedidos SET estado = 'completado', fecha_recibido = NOW() WHERE id = ?";
        $proceso = mysqli_prepare($conexion, $sql_upd);
        mysqli_stmt_bind_param($proceso, "i", $pedido['id']);
        mysqli_stmt_execute($proceso);
        mysqli_stmt_close($proceso);

        // 2. Movemos el dinero del holding al saldo disponible del vendedor
        $sql_upd = "UPDATE info_clientes
                    SET saldo = saldo + ?,
                        saldo_holding = saldo_holding - ?,
                        ventas_completadas = ventas_completadas + 1
                    WHERE id = ?";
        $proceso = mysqli_prepare($conexion, $sql_upd);
        mysqli_stmt_bind_param($proceso, "ddi", $pedido['precio_total'], $pedido['precio_total'], $pedido['vendedor_id']);
        mysqli_stmt_execute($proceso);
        mysqli_stmt_close($proceso);

        // 3. Registramos transaccion y mensaje en el chat
        registrar_transaccion($conexion, $pedido['vendedor_id'], 'liberacion', $pedido['precio_total'], $pedido['id'], 'Liberacion automatica por plazo cumplido');
        mensaje_sistema($conexion, $pedido['id'], 'Plazo de seguridad cumplido. Los fondos se liberaron automaticamente al vendedor.');

        mysqli_commit($conexion);
        $liberados++;
    } catch (Exception $e) {
        mysqli_rollback($conexion);
    }
}

echo "Liberados " . $liberados . " pedidos\n";
?>
