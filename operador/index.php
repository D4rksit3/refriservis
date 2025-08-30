<?php
// operador/index.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') header('Location: /index.php');
require_once __DIR__.'/../includes/header.php';
?>
<div class="card p-3">
  <h5>Panel Operador</h5>
  <p class="small text-muted">Tareas asignadas y estado</p>
  <a class="btn btn-primary" href="/operador/mis_mantenimientos.php">Ver mis tareas</a>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
