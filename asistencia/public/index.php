<?php
// public/index.php
session_start();
if (isset($_SESSION['user_id'])) {
    // Si ya hay sesión, redirigir al dashboard o a marcar
    header('Location: marcar.php');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Login - Sistema Asistencia</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width:420px; margin-top:8vh;">
  <div class="card shadow-sm">
    <div class="card-body">
      <h4 class="card-title mb-3 text-center">Iniciar sesión</h4>
      <form id="loginForm" method="post" action="/api/auth.php">
        <input type="hidden" name="action" value="login">
        <div class="mb-2">
          <label class="form-label">DNI o Email</label>
          <input name="user" required class="form-control" placeholder="DNI o email">
        </div>
        <div class="mb-3">
          <label class="form-label">Contraseña</label>
          <input name="password" type="password" required class="form-control" placeholder="Contraseña">
        </div>
        <div id="msg" class="mb-2 text-danger"></div>
        <div class="d-grid">
          <button class="btn btn-primary">Entrar</button>
        </div>
      </form>
      <hr>
      <p class="text-muted small mb-0">Si olvidaste tu contraseña, contacta con el administrador.</p>
    </div>
  </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const form = new FormData(this);
  const resp = await fetch('/api/auth.php', { method: 'POST', body: form });
  const data = await resp.json();
  if (data.ok) {
    window.location.href = '/marcar.php';
  } else {
    document.getElementById('msg').innerText = data.msg || 'Error al iniciar sesión';
  }
});
</script>
</body>
</html>
