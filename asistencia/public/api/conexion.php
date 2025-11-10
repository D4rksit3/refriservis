<?php
// api/conexion.php
$host = "localhost";
$dbname = "db_asistencia_ext";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo "Error de conexión: " . $e->getMessage();
    exit;
}
?>
