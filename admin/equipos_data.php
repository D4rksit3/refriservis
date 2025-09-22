<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../config/db.php';

$draw = intval($_GET['draw'] ?? 0);
$start = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 10);
$searchValue = $_GET['search']['value'] ?? '';
$orderCol = $_GET['order'][0]['column'] ?? 0;
$orderDir = $_GET['order'][0]['dir'] ?? 'asc';

$columns = ['id_equipo','Nombre','Descripcion','Cliente','Categoria','Estatus','Fecha_validad'];
$orderBy = $columns[$orderCol] ?? 'id_equipo';

// Total registros
$totalQuery = $pdo->query("SELECT COUNT(*) FROM equipos");
$recordsTotal = $totalQuery->fetchColumn();

// Filtro
$where = "";
$params = [];
if(!empty($searchValue)){
    $where = "WHERE Nombre LIKE ? OR Descripcion LIKE ? OR Cliente LIKE ? OR Categoria LIKE ? OR Estatus LIKE ?";
    $params = array_fill(0,5,"%$searchValue%");
}

// Total filtrado
$stmt = $pdo->prepare("SELECT COUNT(*) FROM equipos $where");
$stmt->execute($params);
$recordsFiltered = $stmt->fetchColumn();

// Datos paginados
$sql = "SELECT * FROM equipos $where ORDER BY $orderBy $orderDir LIMIT ?, ?";
$stmt = $pdo->prepare($sql);
foreach($params as $i=>$val){
    $stmt->bindValue($i+1,$val,PDO::PARAM_STR);
}
$stmt->bindValue(count($params)+1,$start,PDO::PARAM_INT);
$stmt->bindValue(count($params)+2,$length,PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agregar botones
foreach($data as &$row){
    $row['acciones'] = '<button type="button" class="editar btn btn-warning btn-sm" data-id="'.$row['id_equipo'].'">✏️ Editar</button>
                        <button type="button" class="eliminar btn btn-danger btn-sm" data-id="'.$row['id_equipo'].'">🗑️ Eliminar</button>';
}

echo json_encode([
    "draw"=>$draw,
    "recordsTotal"=>$recordsTotal,
    "recordsFiltered"=>$recordsFiltered,
    "data"=>$data
]);
