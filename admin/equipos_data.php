<?php
// equipos_data.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../config/db.php';

// Traer todos los equipos (puedes hacer filtros después si quieres)
$stmt = $pdo->query("
    SELECT 
        e.id_equipo,
        e.Nombre,
        e.Descripcion,
        c.Nombre AS Cliente,
        cat.Nombre AS Categoria,
        e.Estatus,
        e.Fecha_validad
    FROM equipos e
    LEFT JOIN clientes c ON e.id_cliente = c.id_cliente
    LEFT JOIN categorias cat ON e.id_categoria = cat.id_categoria
");

$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agregar columna de acciones
foreach ($equipos as &$eq) {
    $eq['acciones'] = '
        <button class="btn btn-sm btn-warning editar" data-id="'.$eq['id_equipo'].'">✏️ Editar</button>
        <button class="btn btn-sm btn-danger eliminar" data-id="'.$eq['id_equipo'].'">🗑️ Eliminar</button>
    ';
}

// DataTables espera esto:
echo json_encode([
    "data" => $equipos
]);
