<?php
include("db.php");

$documento = $_POST['documento'] ?? '';
$tipo = $_POST['tipo'] ?? '';
$lat = $_POST['latitud'] ?? '';
$lng = $_POST['longitud'] ?? '';

if (!$documento || !$tipo) {
  die("Datos incompletos");
}

// Buscar usuario
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE documento = ?");
$stmt->execute([$documento]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  // Crear usuario automáticamente si no existe
  $nombre = "Empleado " . $documento;
  $pdo->prepare("INSERT INTO usuarios (nombre, documento) VALUES (?, ?)")->execute([$nombre, $documento]);
  $user_id = $pdo->lastInsertId();
} else {
  $user_id = $user['id'];
}

// Guardar asistencia
$stmt = $pdo->prepare("INSERT INTO asistencias (usuario_id, tipo, latitud, longitud) VALUES (?, ?, ?, ?)");
$stmt->execute([$user_id, $tipo, $lat, $lng]);

echo "<script>alert('Asistencia registrada correctamente'); window.location='index.php';</script>";
?>
