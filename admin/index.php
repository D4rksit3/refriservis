<?php
// admin/index.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
  header('Location: /index.php'); exit;
}
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

// contar elementos
$cuentas = [
  'usuarios' => $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(),
  'clientes' => $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn(),
  'inventario' => $pdo->query('SELECT COUNT(*) FROM inventario')->fetchColumn(),
  'mantenimientos' => $pdo->query('SELECT COUNT(*) FROM mantenimientos')->fetchColumn(),
];
?>
<div class="row g-3">
  <div class="col-12">
    <div class="card p-3">
      <h5>Panel Administrativo</h5>
      <p class="text-muted small">Bienvenido, <?=htmlspecialchars($_SESSION['nombre'])?></p>
      <div class="row">
        <?php foreach($cuentas as $k=>$v): ?>
          <div class="col-6 col-md-3">
            <div class="p-3 border rounded">
              <div class="small text-muted"><?=ucfirst($k)?></div>
              <div class="h4"><?=$v?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
