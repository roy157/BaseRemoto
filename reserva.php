<?php
session_start();

// Conexión a la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'hotel_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['continuar_fechas'])) {
    $checkin = $_POST['fecha_checkin'];
    $checkout = $_POST['fecha_checkout'];
    $_SESSION['fecha_checkin'] = $checkin;
    $_SESSION['fecha_checkout'] = $checkout;

    // 1. Buscar las reservas que incluyan las fechas seleccionadas
    $sql_reservas = "SELECT id_reserva FROM reservas WHERE fecha_checkin <= ? AND fecha_checkout >= ?";
    $stmt_reservas = $conn->prepare($sql_reservas);
    $stmt_reservas->bind_param("ss", $checkout, $checkin); // Fechas inversas para cubrir el rango completo
    $stmt_reservas->execute();
    $result_reservas = $stmt_reservas->get_result();

    $reservados = [];
    if ($result_reservas->num_rows > 0) {
        while ($row_reserva = $result_reservas->fetch_assoc()) {
            // 2. Buscar los id_cuarto en reservaporcuartos que están siendo usados en estas reservas
            $sql_cuartos_reservados = "SELECT id_cuarto FROM reservaporcuartos WHERE id_reserva = ?";
            $stmt_cuartos = $conn->prepare($sql_cuartos_reservados);
            $stmt_cuartos->bind_param("i", $row_reserva['id_reserva']);
            $stmt_cuartos->execute();
            $result_cuartos = $stmt_cuartos->get_result();
            while ($row_cuarto = $result_cuartos->fetch_assoc()) {
                $reservados[] = $row_cuarto['id_cuarto']; // Cuartos reservados
            }
        }
    }

    // 3. Mostrar los cuartos que NO están reservados
    if (count($reservados) > 0) {
        $sql_disponibles = "SELECT * FROM cuartos WHERE id_cuarto NOT IN (" . implode(",", array_fill(0, count($reservados), '?')) . ")";
        $stmt_disponibles = $conn->prepare($sql_disponibles);
        $stmt_disponibles->bind_param(str_repeat('i', count($reservados)), ...$reservados);
    } else {
        // Si no hay cuartos reservados, seleccionamos todos los cuartos disponibles
        $sql_disponibles = "SELECT * FROM cuartos";
        $stmt_disponibles = $conn->prepare($sql_disponibles);
    }

    $stmt_disponibles->execute();
    $result_disponibles = $stmt_disponibles->get_result();

    // Mostrar cuartos disponibles
    $cuartos_disponibles = [];
    while ($row_cuarto = $result_disponibles->fetch_assoc()) {
        $cuartos_disponibles[] = $row_cuarto;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva de Cuartos</title>
</head>
<body>
    <h1>Reserva de Cuartos</h1>

    <!-- Sección de Fechas -->
    <form action="reserva.php" method="POST">
        <label for="fecha_checkin">Fecha de Check-In:</label>
        <input type="date" name="fecha_checkin" id="fecha_checkin" required><br>

        <label for="fecha_checkout">Fecha de Check-Out:</label>
        <input type="date" name="fecha_checkout" id="fecha_checkout" required><br>

        <button type="submit" name="continuar_fechas">Continuar</button>
    </form>

    <?php if (isset($cuartos_disponibles) && count($cuartos_disponibles) > 0) { ?>
        <!-- Sección para mostrar cuartos disponibles -->
        <form action="reserva.php" method="POST">
            <h2>Cuartos Disponibles</h2>
            <?php foreach ($cuartos_disponibles as $cuarto) { ?>
                <input type="checkbox" name="cuartos[]" value="<?php echo $cuarto['id_cuarto']; ?>"> 
                Cuarto: <?php echo $cuarto['numero']; ?> - Tipo: <?php echo $cuarto['tipo']; ?> - Precio: S/. <?php echo $cuarto['precio_base']; ?><br>
            <?php } ?>
            <br>
            <button type="submit" name="reservar_cuartos">Reservar Cuartos</button>
        </form>
    <?php } elseif (isset($cuartos_disponibles)) { ?>
        <p>No hay cuartos disponibles para las fechas seleccionadas.</p>
    <?php } ?>
</body>
</html>
