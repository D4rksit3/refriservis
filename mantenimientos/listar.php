<?php
// mantenimientos/listar.php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: /index.php'); exit; }
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

// Si admin ve todos; digitador ve los suyos; operador puede ver asignados
$where = '1=1';
$params = [];
if ($_SESSION['rol'] === 'digitador') {
  $where = 'digitador_id = ?';
  $params[] = $_SESSION['user_id'];
} elseif ($_SESSION['rol'] === 'operador') {
  $where = 'operador_id = ?';
  $params[] = $_SESSION['user_id'];
}

$stmt = $pdo->prepare("SELECT m.*, c.nombre as cliente, i.nombre as inventario, u.nombre as digitador, o.nombre as operador
  FROM mantenimientos m
  LEFT JOIN clientes c ON c.id = m.cliente_id
  LEFT JOIN inventario i ON i.id = m.inventario_id
  LEFT JOIN usuarios u ON u.id = m.digitador_id
  LEFT JOIN usuarios o ON o.id = m.operador_id
  WHERE $where ORDER BY m.creado_en DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<div class="card p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5>Mantenimientos</h5>
    <?php if($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'digitador'): ?>
      <a class="btn btn-primary btn-sm" href="/mantenimientos/crear.php">+ Nuevo</a>
    <?php endif; ?>
  </div>
  <div class="table-responsive">
    <table class="table table-sm">
      <thead><tr><th>ID</th><th>Título</th><th>Fecha</th><th>Cliente</th><th>Inventario</th><th>Estado</th><th>Digitador</th><th>Operador</th><th></th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?=$r['id']?></td>
            <td><?=htmlspecialchars($r['titulo'])?></td>
            <td><?=$r['fecha']?></td>
            <td><?=htmlspecialchars($r['cliente'])?></td>
            <td><?=htmlspecialchars($r['inventario'])?></td>
            <td><span class="badge bg-<?= $r['estado']==='finalizado' ? 'success' : ($r['estado']==='en proceso' ? 'warning text-dark' : 'secondary') ?>"><?=htmlspecialchars($r['estado'])?></span></td>
            <td><?=htmlspecialchars($r['digitador'])?></td>
            <td><?=htmlspecialchars($r['operador'])?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="/mantenimientos/editar.php?id=<?=$r['id']?>">Ver / Editar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
