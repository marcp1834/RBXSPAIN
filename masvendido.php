<?php
session_start();
require_once('conexion.php');
include('header.php');

$usuario_id = $_SESSION['usuario_id'] ?? 0;
if ($usuario_id == 0) {
    header("Location: login.php");
    exit();
}

$sql = "SELECT producto_id, SUM(cantidad) as total_anadido FROM carrito GROUP BY producto_id ORDER BY total_anadido DESC";
$res = mysqli_query($conexion, $sql);

$productos = array();
$anadidos = array();

while($row = mysqli_fetch_assoc($res)) {
    $id_producto = $row['producto_id'];
    $total_anadido = $row['total_anadido'];
    $sql2 = "SELECT nombre FROM productos WHERE id = $id_producto";
    $res2 = mysqli_query($conexion, $sql2);
    $prod = mysqli_fetch_assoc($res2);
    if ($prod) {
        $productos[] = $prod['nombre'];
        $anadidos[] = $total_anadido;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lo Más POPULAR</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/grafica.css">
</head>
<body>
    <h2 class="titulo-grafica">Productos Más Populares</h2>
    <?php if (count($productos) > 0): ?>
    <div class="contenedor-canvas">
        <canvas class="miGrafica"></canvas>
    </div>
    <div class="botones-grafica">
        <button class="btn" onclick="mostrarGrafica()">Mostrar Gráfica</button>
    </div>
    <div style="text-align:center; margin-top:20px;">
        <button class="btn" onclick="leerTexto()">🔊 Leer texto</button>
        <button class="btn" onclick="detenerLectura()">⏹️ Detener lectura</button>
    </div>
    <script>
        var productos = <?php echo json_encode($productos); ?>;
        var anadidos = <?php echo json_encode($anadidos); ?>;
        var miGrafica = null;
        function mostrarGrafica() {
            var canvas = document.querySelector('.miGrafica');
            var ctx = canvas.getContext('2d');
            if (miGrafica) {
                miGrafica.destroy();
            }
            miGrafica = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: productos,
                    datasets: [{
                        label: 'Veces añadido',
                        data: anadidos,
                        backgroundColor: 'rgba(32, 100, 47, 0.8)',
                        borderColor: 'rgba(32, 100, 47, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Veces añadido'
                            },
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Productos'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Productos Más Añadidos al Carrito'
                        }
                    }
                }
            });
        }
    </script>
    <?php else: ?>
        <p class="mensaje-grafica">Aún no hay productos añadidos al carrito para mostrar la gráfica.</p>
    <?php endif; ?>

    <style>
        .contenedor-canvas {
            width: 80%;
            height: 400px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .titulo-grafica {
            text-align: center;
            color: #333;
            margin: 20px 0;
            font-family: Georgia, 'Times New Roman', Times, serif;
        }

        .botones-grafica {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
        }

        .btn {
            padding: 10px 20px;
            background-color: #20642fc9;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #164a22;
        }
    </style>
    <script>
    function leerTexto() {
        let texto = "Productos más vendidos de CM Store, te invitamos a que revises nuestros productos y que te diviertas	.";
        let locucion = new SpeechSynthesisUtterance(texto);
        locucion.lang = 'es-ES';
        locucion.pitch = 1.2;
        locucion.rate = 1.15;
        if (window.speechSynthesis.speaking || window.speechSynthesis.pending) {
            window.speechSynthesis.cancel();
        }
        window.speechSynthesis.speak(locucion);
    }
    function detenerLectura() {
        window.speechSynthesis.cancel();
    }
    </script>
</body>
</html>