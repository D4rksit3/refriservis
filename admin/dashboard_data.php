<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    exit('Acceso denegado');
}

require_once __DIR__.'/../config/db.php';

$periodo = $_GET['periodo'] ?? 'semana';
$tecnico = $_GET['tecnico'] ?? 'todos';

$hoy = date('Y-m-d');
switch($periodo){
    case 'dia': $inicio=$hoy; break;
    case 'semana': $inicio=date('Y-m-d', strtotime('monday this week')); break;
    case 'mes': $inicio=date('Y-m-01'); break;
    default: $inicio=$hoy;
}

$filtro_tecnico = ($tecnico !== 'todos') ? "AND operador_id = :tecnico" : "";

// 1️⃣ Estado de mantenimientos
$sql = "SELECT estado, COUNT(*) as total FROM mantenimientos WHERE fecha BETWEEN :inicio AND :hoy $filtro_tecnico GROUP BY estado";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':inicio',$inicio);
$stmt->bindValue(':hoy',$hoy);
if($tecnico!=='todos') $stmt->bindValue(':tecnico',$tecnico,PDO::PARAM_INT);
$stmt->execute();

$estado=['pendiente'=>0,'en_proceso'=>0,'finalizado'=>0];
while($r=$stmt->fetch()){ $estado[$r['estado']]=$r['total']; }

// 2️⃣ Distribución por técnico
$sql2="SELECT u.nombre, COUNT(*) as total FROM mantenimientos m JOIN usuarios u ON m.operador_id=u.id WHERE m.estado='finalizado' AND fecha BETWEEN :inicio AND :hoy GROUP BY m.operador_id";
$stmt2=$pdo->prepare($sql2);
$stmt2->bindValue(':inicio',$inicio);
$stmt2->bindValue(':hoy',$hoy);
$stmt2->execute();
$tecnicos=$stmt2->fetchAll();

// 3️⃣ Evolución por día
$fechas = [];
$creados = [];
$finalizados = [];
$start = new DateTime($inicio);
$end = new DateTime($hoy);
for($d=$start; $d<=$end; $d->modify('+1 day')){
    $day = $d->format('Y-m-d');
    $fechas[] = $day;

    $stmt=$pdo->prepare("SELECT COUNT(*) FROM mantenimientos WHERE fecha=:fecha $filtro_tecnico");
    $stmt->bindValue(':fecha',$day);
    if($tecnico!=='todos') $stmt->bindValue(':tecnico',$tecnico,PDO::PARAM_INT);
    $stmt->execute();
    $creados[]=(int)$stmt->fetchColumn();

    $stmt=$pdo->prepare("SELECT COUNT(*) FROM mantenimientos WHERE fecha=:fecha AND estado='finalizado' $filtro_tecnico");
    $stmt->bindValue(':fecha',$day);
    if($tecnico!=='todos') $stmt->bindValue(':tecnico',$tecnico,PDO::PARAM_INT);
    $stmt->execute();
    $finalizados[]=(int)$stmt->fetchColumn();
}

// 4️⃣ Estado por categoría
$stmt=$pdo->prepare("SELECT categoria, estado, COUNT(*) as total FROM mantenimientos WHERE fecha BETWEEN :inicio AND :hoy $filtro_tecnico GROUP BY categoria,estado");
$stmt->bindValue(':inicio',$inicio);
$stmt->bindValue(':hoy',$hoy);
if($tecnico!=='todos') $stmt->bindValue(':tecnico',$tecnico,PDO::PARAM_INT);
$stmt->execute();

$categorias=[];
foreach($stmt->fetchAll() as $r){
    $cat=$r['categoria'];
    if(!isset($categorias[$cat])) $categorias[$cat]=['pendiente'=>0,'en_proceso'=>0,'finalizado'=>0];
    $categorias[$cat][$r['estado']]=$r['total'];
}

$categoria_labels=array_keys($categorias);
$categoria_pendiente=array_map(fn($c)=>$c['pendiente'],$categorias);
$categoria_en_proceso=array_map(fn($c)=>$c['en_proceso'],$categorias);
$categoria_finalizado=array_map(fn($c)=>$c['finalizado'],$categorias);

// 5️⃣ Top clientes
$stmt=$pdo->prepare("SELECT c.cliente, COUNT(*) as total FROM mantenimientos m JOIN clientes c ON m.cliente_id=c.id WHERE fecha BETWEEN :inicio AND :hoy $filtro_tecnico GROUP BY c.id ORDER BY total DESC LIMIT 10");
$stmt->bindValue(':inicio',$inicio);
$stmt->bindValue(':hoy',$hoy);
if($tecnico!=='todos') $stmt->bindValue(':tecnico',$tecnico,PDO::PARAM_INT);
$stmt->execute();
$clientes=$stmt->fetchAll();
$clientes_labels=array_map(fn($c)=>$c['cliente'],$clientes);
$clientes_valores=array_map(fn($c)=>(int)$c['total'],$clientes);

// 6️⃣ Mantenimientos vs reportes
$stmt=$pdo->prepare("SELECT COUNT(*) FROM mantenimientos WHERE fecha BETWEEN :inicio AND :hoy $filtro_tecnico");
$stmt->bindValue(':inicio',$inicio);
$stmt->bindValue(':hoy',$hoy);
if($tecnico!=='todos') $stmt->bindValue(':tecnico',$tecnico,PDO::PARAM_INT);
$stmt->execute();
$totalMantenimientos=(int)$stmt->fetchColumn();

$stmt=$pdo->prepare("SELECT COUNT(*) FROM mantenimientos WHERE fecha BETWEEN :inicio AND :hoy AND reporte_generado=1 $filtro_tecnico");
$stmt->bindValue(':inicio',$inicio);
$stmt->bindValue(':hoy',$hoy);
if($tecnico!=='todos') $stmt->bindValue(':tecnico',$tecnico,PDO::PARAM_INT);
$stmt->execute();
$totalReportes=(int)$stmt->fetchColumn();

// Respuesta JSON
echo json_encode([
    'estado'=>$estado,
    'tecnicos'=>$tecnicos,
    'linea'=>['fechas'=>$fechas,'creados'=>$creados,'finalizados'=>$finalizados],
    'categoria'=>[
        'labels'=>$categoria_labels,
        'pendiente'=>$categoria_pendiente,
        'en_proceso'=>$categoria_en_proceso,
        'finalizado'=>$categoria_finalizado
    ],
    'clientes'=>['labels'=>$clientes_labels,'valores'=>$clientes_valores],
    'reportes'=>['mantenimientos'=>$totalMantenimientos,'generados'=>$totalReportes]
]);
