<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
require_once __DIR__.'/../config/db.php';

// ====================
// MODO AJAX DataTables
// ====================
if(isset($_GET['ajax']) && $_GET['ajax']==1){
    $draw = $_GET['draw'] ?? 1;
    $start = $_GET['start'] ?? 0;
    $length = $_GET['length'] ?? 10;
    $search = $_GET['search']['value'] ?? '';
    $orderCol = $_GET['order'][0]['column'] ?? 0;
    $orderDir = $_GET['order'][0]['dir'] ?? 'asc';

    $columns = ['productos_id','Nombre','Categoria','Estatus','Valor_unitario'];
    $orderBy = $columns[$orderCol] ?? 'productos_id';

    $totalRecords = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();

    $where = '';
    $params = [];
    if(!empty($search)){
        $where = "WHERE Nombre LIKE ? OR Categoria LIKE ? OR Estatus LIKE ?";
        $params = ["%$search%","%$search%","%$search%"];
    }

    $filteredRecords = $pdo->prepare("SELECT COUNT(*) FROM productos $where");
    $filteredRecords->execute($params);
    $filteredRecords = $filteredRecords->fetchColumn();

    $sql = "SELECT * FROM productos $where ORDER BY $orderBy $orderDir LIMIT ?,?";
    $stmt = $pdo->prepare($sql);
    foreach($params as $i=>$v){
        $stmt->bindValue($i+1,$v,PDO::PARAM_STR);
    }
    $stmt->bindValue(count($params)+1,(int)$start,PDO::PARAM_INT);
    $stmt->bindValue(count($params)+2,(int)$length,PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($data as &$row){
        $row['acciones'] = '
        <button class="btn btn-warning btn-sm btnEditar" 
            data-id="'.$row['productos_id'].'" 
            data-nombre="'.htmlspecialchars($row['Nombre']).'" 
            data-categoria="'.htmlspecialchars($row['Categoria']).'" 
            data-estatus="'.htmlspecialchars($row['Estatus']).'" 
            data-valor="'.$row['Valor_unitario'].'">
            ✏️
        </button>
        <button class="btn btn-danger btn-sm btnEliminar" data-id="'.$row['productos_id'].'">🗑️</button>';
    }

    echo json_encode([
        "draw"=>intval($draw),
        "recordsTotal"=>$totalRecords,
        "recordsFiltered"=>$filteredRecords,
        "data"=>$data
    ]);
    exit;
}

// ====================
// CRUD (Agregar / Editar / Eliminar)
// ====================
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['accion'])){
    $accion = $_POST['accion'];

    if($accion==='agregar'){
        $sql = "INSERT INTO productos (Nombre,Categoria,Estatus,Valor_unitario) VALUES (?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['Nombre'],$_POST['Categoria'],$_POST['Estatus'],$_POST['Valor_unitario']]);
    }

    if($accion==='editar'){
        $sql = "UPDATE productos SET Nombre=?, Categoria=?, Estatus=?, Valor_unitario=? WHERE productos_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['Nombre'],$_POST['Categoria'],$_POST['Estatus'],$_POST['Valor_unitario'],$_POST['productos_id']]);
    }

    if($accion==='eliminar'){
        $sql = "DELETE FROM productos WHERE productos_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['productos_id']]);
    }
}
