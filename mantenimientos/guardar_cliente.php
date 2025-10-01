<?php
require_once __DIR__.'/../config/db.php';

$cliente = trim($_POST['cliente'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$email = trim($_POST['email'] ?? '');
$responsable = trim($_POST['responsable'] ?? '');
$estatus = 1;

if($cliente=='' || $direccion=='' || $telefono==''){
    echo json_encode(['success'=>false,'error'=>'Campos obligatorios faltantes']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO clientes (cliente,direccion,telefono,email,responsable,estatus) VALUES (?,?,?,?,?,?)");
$stmt->execute([$cliente,$direccion,$telefono,$email,$responsable,$estatus]);

$id = $pdo->lastInsertId();
echo json_encode([
    'success'=>true,
    'id'=>$id,
    'text'=>$cliente.' - '.$direccion.' - '.$telefono
]);
