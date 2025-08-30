<?php
// digitador/index.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'digitador') header('Location: /index.php');
require_once __DIR__.'/../includes/header.php';
?>
<div class="card p-3">
  <h5>Panel Digitador</h5>
  <p class="small text-muted">Desde aquí puedes crear mantenimientos y subir CSV (si usas import masivo).</p>
  <div class="row">
    <div class="col-6"><a class="btn btn-primary w-100" href="/mantenimientos/crear.php">Crear mantenimiento</a></div>
    <div class="col-6"><a class="btn btn-outline-primary w-100" href="/digitador/subir_mantenimiento.php">Importar CSV</a></div>
  </div>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
