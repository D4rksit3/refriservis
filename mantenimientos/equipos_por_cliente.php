<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$idCliente = $_GET['id'] ?? 0;

if (!$idCliente) {
    echo json_encode([]);
    exit;
}

// Obtener el nombre del cliente
$stmt = $pdo->prepare("SELECT cliente FROM clientes WHERE id = ?");
$stmt->execute([$idCliente]);
$cliente = $stmt->fetchColumn();

if (!$cliente) {
    echo json_encode([]);
    exit;
}

// Buscar equipos que coincidan con el cliente (ignorando mayúsculas y espacios)
$sql = "
SELECT 
  e.id_equipo,
  e.Identificador,
  e.Nombre AS nombre_equipo,
  e.Categoria,
  e.Estatus,
  e.ubicacion
FROM refriservis.equipos e
WHERE TRIM(LOWER(CONCAT(e.Cliente, ' ', e.ubicacion))) = TRIM(LOWER(:cliente))
ORDER BY e.Nombre
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':cliente' => $cliente]);
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($equipos);
