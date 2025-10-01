<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    http_response_code(403);
    exit('Acceso denegado');
}

require_once __DIR__.'/../config/db.php';

$periodo = $_GET['periodo'] ?? 'semana';
$tecnico = $_GET['tecnico'] ?? 'todos';

// Calcular rango de fechas
$hoy = date('Y-m-d');
switch($periodo){
    case 'dia':
        $inicio = $hoy;
        break;
    case 'semana':
        $inicio = date('Y-m-d', strtotime('monday this week'));
        break;
    case 'mes':
        $inicio = date('Y-m-01');
        break;
    default:
        $inicio = $hoy;
}

// Filtro de técnico
$filtro_tecnico = ($tecnico !== 'todos') ? "AND operador_id = :tecnico" : "";

// Contar pendientes y finalizados
$sql = "SELECT estado, COUNT(*) as total 
        FROM mantenimientos 
        WHERE fecha BETWEEN :inicio AND :hoy $filtro_tecnico 
        GROUP BY estado";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':inicio', $inicio);
$stmt->bindValue(':hoy', $hoy);
if($tecnico !== 'todos') $stmt->bindValue(':tecnico', $tecnico, PDO::PARAM_INT);
$stmt->execute();

$pendientes = 0;
$finalizados = 0;
while($row = $stmt->fetch()){
    if($row['estado']=='pendiente') $pendientes = (int)$row['total'];
    if($row['estado']=='finalizado') $finalizados = (int)$row['total'];
}

// Conteo por técnico (solo finalizados)
$sql2 = "SELECT u.nombre, COUNT(*) as total 
         FROM mantenimientos m
         JOIN usuarios u ON m.operador_id = u.id
         WHERE m.estado='finalizado' AND fecha BETWEEN :inicio AND :hoy
         GROUP BY m.operador_id";
$stmt2 = $pdo->prepare($sql2);
$stmt2->bindValue(':inicio', $inicio);
$stmt2->bindValue(':hoy', $hoy);
$stmt2->execute();
$tecnicos = $stmt2->fetchAll();

echo json_encode([
    'pendientes' => $pendientes,
    'finalizados' => $finalizados,
    'tecnicos' => $tecnicos
]);
