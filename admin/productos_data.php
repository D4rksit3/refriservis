<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../config/db.php';

$draw = intval($_GET['draw'] ?? 1);
$start = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 10);
$searchValue = $_GET['search']['value'] ?? '';
$orderCol = $_GET['order'][0]['column'] ?? 0;
$orderDir = $_GET['order'][0]['dir'] ?? 'asc';

$columns = ['productos_id','Nombre','Categoria','Estatus','Valor_unitario'];
$orderBy = $columns[$orderCol] ?? 'productos_id';

// Total registros
$totalQuery = $pdo->query("SELECT COUNT(*) FROM productos");
$recordsTotal = $totalQuery->fetchColumn();

// Filtro
$where = "";
$params = [];
if(!empty($searchValue)){
    $where = "WHERE Nombre LIKE ? OR Categoria LIKE ? OR Estatus LIKE ? OR Valor_unitario LIKE ?";
    $params = array_fill(0,4,"%$searchValue%");
}

// Total filtrado
$stmt = $pdo->prepare("SELECT COUNT(*) FROM productos $where");
$stmt->execute($params);
$recordsFiltered = $stmt->fetchColumn();

// Datos
$sql = "SELECT * FROM productos $where ORDER BY $orderBy $orderDir LIMIT ?, ?";
$stmt = $pdo->prepare($sql);
foreach($params as $i=>$val){
    $stmt->bindValue($i+1, $val, PDO::PARAM_STR);
}
$stmt->bindValue(count($params)+1, $start, PDO::PARAM_INT);
$stmt->bindValue(count($params)+2, $length, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agregar botones HTML
foreach($data as &$row){
    $row['acciones'] = '
        <button class="btn btn-sm btn-warning editar" data-id="'.$row['productos_id'].'">✏️ Editar</button>
        <button class="btn btn-sm btn-danger eliminar" data-id="'.$row['productos_id'].'">🗑️ Eliminar</button>
    ';
}

echo json_encode([
    "draw"=>$draw,
    "recordsTotal"=>$recordsTotal,
    "recordsFiltered"=>$recordsFiltered,
    "data"=>$data
]);
