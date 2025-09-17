<?php
// login.php
session_start();
require_once __DIR__ . '/config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($usuario === '' || $password === '') {
        $error = 'Usuario y contraseña requeridos.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? LIMIT 1");
        $stmt->execute([$usuario]);
        $row = $stmt->fetch();

        if ($row && password_verify($password, $row['password'])) {
            // Guardamos datos en sesión
            $_SESSION['usuario_id'] = $row['id'];       // ID único
            $_SESSION['usuario']    = $row['usuario']; // nombre de usuario
            $_SESSION['rol']        = $row['rol'];     // rol (admin, digitador, operador)

            // Redirigir según rol
            if ($row['rol'] === 'admin') {
                header('Location: /admin/index.php');
            } elseif ($row['rol'] === 'digitador') {
                header('Location: /digitador/index.php');
            } elseif ($row['rol'] === 'operador') {
                header('Location: /operador/index.php');
            } else {
                $error = 'Rol no reconocido.';
            }
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>RefriServis - Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f4f7fa;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
    }
    .login-card {
      width: 100%;
      max-width: 380px;
      padding: 2rem;
      border-radius: 1rem;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      background: #fff;
    }
    .brand {
      font-weight: bold;
      font-size: 1.5rem;
      color: #0d6efd;
      margin-bottom: 1rem;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="brand">RefriServis</div>
    <?php if ($error): ?>
      <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Usuario</label>
        <input type="text" name="usuario" class="form-control" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Ingresar</button>
    </form>
  </div>
</body>
</html>
