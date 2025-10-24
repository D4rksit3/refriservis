<?php
header('Content-Type: application/json');
require_once __DIR__.'/../config/db.php';// o la ruta correcta a tu archivo de conexión

if (!isset($_GET['id'])) {
    echo json_encode([]);
    exit;
}

$id_cliente = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("
        SELECT 
            e.id_equipo,
            e.Identificador,
            e.Nombre AS nombre_equipo,
            e.marca,
            e.modelo,
            e.ubicacion,
            e.voltaje,
            e.Descripcion,
            e.Categoria,
            e.Estatus
        FROM refriservis.equipos e
        INNER JOIN refriservis.clientes c 
            ON TRIM(LOWER(c.cliente)) = TRIM(LOWER(e.Cliente))
        WHERE c.id = :id
        ORDER BY e.Nombre
    ");
    $stmt->execute([':id' => $id_cliente]);
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($equipos);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
