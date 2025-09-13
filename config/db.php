<?php
// config/db.php
// Ajusta estos valores según tu entorno
$db_host = 'localhost';
$db_name = 'refriservis';
$db_user = 'refriservis';
$db_pass = '123456';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (Exception $e) {
    // en producción sustituir por log en archivo
    die('Error DB: ' . $e->getMessage());
}

