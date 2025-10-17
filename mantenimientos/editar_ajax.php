<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'msg' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$id         = $_POST['id'] ?? null;
$titulo     = $_POST['titulo'] ?? '';
$fecha      = $_POST['fecha'] ?? '';
$cliente_id = $_POST['cliente_id'] ?? null;

if (!$id || !$titulo || !$fecha) {
    echo json_encode(['success' => false, 'msg' => 'Faltan datos']);
    exit;
}

// Verificar estado
$stmt = $pdo->prepare("SELECT estado FROM mantenimientos WHERE id=?");
$stmt->execute([$id]);
$estado = $stmt->fetchColumn();

if (!$estado) {
    echo json_encode(['success' => false, 'msg' => 'Registro no encontrado']);
    exit;
}
if ($estado === 'finalizado') {
    echo json_encode(['success' => false, 'msg' => 'No se puede editar un mantenimiento finalizado']);
    exit;
}

// Actualizar
$stmt = $pdo->prepare("UPDATE mantenimientos 
                       SET titulo=?, fecha=?, cliente_id=?, modificado_en=NOW(), modificado_por=? 
                       WHERE id=?");

$ok = $stmt->execute([$titulo, $fecha, $cliente_id, $_SESSION['usuario_id'], $id]);

if (!$ok) {
    $errorInfo = $stmt->errorInfo();
    echo json_encode(['success' => false, 'msg' => $errorInfo[2]]);
    exit;
}

echo json_encode(['success' => true]);
