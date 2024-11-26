<?php
// Inicia la sesión
session_start();

// Verificar si ya existe alguna variable de sesión antes de eliminarla
if (isset($_SESSION['check_in']) || isset($_SESSION['check_out'])) {
    // Si hay datos de sesión previos, los eliminamos
    session_unset(); // Elimina todas las variables de sesión
    session_destroy(); // Destruye la sesión
}

// Datos de conexión a la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'bd_hotel';

// Conexión a la base de datos MySQL
$conn = new mysqli($host, $user, $pass, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error en la conexión a la base de datos: " . $conn->connect_error);
}

// Verifica si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Guarda las fechas de Check-in y Check-out en la sesión
    $_SESSION['check_in'] = $_POST['check_in'];
    $_SESSION['check_out'] = $_POST['check_out'];

    // Redirigir a la página hyh.php
    header("Location: hyh.php");
    exit; // Es importante llamar a exit después de header para evitar que el script continúe ejecutándose
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva de Hotel</title>
</head>
<body>
    <h1>Reserva de Hotel</h1>
    
    <form action="principal.php" method="POST">
        <label for="check_in">Fecha Check-in:</label>
        <input type="date" id="check_in" name="check_in" required>
        <br><br>
        
        <label for="check_out">Fecha Check-out:</label>
        <input type="date" id="check_out" name="check_out" required>
        <br><br>
        
        <button type="submit">Buscar</button>
    </form>

    <?php
    // Cerrar la conexión a la base de datos
    $conn->close();
    ?>
</body>
</html>
