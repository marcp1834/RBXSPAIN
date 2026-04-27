<?php
// Pagina de un pedido concreto: chat + estado + acciones segun el rol
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('conexion.php');
require_once('funciones.php');

pedir_login();

$pedido_id = intval($_GET['id'] ?? 0);
$id_usuario = $_SESSION['usuario_id'];
$errores = array();

// Cargamos el pedido
$sql = "SELECT p.*, o.titulo AS titulo_oferta, o.precio_unitario,
               c.nombre AS nombre_categoria, c.slug AS slug_categoria,
               vendedor.usuario AS nombre_vendedor,
               comprador.usuario AS nombre_comprador
        FROM pedidos p
        JOIN ofertas o ON p.oferta_id = o.id
        JOIN categorias c ON o.categoria_id = c.id
        JOIN info_clientes vendedor ON p.vendedor_id = vendedor.id
        JOIN info_clientes comprador ON p.comprador_id = comprador.id
        WHERE p.id = ?";
$proceso = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($proceso, "i", $pedido_id);
mysqli_stmt_execute($proceso);
$resultado = mysqli_stmt_get_result($proceso);
$pedido = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($proceso);

if (!$pedido) {
    header("Location: panel_comprador.php");
    exit();
}

// Solo pueden ver el pedido el comprador, el vendedor o un admin
$es_comprador = ($id_usuario == $pedido['comprador_id']);
$es_vendedor_pedido = ($id_usuario == $pedido['vendedor_id']);
if (!$es_comprador && !$es_vendedor_pedido && !es_admin($conexion)) {
    header("Location: panel_comprador.php");
    exit();
}


