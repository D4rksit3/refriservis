<?php
// api/usuarios.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/conexion.php';

// solamente admin o supervisor pueden usar este endpoint
$role = $_SESSION['role'] ?? null;
if (!$role || !in_array($role, ['admin','supervisor'])) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'msg'=>'No autorizado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;

function json($a){ echo json_encode($a); exit; }

if ($action === 'listar') {
    $stmt = $pdo->query("SELECT u.id, u.nombre, u.dni, u.email, u.rol, u.activo, u.horario_inicio, u.horario_fin, s.nombre as sede
                         FROM usuarios u
                         LEFT JOIN sedes s ON u.sede_id = s.id
                         ORDER BY u.nombre");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    json(['ok'=>true,'rows'=>$rows]);
}

if ($action === 'crear') {
    $dni = trim($_POST['dni'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? null);
    $rol = $_POST['rol'] ?? 'empleado';
    $sede_id = $_POST['sede_id'] ?: null;
    $horario_inicio = $_POST['horario_inicio'] ?: '08:00:00';
    $horario_fin = $_POST['horario_fin'] ?: '17:00:00';
    $password = $_POST['password'] ?? null;

    if (!$dni || !$nombre) json(['ok'=>false,'msg'=>'DNI y nombre requeridos']);

    // verificar dni unico
    $chk = $pdo->prepare("SELECT id FROM usuarios WHERE dni = ? LIMIT 1");
    $chk->execute([$dni]);
    if ($chk->fetch()) json(['ok'=>false,'msg'=>'DNI ya registrado']);

    $hash = $password ? password_hash($password, PASSWORD_DEFAULT) : null;

    $stmt = $pdo->prepare("INSERT INTO usuarios (dni, nombre, email, rol, sede_id, horario_inicio, horario_fin, password, creado_at)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$dni, $nombre, $email, $rol, $sede_id, $horario_inicio, $horario_fin, $hash]);
    json(['ok'=>true,'msg'=>'Usuario creado']);
}

if ($action === 'editar') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) json(['ok'=>false,'msg'=>'ID requerido']);

    $dni = trim($_POST['dni'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? null);
    $rol = $_POST['rol'] ?? 'empleado';
    $sede_id = $_POST['sede_id'] ?: null;
    $horario_inicio = $_POST['horario_inicio'] ?: '08:00:00';
    $horario_fin = $_POST['horario_fin'] ?: '17:00:00';
    $password = $_POST['password'] ?? null;

    if ($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET dni=?, nombre=?, email=?, rol=?, sede_id=?, horario_inicio=?, horario_fin=?, password=? WHERE id=?");
        $stmt->execute([$dni, $nombre, $email, $rol, $sede_id, $horario_inicio, $horario_fin, $hash, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET dni=?, nombre=?, email=?, rol=?, sede_id=?, horario_inicio=?, horario_fin=? WHERE id=?");
        $stmt->execute([$dni, $nombre, $email, $rol, $sede_id, $horario_inicio, $horario_fin, $id]);
    }
    json(['ok'=>true,'msg'=>'Usuario actualizado']);
}

if ($action === 'activar') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) json(['ok'=>false,'msg'=>'ID requerido']);
    $pdo->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?")->execute([$id]);
    json(['ok'=>true]);
}

if ($action === 'desactivar') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) json(['ok'=>false,'msg'=>'ID requerido']);
    $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?")->execute([$id]);
    json(['ok'=>true]);
}

json(['ok'=>false,'msg'=>'Acción inválida']);
