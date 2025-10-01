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
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['usuario']    = $row['usuario'];
            $_SESSION['rol']        = $row['rol'];

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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      height: 100vh;
      display: flex;
    }
    .left-panel {
      flex: 1;
      background: linear-gradient(135deg, #0d6efd, #00c6ff);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      padding: 2rem;
    }
    .left-panel h1 {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 1rem;
    }
    .left-panel p {
      font-size: 1.1rem;
      max-width: 400px;
      text-align: center;
      opacity: 0.9;
    }
    .right-panel {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f8f9fa;
    }
    .login-card {
      width: 100%;
      max-width: 400px;
      background: #fff;
      border-radius: 1rem;
      padding: 2.5rem;
      box-shadow: 0 8px 30px rgba(0,0,0,0.1);
      animation: fadeIn 0.8s ease;
    }
    .login-card h2 {
      font-weight: 600;
      font-size: 1.5rem;
      margin-bottom: 1rem;
      text-align: center;
      color: #0d6efd;
    }
    .form-control {
      padding-left: 2.5rem;
      border-radius: 0.5rem;
      transition: all 0.2s ease;
    }
    .form-control:focus {
      border-color: #0d6efd;
      box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
    }
    .input-icon {
      position: absolute;
      left: 10px;
      top: 50%;
      transform: translateY(-50%);
      color: #6c757d;
    }
    .input-group {
      position: relative;
    }
    button {
      font-weight: 600;
      padding: 0.75rem;
      border-radius: 0.5rem;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    button:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(13,110,253,.3);
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px);}
      to { opacity: 1; transform: translateY(0);}
    }
  </style>
</head>
<body>
  <!-- Panel izquierdo estilo landing -->
  <div class="left-panel">
    <h1>🔧 RefriServis</h1>
    <p>Gestión inteligente de mantenimientos y operaciones. Accede a tu panel de trabajo y mantén todo bajo control.</p>
  </div>

  <!-- Panel derecho con login -->
  <div class="right-panel">
    <div class="login-card">
      <h2>Iniciar Sesión</h2>
      <p class="text-muted text-center mb-4">Accede con tu usuario</p>

      <?php if ($error): ?>
        <div class="alert alert-danger small text-center"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3 input-group">
          <span class="input-icon"><i class="bi bi-person-fill"></i></span>
          <input type="text" name="usuario" class="form-control" placeholder="Usuario" required autofocus>
        </div>
        <div class="mb-3 input-group">
          <span class="input-icon"><i class="bi bi-lock-fill"></i></span>
          <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Ingresar</button>
      </form>

      <div class="mt-4 text-center">
        <a href="#" class="small text-decoration-none text-primary">¿Olvidaste tu contraseña?</a>
      </div>
    </div>
  </div>
</body>
</html>
