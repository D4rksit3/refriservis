<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

// Validar ID recibido
$id = $_GET['id'] ?? null;
if (!$id || !ctype_digit($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

// Consultar el equipo desde la tabla `equipos`
$stmt = $pdo->prepare("
    SELECT 
        id_equipo,
        Identificador,
        marca,
        modelo,
        ubicacion,
        voltaje
    FROM equipos
    WHERE id_equipo = ?
");
$stmt->execute([$id]);
$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    http_response_code(404);
    echo json_encode(['error' => 'Equipo no encontrado']);
    exit;
}

// Responder en JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($equipo);
