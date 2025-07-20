<?php
$host = "localhost";
$usuario = "root";
$contrasena = "";
$bd = "cine";

$conn = new mysqli($host, $usuario, $contrasena, $bd);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$pelicula_id = isset($_GET['pelicula_id']) ? (int)$_GET['pelicula_id'] : 0;

if ($pelicula_id <= 0) {
    die("ID de película no válido.");
}

$sql = "SELECT * FROM peliculas WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pelicula_id);
$stmt->execute();
$resultado = $stmt->get_result();
$pelicula = $resultado->fetch_assoc();

if (!$pelicula) {
    die("Película no encontrada.");
}

// Procesar reserva
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["reservar"])) {
    $sesion = $_POST["sesion"];
    $asientos = $_POST["asientos"] ?? '';

    if ($sesion !== "" && $asientos !== "") {
        // Aquí puedes guardar los asientos en la base de datos si lo deseas
        echo "<script>
            alert('¡Reserva realizada para \"" . addslashes($pelicula['titulo']) . "\" en la sesión: $sesion con los asientos: $asientos!');
            window.location.href = 'cliente.php';
        </script>";
        exit;
    } else {
        echo "<script>alert('Por favor, seleccione una sesión y al menos un asiento.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reservar Entrada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('https://png.pngtree.com/thumb_back/fh260/background/20230704/pngtree-experience-artful-cinema-from-home-enjoy-popcorn-3d-glasses-and-film-image_3726475.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            font-family: 'Segoe UI', sans-serif;
            color: white;
        }
        .reserva-box {
            background: rgba(0, 0, 0, 0.75);
            padding: 40px;
            border-radius: 15px;
            max-width: 800px;
            margin: auto;
            margin-top: 100px;
            box-shadow: 0 0 20px rgba(255, 0, 0, 0.4);
        }
        .seat {
            width: 30px;
            height: 30px;
            background-color: #444;
            margin: 5px;
            border-radius: 5px;
            cursor: pointer;
        }
        .seat.selected {
            background-color: #6feaf6;
        }
        .seat.occupied {
            background-color: #ff4747;
            cursor: not-allowed;
        }
        .seat-container {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            justify-items: center;
            margin-bottom: 20px;
        }
        .legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 10px;
        }
        .legend div {
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="reserva-box">
        <h2>🎟️ Reservar entradas para:<br><?php echo htmlspecialchars($pelicula['titulo']); ?></h2>

        <form method="POST" id="form-reserva">
            <label for="sesion" class="form-label">Seleccione Sesión</label>
            <select class="form-select mb-3" name="sesion" id="sesion" required>
                <option value="">-- Seleccione --</option>
                <option value="matinee">Matinée (2:00 PM)</option>
                <option value="tarde">Tarde (5:00 PM)</option>
                <option value="noche">Noche (8:00 PM)</option>
            </select>

            <div class="legend">
                <div><div class="seat"></div> Disponible</div>
                <div><div class="seat selected"></div> Seleccionado</div>
                <div><div class="seat occupied"></div> Ocupado</div>
            </div>

            <div class="seat-container" id="asientos">
                <?php
                $filas = 5;
                $columnas = 10;
                for ($i = 1; $i <= $filas * $columnas; $i++) {
                    echo "<div class='seat' data-num='$i'></div>";
                }
                ?>
            </div>

            <input type="hidden" name="asientos" id="asientosInput">

            <button type="submit" name="reservar" class="btn btn-primary">Reservar</button>
            <a href="cliente.php" class="btn btn-secondary ms-2">Volver</a>
        </form>
    </div>

    <script>
        const seats = document.querySelectorAll('.seat');
        const asientosInput = document.getElementById('asientosInput');
        const sesionSelect = document.getElementById('sesion');

        let ocupados = [];

        sesionSelect.addEventListener('change', () => {
            seats.forEach(seat => {
                seat.classList.remove('occupied', 'selected');
                seat.classList.add('available');
            });

            const sesion = sesionSelect.value;
            if (!sesion) return;

            fetch(`obtener_asientos_ocupados.php?sesion_id=${sesion}`)
                .then(res => res.json())
                .then(data => {
                    ocupados = data.ocupados.map(Number);
                    seats.forEach(seat => {
                        const num = parseInt(seat.getAttribute('data-num'));
                        if (ocupados.includes(num)) {
                            seat.classList.add('occupied');
                        }
                    });
                });
        });

        seats.forEach(seat => {
            seat.addEventListener('click', () => {
                if (seat.classList.contains('occupied')) return;
                seat.classList.toggle('selected');

                const seleccionados = Array.from(document.querySelectorAll('.seat.selected'))
                    .map(s => s.getAttribute('data-num'));
                asientosInput.value = seleccionados.join(',');
            });
        });
    </script>
</body>
</html>