// ACCIONES POST -----------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 1. ENVIAR MENSAJE EN EL CHAT
    if (isset($_POST['enviar_mensaje'])) {
        $texto = trim($_POST['mensaje']);
        if ($texto !== '') {
            $sql = "INSERT INTO mensajes (pedido_id, emisor_id, mensaje, tipo) VALUES (?, ?, ?, 'texto')";
            $proceso = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($proceso, "iis", $pedido_id, $id_usuario, $texto);
            mysqli_stmt_execute($proceso);
            mysqli_stmt_close($proceso);
        }
        header("Location: ver_pedido.php?id=" . $pedido_id);
        exit();
    }

    // 2. EL COMPRADOR MANDA LA URL DEL GAMEPASS
    // Ademas detectamos el precio del gamepass automaticamente con la API de Roblox
    if (isset($_POST['enviar_gamepass']) && $es_comprador) {
        $url = trim($_POST['gamepass_url']);
        $id_gamepass = detectar_id_gamepass($url);

        if (!$id_gamepass) {
            $errores[] = "La URL del gamepass no parece valida";
        } else {
            // Pedimos el precio en robux a la API de Roblox
            $datos_gp = consultar_gamepass($id_gamepass);
            $robux_detectados = null;
            if ($datos_gp && isset($datos_gp['PriceInRobux'])) {
                $robux_detectados = intval($datos_gp['PriceInRobux']);
            }

            $sql = "UPDATE pedidos SET gamepass_url = ?, gamepass_robux = ?, estado = 'en_proceso' WHERE id = ? AND comprador_id = ?";
            $proceso = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($proceso, "siii", $url, $robux_detectados, $pedido_id, $id_usuario);
            mysqli_stmt_execute($proceso);
            mysqli_stmt_close($proceso);

            // Avisamos por el chat
            $aviso = "Comprador envio el gamepass: " . $url;
            if ($robux_detectados !== null) {
                $aviso .= " (detectado: " . $robux_detectados . " robux)";
            }
            mensaje_sistema($conexion, $pedido_id, $aviso);

            header("Location: ver_pedido.php?id=" . $pedido_id);
            exit();
        }
    }

    // 3. EL VENDEDOR SUBE LA CAPTURA DE PAGO Y MARCA COMO ENTREGADO
    if (isset($_POST['marcar_entregado']) && $es_vendedor_pedido) {
        $ruta_imagen = null;

        // Subida de archivo (opcional)
        if (isset($_FILES['captura']) && $_FILES['captura']['error'] === UPLOAD_ERR_OK) {
            $tipos_validos = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
            if (in_array($_FILES['captura']['type'], $tipos_validos) && $_FILES['captura']['size'] < 5 * 1024 * 1024) {
                // Crear carpeta de uploads si no existe
                $carpeta = __DIR__ . '/uploads/capturas';
                if (!is_dir($carpeta)) {
                    mkdir($carpeta, 0755, true);
                }
                $extension = pathinfo($_FILES['captura']['name'], PATHINFO_EXTENSION);
                $nombre_archivo = 'pedido_' . $pedido_id . '_' . time() . '.' . $extension;
                $destino = $carpeta . '/' . $nombre_archivo;
                if (move_uploaded_file($_FILES['captura']['tmp_name'], $destino)) {
                    $ruta_imagen = 'uploads/capturas/' . $nombre_archivo;
                }
            } else {
                $errores[] = "La captura tiene que ser una imagen menor de 5MB";
            }
        }

        if (empty($errores)) {
            // Cuando el vendedor marca como entregado, el dinero pasa al saldo_holding
            // del vendedor durante 3 dias. Asi si el comprador no responde, el cron
            // liberara los fondos automaticamente
            mysqli_begin_transaction($conexion);
            try {
                // Calculamos la fecha en la que se liberaran los fondos automaticamente (3 dias)
                $sql = "UPDATE pedidos
                        SET captura_pago = ?, estado = 'entregado',
                            fecha_entregado = NOW(),
                            fecha_liberacion = DATE_ADD(NOW(), INTERVAL 3 DAY)
                        WHERE id = ? AND vendedor_id = ?";
                $proceso = mysqli_prepare($conexion, $sql);
                mysqli_stmt_bind_param($proceso, "sii", $ruta_imagen, $pedido_id, $id_usuario);
                mysqli_stmt_execute($proceso);
                mysqli_stmt_close($proceso);

                // Movemos el dinero al saldo en holding del vendedor
                $sql = "UPDATE info_clientes SET saldo_holding = saldo_holding + ? WHERE id = ?";
                $proceso = mysqli_prepare($conexion, $sql);
                mysqli_stmt_bind_param($proceso, "di", $pedido['precio_total'], $id_usuario);
                mysqli_stmt_execute($proceso);
                mysqli_stmt_close($proceso);

                registrar_transaccion($conexion, $id_usuario, 'holding', $pedido['precio_total'], $pedido_id, 'Fondos en holding por venta de ' . $pedido['titulo_oferta']);

                mensaje_sistema($conexion, $pedido_id, 'El vendedor marco el pedido como entregado. Comprueba que todo esta bien y confirma la recepcion. Si no respondes, los fondos se liberaran automaticamente en 3 dias.');

                mysqli_commit($conexion);
                header("Location: ver_pedido.php?id=" . $pedido_id);
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conexion);
                $errores[] = "Error al marcar como entregado";
            }
        }
    }

    // 4. EL COMPRADOR CONFIRMA QUE LO HA RECIBIDO -> SE LIBERA EL SALDO AL VENDEDOR
    if (isset($_POST['confirmar_recepcion']) && $es_comprador) {
        if ($pedido['estado'] !== 'entregado') {
            $errores[] = "El pedido todavia no esta marcado como entregado";
        } else {
            mysqli_begin_transaction($conexion);
            try {
                // Cambiamos estado a completado
                $sql = "UPDATE pedidos SET estado = 'completado', fecha_recibido = NOW(), fecha_liberacion = NOW() WHERE id = ?";
                $proceso = mysqli_prepare($conexion, $sql);
                mysqli_stmt_bind_param($proceso, "i", $pedido_id);
                mysqli_stmt_execute($proceso);
                mysqli_stmt_close($proceso);

                // El dinero estaba en holding desde que el vendedor marco "entregado"
                // Lo movemos a saldo disponible y subimos el contador de ventas
                $sql = "UPDATE info_clientes
                        SET saldo = saldo + ?,
                            saldo_holding = saldo_holding - ?,
                            ventas_completadas = ventas_completadas + 1
                        WHERE id = ?";
                $proceso = mysqli_prepare($conexion, $sql);
                mysqli_stmt_bind_param($proceso, "ddi", $pedido['precio_total'], $pedido['precio_total'], $pedido['vendedor_id']);
                mysqli_stmt_execute($proceso);
                mysqli_stmt_close($proceso);

                registrar_transaccion($conexion, $pedido['vendedor_id'], 'liberacion', $pedido['precio_total'], $pedido_id, 'Liberacion de fondos por confirmacion de ' . $pedido['titulo_oferta']);

                mensaje_sistema($conexion, $pedido_id, 'El comprador confirmo recepcion. Pedido completado y saldo liberado al vendedor.');

                mysqli_commit($conexion);
                header("Location: ver_pedido.php?id=" . $pedido_id);
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conexion);
                $errores[] = "Error al confirmar la recepcion";
            }
        }
    }

    // 5. ABRIR DISPUTA (puede hacerla cualquiera de los dos)
    if (isset($_POST['abrir_disputa'])) {
        $sql = "UPDATE pedidos SET estado = 'en_disputa' WHERE id = ?";
        $proceso = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($proceso, "i", $pedido_id);
        mysqli_stmt_execute($proceso);
        mysqli_stmt_close($proceso);

        mensaje_sistema($conexion, $pedido_id, 'Se ha abierto una disputa. Un administrador revisara el caso.');
        header("Location: ver_pedido.php?id=" . $pedido_id);
        exit();
    }
}

