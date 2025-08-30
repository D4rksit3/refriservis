<?php
// operador/mis_mantenimientos.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') header('Location: /index.php');
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

$stmt = $pdo->prepare('SELECT m.*, c.nombre as cliente, i.nombre as inventario FROM mantenimientos m LEFT JOIN clientes c ON c.id=m.cliente_id LEFT JOIN inventario i ON i.id=m.inventario_id WHERE m.operador_id = ? ORDER BY m.creado_en DESC');
$stmt->execute([$_SESSION['user_id']]);
$rows = $stmt->fetchAll();
?>
<div class="card p-3">
  <h5>Mis mantenimientos asignados</h5>
  <div class="table-responsive">
    <table class="table table-sm">
      <thead><tr><th>ID</th><th>Título</th><th>Fecha</th><th>Cliente</th><th>Estado</th><th></th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?=$r['id']?></td>
            <td><?=htmlspecialchars($r['titulo'])?></td>
            <td><?=$r['fecha']?></td>
            <td><?=htmlspecialchars($r['cliente'])?></td>
            <td><?=$r['estado']?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/mantenimientos/editar.php?id=<?=$r['id']?>">Actualizar</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
