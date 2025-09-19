<?php
require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->query("SELECT id, nombre, modelo, serie FROM inventario_equipos ORDER BY id DESC");
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="card shadow-sm">
  <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0">💻 Inventario - Equipos</h5>
    <a href="inventario_equipos_nuevo.php" class="btn btn-light btn-sm">➕ Nuevo Equipo</a>
  </div>
  <div class="card-body">
    <table class="table table-bordered table-striped align-middle">
      <thead class="table-success">
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Modelo</th>
          <th>Serie</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($equipos as $e): ?>
          <tr>
            <td><?=$e['id']?></td>
            <td><?=htmlspecialchars($e['nombre'])?></td>
            <td><?=htmlspecialchars($e['modelo'])?></td>
            <td><?=htmlspecialchars($e['serie'])?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
