<?php
// api/conexion.php
$host = "localhost";
$dbname = "asistencia_db";
$user = "refriservis";
$pass = "123456";

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
