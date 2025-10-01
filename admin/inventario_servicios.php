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

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>


<?php include __DIR__ . '/../includes/footer.php'; ?>
