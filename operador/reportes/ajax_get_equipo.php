<?php
require_once __DIR__ . '/../../config/db.php';

$codigo = $_GET['codigo'] ?? '';
if(!$codigo){ echo json_encode([]); exit; }

$stmt = $pdo->prepare("
  SELECT 
    inv.nombre AS tipo,
    eq.nombre
    eq.marca,
    eq.modelo,
    eq.ubicacion,
    inv.gas
  FROM equipos eq
  LEFT JOIN inventario inv ON eq.Identificador = inv.codigo
  WHERE eq.Identificador = ?
  LIMIT 1
");
$stmt->execute([$codigo]);
$eq = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($eq ?: []);
