<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// equipos_data.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../config/db.php';

// Traer todos los equipos (puedes hacer filtros después si quieres)
$stmt = $pdo->query("
    SELECT 
        *
    FROM equipos
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
