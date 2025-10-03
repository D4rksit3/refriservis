<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../config/db.php';

$draw = intval($_GET['draw'] ?? 0);
$start = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 10);
$searchValue = $_GET['search']['value'] ?? '';
$orderCol = $_GET['order'][0]['column'] ?? 0;
$orderDir = $_GET['order'][0]['dir'] ?? 'asc';

$columns = ['id_equipo','Identificador','Nombre','marca','modelo','ubicacion','voltaje','Descripcion','Cliente','Categoria','Estatus','Fecha_validad'];
$orderBy = $columns[$orderCol] ?? 'id_equipo';

// total
$totalQuery = $pdo->query("SELECT COUNT(*) FROM equipos");
$recordsTotal = (int)$totalQuery->fetchColumn();

// where / params
$where = "";
$params = [];
if($searchValue !== ''){
    $where = "WHERE Nombre LIKE ? OR Descripcion LIKE ? OR Cliente LIKE ? OR Categoria LIKE ? OR Estatus LIKE ?";
    $params = array_fill(0,5, "%$searchValue%");
}

// filtered count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM equipos $where");
$stmt->execute($params);
$recordsFiltered = (int)$stmt->fetchColumn();

// data
$sql = "SELECT * FROM equipos $where ORDER BY $orderBy $orderDir LIMIT ?, ?";
$stmt = $pdo->prepare($sql);
foreach($params as $i=>$val){ $stmt->bindValue($i+1, $val, PDO::PARAM_STR); }
$stmt->bindValue(count($params)+1, (int)$start, PDO::PARAM_INT);
$stmt->bindValue(count($params)+2, (int)$length, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// add action buttons (use data-id attributes)
foreach($data as &$row){
    $id = $row['id_equipo'];
    $row['acciones'] = '<button type="button" class="btn btn-sm btn-warning editar-equipo" data-id="'.$id.'">✏️</button> <button type="button" class="btn btn-sm btn-danger eliminar-equipo" data-id="'.$id.'">🗑️</button>';
}

echo json_encode([
    "draw" => $draw,
    "recordsTotal" => $recordsTotal,
    "recordsFiltered" => $recordsFiltered,
    "data" => $data
]);
