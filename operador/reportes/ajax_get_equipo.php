<?php
require_once __DIR__ . '/../../config/db.php';

$codigo = $_GET['codigo'] ?? '';
if(!$codigo){ echo json_encode([]); exit; }

$stmt = $pdo->prepare("
  SELECT 
    inv.nombre AS tipo,
    eq.Nombre
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
 
/* 

require_once __DIR__ . '/../../config/db.php';

$id_equipo = $_GET['id_equipo'] ?? '';
if (!$id_equipo) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            eq.id_equipo,
            eq.Nombre AS nombre,
            eq.Marca AS marca,
            eq.Modelo AS modelo,
            eq.Ubicacion AS ubicacion,
            eq.Voltaje AS voltaje,
            inv.nombre AS tipo,
            inv.gas AS gas
        FROM equipos eq
        LEFT JOIN inventario inv ON eq.Identificador = inv.codigo
        WHERE eq.id_equipo = ?
        LIMIT 1
    ");
    $stmt->execute([$id_equipo]);
    $eq = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($eq) {
        echo json_encode(['success' => true, 'data' => $eq]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Equipo no encontrado']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
 */