// Recargamos el pedido por si cambio algo
$proceso = mysqli_prepare($conexion, "SELECT * FROM pedidos WHERE id = ?");
mysqli_stmt_bind_param($proceso, "i", $pedido_id);
mysqli_stmt_execute($proceso);
$pedido_actualizado = mysqli_fetch_assoc(mysqli_stmt_get_result($proceso));
mysqli_stmt_close($proceso);
$pedido = array_merge($pedido, $pedido_actualizado);

// Cargamos los mensajes del chat
$sql = "SELECT m.*, u.usuario AS nombre_emisor
        FROM mensajes m
        LEFT JOIN info_clientes u ON m.emisor_id = u.id
        WHERE m.pedido_id = ?
        ORDER BY m.fecha ASC";
$proceso = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($proceso, "i", $pedido_id);
mysqli_stmt_execute($proceso);
$mensajes = mysqli_stmt_get_result($proceso);

include('header.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido #<?php echo $pedido['id']; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/stylephp.css">
</head>
<body>

<section class="loginsection">
    <h2 class="tituloLogin">Pedido #<?php echo $pedido['id']; ?></h2>

    <!-- Datos basicos del pedido -->
    <div class="grupoLogin">
        <p>Oferta: <strong><?php echo htmlspecialchars($pedido['titulo_oferta']); ?></strong></p>
        <p>Categoria: <?php echo htmlspecialchars($pedido['nombre_categoria']); ?></p>
        <p>Cantidad: <strong><?php echo $pedido['cantidad']; ?></strong></p>
        <p>Total pagado: <strong><?php echo formatear_precio($pedido['precio_total']); ?></strong></p>
        <p>Comprador: <?php echo htmlspecialchars($pedido['nombre_comprador']); ?></p>
        <p>Vendedor: <?php echo htmlspecialchars($pedido['nombre_vendedor']); ?></p>
        <p>Estado actual: <strong><?php echo htmlspecialchars($pedido['estado']); ?></strong></p>
        <?php if (!empty($pedido['gamepass_url'])): ?>
            <p>Gamepass enviado: <a href="<?php echo htmlspecialchars($pedido['gamepass_url']); ?>" target="_blank"><?php echo htmlspecialchars($pedido['gamepass_url']); ?></a></p>
            <?php if ($pedido['gamepass_robux']): ?>
                <p>Robux detectados en el gamepass: <strong><?php echo $pedido['gamepass_robux']; ?></strong></p>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (!empty($pedido['captura_pago'])): ?>
            <p>Captura de entrega:</p>
            <img src="<?php echo htmlspecialchars($pedido['captura_pago']); ?>" alt="Captura de pago" style="max-width:300px;">
        <?php endif; ?>
    </div>

    <?php if (!empty($errores)): ?>
        <div class="errores">
            <?php foreach ($errores as $err): ?>
                <p class="error"><?php echo htmlspecialchars($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ACCIONES SEGUN ESTADO Y ROL ---------------------------------- -->

    <!-- Comprador, paso 1: enviar URL del gamepass -->
    <?php if ($pedido['estado'] === 'esperando_info' && $es_comprador): ?>
        <div class="grupoLogin">
            <h3>Envia la URL del gamepass</h3>
            <p>Crea un gamepass en Roblox por la cantidad acordada y pega aqui su URL.</p>
            <form method="post">
                <input type="text" name="gamepass_url" class="inputLogin" placeholder="https://www.roblox.com/game-pass/..." required>
                <input type="submit" name="enviar_gamepass" value="Enviar gamepass" class="botonLogin">
            </form>
        </div>
    <?php endif; ?>

    <!-- Vendedor, paso 2: subir captura y marcar como entregado -->
    <?php if ($pedido['estado'] === 'en_proceso' && $es_vendedor_pedido): ?>
        <div class="grupoLogin">
            <h3>Confirma la entrega</h3>
            <p>Sube la captura de pago del gamepass y marca el pedido como entregado.</p>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="captura" class="inputLogin" accept="image/*">
                <input type="submit" name="marcar_entregado" value="Marcar como entregado" class="botonLogin">
            </form>
        </div>
    <?php endif; ?>

    <!-- Comprador, paso 3: confirmar recepcion -->
    <?php if ($pedido['estado'] === 'entregado' && $es_comprador): ?>
        <div class="grupoLogin">
            <h3>Confirma que has recibido tus monedas</h3>
            <p>Si todo esta bien, confirma la recepcion para liberar el saldo al vendedor.</p>
            <form method="post" onsubmit="return confirm('Seguro que has recibido todo correctamente?')">
                <input type="submit" name="confirmar_recepcion" value="Confirmar recepcion" class="botonLogin">
            </form>
            <p>Si no confirmas, el saldo se liberara automaticamente al vendedor en unos dias.</p>
        </div>
    <?php endif; ?>

    <!-- Boton de disputa (en cualquier estado intermedio) -->
    <?php if (in_array($pedido['estado'], array('esperando_info','en_proceso','entregado','holding'))): ?>
        <div class="grupoLogin">
            <form method="post" onsubmit="return confirm('Quieres abrir una disputa? La revisara un administrador.')">
                <input type="submit" name="abrir_disputa" value="Abrir disputa" class="eliminar-btn">
            </form>
        </div>
    <?php endif; ?>

    <!-- CHAT ----------------------------------------- -->
    <h3>Chat del pedido</h3>
    <div class="carrito" style="max-height:400px;overflow-y:auto;">
        <?php while ($msg = mysqli_fetch_assoc($mensajes)): ?>
            <div class="carrito-item">
                <small>
                    <?php if ($msg['tipo'] === 'sistema'): ?>
                        <strong>[Sistema]</strong>
                    <?php else: ?>
                        <strong><?php echo htmlspecialchars($msg['nombre_emisor']); ?>:</strong>
                    <?php endif; ?>
                    <em><?php echo $msg['fecha']; ?></em>
                </small>
                <p><?php echo nl2br(htmlspecialchars($msg['mensaje'])); ?></p>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Caja para escribir mensajes (mientras el pedido este abierto) -->
    <?php if (!in_array($pedido['estado'], array('completado','cancelado'))): ?>
        <form method="post">
            <div class="grupoLogin">
                <textarea name="mensaje" class="inputLogin" rows="2" placeholder="Escribe tu mensaje..." required></textarea>
                <input type="submit" name="enviar_mensaje" value="Enviar" class="botonLogin">
            </div>
        </form>
    <?php endif; ?>

    <!-- Si esta completado y es el comprador, link a valorar -->
    <?php if ($pedido['estado'] === 'completado' && $es_comprador): ?>
        <p><a href="valorar.php?pedido=<?php echo $pedido['id']; ?>" class="botonLogin">Valorar al vendedor</a></p>
    <?php endif; ?>

</section>

</body>
</html>
