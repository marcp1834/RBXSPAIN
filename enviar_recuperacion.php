<?php
require_once('conexion.php');
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = $_POST['email'];

    $proceso1 = $conexion->prepare("SELECT id FROM info_clientes WHERE correo = ?");
    $proceso1->bind_param("s", $email);
    $proceso1->execute();
    $resultado = $proceso1->get_result();

    if ($usuario = $resultado->fetch_assoc()) {
        $token = bin2hex(random_bytes(16));
        $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));
        $user_id = $usuario['id'];

        $proceso2 = $conexion->prepare("INSERT INTO recuperaciones (user_id, token, expira) VALUES (?, ?, ?)");
        $proceso2->bind_param("iss", $user_id, $token, $expira);
        $proceso2->execute();
        $proceso2->close();

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'skibidi1834yt@gmail.com';
            $mail->Password = 'zfum zhbr tqdd cdkj';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            $mail->setFrom('skibidi1834yt@gmail.com', 'CMSHOP');
            $mail->addAddress($email, 'Usuario');
            $mail->isHTML(true);
            $mail->Subject = 'Recupera tu contrasena';
            $enlace = "http://localhost/cmshop2/restablecer_password.php?token=$token";
            $mail->Body = '<h1>Recuperacion de contraseña</h1>Haz clic en este <a href="'.$enlace.'">enlace</a> para restablecer tu contrasena.';
            $mail->AltBody = 'Copia y pega este enlace en tu navegador para restablecer tu contrasena: '.$enlace;
            $mail->send();
        } catch (Exception $e) {
            echo "Error al enviar el correo: " . $mail->ErrorInfo;
        }
    }
    $proceso1->close();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <section class="recuperar-section">
        <h2>Recuperar contraseña</h2>
        <form action="enviar_recuperacion.php" method="post">
            <label for="email">Tu e-mail:</label>
            <input type="email" id="email" name="email" required placeholder="Escribe tu e-mail">
            <br>
            <button type="submit" class="botonRecuperar">Enviar</button>
        </form>
    </section>
</body>
</html>