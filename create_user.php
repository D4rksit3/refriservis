<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__.'/config/db.php';

try {
    $nombre = "Administrador";
    $usuario = "jroque";
    $rol = "admin";
    $passPlano = "123456";
    $hash = password_hash($passPlano, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, usuario, password, rol) VALUES (?,?,?,?)");
    $stmt->execute([$nombre, $usuario, $hash, $rol]);

    echo "✅ Usuario admin creado con contraseña: Admin123!";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
