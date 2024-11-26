<?php
// Inicia la sesión
session_start();

// Conexión a la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'bd_hotel';
$conn = new mysqli($host, $user, $pass, $dbname);

// Verifica si la conexión es exitosa
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Función para formatear la fecha
function formatearFecha($fecha) {
    setlocale(LC_TIME, 'es_ES.UTF-8');
    return strftime("%B %d", strtotime($fecha));
}

// Verifica si las fechas están guardadas en la sesión
$check_in = isset($_SESSION['check_in']) ? $_SESSION['check_in'] : null;
$check_out = isset($_SESSION['check_out']) ? $_SESSION['check_out'] : null;

// Verifica si hay información sobre habitaciones en la sesión
$habitaciones = isset($_SESSION['habitaciones']) ? $_SESSION['habitaciones'] : 0;
$adultos = isset($_SESSION['adultos']) ? $_SESSION['adultos'] : [];
$ninos = isset($_SESSION['ninos']) ? $_SESSION['ninos'] : [];

// Formatear fechas
$fecha_estadia = "";
if ($check_in && $check_out) {
    $fecha_estadia = "Desde " . formatearFecha($check_in) . " hasta " . formatearFecha($check_out);
}

// Almacén de habitaciones disponibles
$habitacionesDisponibles = [];

// Consulta a la base de datos para buscar habitaciones disponibles
if ($check_in && $check_out && $habitaciones > 0) {
    // Recorre cada habitación solicitada por el usuario
    for ($i = 0; $i < $habitaciones; $i++) {
        // Sumar la cantidad de adultos y niños por habitación
        $adultos_requeridos = $adultos[$i];
        $ninos_requeridos = $ninos[$i];
        $capacidad_total = $adultos_requeridos + $ninos_requeridos; // Sumar adultos y niños

        // Consulta SQL para buscar habitaciones que tengan suficiente capacidad
        $sql = "SELECT * FROM cuartos 
                WHERE capacidad >= $capacidad_total 
                AND id_cuarto NOT IN (
                    SELECT id_cuarto FROM reservas 
                    WHERE (check_in BETWEEN '$check_in' AND '$check_out') 
                    OR (check_out BETWEEN '$check_in' AND '$check_out')
                )";

        $resultado = $conn->query($sql);

        if ($resultado->num_rows > 0) {
            // Agregar habitaciones que cumplen los requisitos a la lista de disponibles
            while ($fila = $resultado->fetch_assoc()) {
                $habitacionesDisponibles[] = $fila;
            }
        }
    }
}

// Manejo del formulario de reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Almacena en la sesión las habitaciones seleccionadas
    $_SESSION['reservas'] = isset($_POST['reservas']) ? $_POST['reservas'] : [];
    
    // Redirige a la página de pago
    header('Location: pagor.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reservas</title>
</head>
<body>
    <h1>Gestión de Reservas</h1>

    <?php if ($fecha_estadia): ?>
        <h2>Estadía: <?php echo htmlspecialchars($fecha_estadia); ?></h2>
        <h3>Detalles de Habitaciones Solicitadas:</h3>
        <ul>
            <?php for ($i = 0; $i < $habitaciones; $i++): ?>
                <li>Habitación <?php echo $i + 1; ?> - Adultos: <?php echo $adultos[$i]; ?>, Niños: <?php echo $ninos[$i]; ?></li>
            <?php endfor; ?>
        </ul>
    <?php else: ?>
        <p>No se han seleccionado fechas.</p>
    <?php endif; ?>

    <form action="gestionh.php" method="POST">
        <h3>Habitaciones Disponibles</h3>
        
        <?php if (!empty($habitacionesDisponibles)): ?>
            <?php foreach ($habitacionesDisponibles as $habitacion): ?>
                <div class="habitacion-disponible">
                    <h4><?php echo htmlspecialchars($habitacion['descripcion']); ?></h4>
                    <p><strong>Numero:</strong> <?php echo $habitacion['numero']; ?></p>
                    <p><strong>Tipo:</strong> <?php echo $habitacion['tipo']; ?></p>
                    <p><strong>Piso:</strong> <?php echo $habitacion['piso']; ?></p>
                    <p><strong>Estado:</strong> <?php echo $habitacion['estado']; ?></p>
                    <p><strong>Capacidad:</strong> <?php echo $habitacion['capacidad']; ?> personas</p>
                    <p><strong>Precio Base:</strong> <?php echo $habitacion['precio_base']; ?></p>
                    <label>
                        <input type="checkbox" name="reservas[]" value="<?php echo $habitacion['id_cuarto']; ?>">
                        Reservar esta habitación
                    </label>
                    <hr>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay habitaciones disponibles que cumplan con los criterios solicitados.</p>
        <?php endif; ?>

        <!-- Botón para enviar la información -->
        <button type="submit">Continuar</button>
    </form>
</body>
</html>

<?php
// Cierra la conexión a la base de datos
$conn->close();
?>
