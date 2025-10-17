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

// ============================
// Recibir datos del formulario
// ============================
$id           = $_POST['id'] ?? null;
$titulo       = trim($_POST['titulo'] ?? '');
$descripcion  = trim($_POST['descripcion'] ?? '');
$fecha        = $_POST['fecha'] ?? '';
$cliente_id   = $_POST['cliente_id'] ?: null;
$operador_id  = $_POST['operador_id'] ?: null;
$categoria    = $_POST['categoria'] ?? '';
$equipos      = $_POST['equipos'] ?? []; // array múltiple

// Validaciones básicas
if (!$id || !$titulo || !$fecha) {
    echo json_encode(['success' => false, 'msg' => 'Faltan datos obligatorios']);
    exit;
}

// ============================
// Verificar estado del mantenimiento
// ============================
$stmt = $pdo->prepare("SELECT estado FROM mantenimientos WHERE id = ?");
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

// ============================
// Actualizar registro principal
// ============================
$sql = "UPDATE mantenimientos 
        SET titulo = ?, 
            descripcion = ?, 
            fecha = ?, 
            cliente_id = ?, 
            operador_id = ?, 
            categoria = ?, 
            modificado_en = NOW(), 
            modificado_por = ?
        WHERE id = ?";

$stmt = $pdo->prepare($sql);
$ok = $stmt->execute([
    $titulo,
    $descripcion,
    $fecha,
    $cliente_id,
    $operador_id,
    $categoria,
    $_SESSION['usuario_id'],
    $id
]);

if (!$ok) {
    $error = $stmt->errorInfo();
    echo json_encode(['success' => false, 'msg' => 'Error al actualizar: '.$error[2]]);
    exit;
}

// ============================
// Guardar equipos (máx 7)
// ============================
$equipos = array_slice($equipos, 0, 7); // solo 7 equipos
$updateCampos = [];
$params = [];

for ($i = 1; $i <= 7; $i++) {
    $campo = "equipo$i";
    $valor = isset($equipos[$i-1]) ? $equipos[$i-1] : null;
    $updateCampos[] = "$campo = ?";
    $params[] = $valor;
}

$params[] = $id;

$stmtEquipos = $pdo->prepare("UPDATE mantenimientos SET ".implode(',', $updateCampos)." WHERE id = ?");
$stmtEquipos->execute($params);

// ============================
// Respuesta final
// ============================
echo json_encode(['success' => true, 'msg' => 'Mantenimiento actualizado correctamente']);
