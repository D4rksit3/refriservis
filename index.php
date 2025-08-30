<?php
// index.php (login)
session_start();
require_once __DIR__.'/config/db.php';

if (isset($_SESSION['usuario'])) {
    // redirigir por rol
    switch ($_SESSION['rol']) {
      case 'admin': header('Location: /admin/index.php'); exit;
      case 'digitador': header('Location: /digitador/index.php'); exit;
      case 'operador': header('Location: /operador/index.php'); exit;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') $error = 'Completa usuario y contraseña.';
    else {
        $stmt = $pdo->prepare('SELECT id, nombre, usuario, password, rol FROM usuarios WHERE usuario = ? LIMIT 1');
        $stmt->execute([$usuario]);
        $u = $stmt->fetch();
        if ($u && password_verify($password, $u['password'])) {
            $_SESSION['usuario'] = $u['usuario'];
            $_SESSION['nombre'] = $u['nombre'];
            $_SESSION['rol'] = $u['rol'];
            $_SESSION['user_id'] = $u['id'];
            // redirigir según rol
            if ($u['rol'] === 'admin') header('Location: /admin/index.php');
            if ($u['rol'] === 'digitador') header('Location: /digitador/index.php');
            if ($u['rol'] === 'operador') header('Location: /operador/index.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ingreso — RefriServis</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/estilos.css">
</head>
<body class="d-flex align-items-center" style="min-height:100vh">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-6">
        <div class="card p-4">
          <div class="text-center mb-3">
            <img src="/assets/img/logo.svg" height="56" alt="RefriServis">
            <h4 class="mt-2">Sistema de Mantenimiento</h4>
            <p class="text-muted small">Ingresa con tu cuenta</p>
          </div>

          <?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>

          <form method="post" class="row g-2">
            <div class="col-12"><label class="form-label">Usuario</label>
              <input class="form-control" name="usuario" required></div>
            <div class="col-12"><label class="form-label">Contraseña</label>
              <input type="password" class="form-control" name="password" required></div>

            <div class="col-12 text-end">
              <button class="btn btn-primary">Ingresar</button>
            </div>
          </form>

          <div class="mt-3 small text-muted">
            Usuario de prueba admin: <strong>admin</strong> / Contraseña: <strong>Admin123!</strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
