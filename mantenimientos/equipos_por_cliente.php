<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once '../config/db.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Falta parámetro id']);
    exit;
}

$id_cliente = intval($_GET['id']);

try {
    // 1️⃣ Buscar el nombre exacto del cliente por ID
    $stmt = $pdo->prepare("SELECT cliente FROM clientes WHERE id = ?");
    $stmt->execute([$id_cliente]);
    $cliente_nombre = $stmt->fetchColumn();

    if (!$cliente_nombre) {
        echo json_encode([]);
        exit;
    }

    // 2️⃣ Buscar equipos cuyo campo Cliente (sin ubicación) coincida con el nombre
    $stmt = $pdo->prepare("
        SELECT 
            id_equipo,
            Identificador,
            Nombre AS nombre_equipo,
            Categoria,
            Estatus,
            ubicacion
        FROM equipos
        WHERE TRIM(LOWER(Cliente)) = TRIM(LOWER(?))
        ORDER BY Nombre
    ");
    $stmt->execute([$cliente_nombre]);
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($equipos);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
