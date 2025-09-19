<?php
require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->query("SELECT id, nombre, categoria, stock FROM inventario_productos ORDER BY id DESC");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="card shadow-sm">
  <div class="card-header bg-warning d-flex justify-content-between align-items-center">
    <h5 class="mb-0">📦 Inventario - Productos</h5>
    <a href="inventario_productos_nuevo.php" class="btn btn-dark btn-sm">➕ Nuevo Producto</a>
  </div>
  <div class="card-body">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-warning">
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Categoría</th>
          <th>Stock</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($productos as $p): ?>
          <tr>
            <td><?=$p['id']?></td>
            <td><?=htmlspecialchars($p['nombre'])?></td>
            <td><?=htmlspecialchars($p['categoria'])?></td>
            <td><?=$p['stock']?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
