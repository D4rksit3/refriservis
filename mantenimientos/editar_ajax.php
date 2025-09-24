<?php
// mantenimientos/editar_ajax.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
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

// Verificar estado, no se puede editar finalizados
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

echo json_encode(['success' => $ok]);
