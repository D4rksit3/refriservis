<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// api/auth.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/conexion.php';

$action = $_POST['action'] ?? $_GET['action'] ?? null;

function json($arr) { echo json_encode($arr); exit; }

if ($action === 'login') {
    $userInput = trim($_POST['user'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$userInput || !$password) json(['ok'=>false,'msg'=>'Credenciales incompletas']);

    // buscar por dni o email
    $stmt = $pdo->prepare("SELECT id, nombre, dni, email, password, rol, activo FROM usuarios WHERE (dni = ? OR email = ?) LIMIT 1");
    $stmt->execute([$userInput, $userInput]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$u) json(['ok'=>false,'msg'=>'Usuario no encontrado']);
    if (!$u['activo']) json(['ok'=>false,'msg'=>'Usuario deshabilitado.']);

    if (!isset($u['password']) || !$u['password']) {
        json(['ok'=>false,'msg'=>'Usuario no tiene contraseña establecida. Contacte al admin.']);
    }

    if (password_verify($password, $u['password'])) {
        // Login OK: establecer sesión segura
        session_regenerate_id(true);
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['user_name'] = $u['nombre'];
        $_SESSION['role'] = $u['rol'];
        // actualizar último login
        $upd = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
        $upd->execute([$u['id']]);
        json(['ok'=>true, 'msg'=>'Login correcto']);
    } else {
        json(['ok'=>false,'msg'=>'Contraseña incorrecta']);
    }
} elseif ($action === 'logout') {
    // destruir sesión
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        setcookie(session_name(), '', time() - 42000, '/');
    }
    session_destroy();
    json(['ok'=>true,'msg'=>'Sesión cerrada']);
} elseif ($action === 'check') {
    if (isset($_SESSION['user_id'])) {
        json(['ok'=>true,'user'=>['id'=>$_SESSION['user_id'],'name'=>$_SESSION['user_name'],'role'=>$_SESSION['role']]]);
    } else {
        json(['ok'=>false]);
    }
} else {
    // si es GET sin action, devolver info básica (opcional)
    json(['ok'=>false,'msg'=>'Acción no definida']);
}

