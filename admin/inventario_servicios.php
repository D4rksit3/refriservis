<?php
require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->query("SELECT id, nombre, precio FROM inventario_servicios ORDER BY id DESC");
$servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="card shadow-sm">
  <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0">🛠️ Inventario - Servicios</h5>
    <a href="inventario_servicios_nuevo.php" class="btn btn-light btn-sm">➕ Nuevo Servicio</a>
  </div>
  <div class="card-body">
    <table class="table table-hover table-striped align-middle">
      <thead class="table-info">
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Precio</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($servicios as $s): ?>
          <tr>
            <td><?=$s['id']?></td>
            <td><?=htmlspecialchars($s['nombre'])?></td>
            <td><?=$s['precio']?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
