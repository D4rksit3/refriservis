<?php
require_once __DIR__.'/../config/db.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? null;
if(!$id){ echo json_encode(['success'=>false]); exit; }

$stmt = $pdo->prepare("SELECT * FROM mantenimientos WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$row){ echo json_encode(['success'=>false]); exit; }

$equipos = [];
for($i=1;$i<=7;$i++){
  if($row['equipo'.$i]) $equipos[] = $row['equipo'.$i];
}

echo json_encode([
  'success'=>true,
  'id'=>$row['id'],
  'titulo'=>$row['titulo'],
  'descripcion'=>$row['descripcion'],
  'fecha'=>$row['fecha'],
  'cliente_id'=>$row['cliente_id'],
  'operador_id'=>$row['operador_id'],
  'categoria'=>$row['categoria'],
  'equipos'=>$equipos
]);
