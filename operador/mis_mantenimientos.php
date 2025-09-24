<?php
// operador/mis_mantenimientos.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header('Location: /index.php');
    exit;
}

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

// Traer mantenimientos asignados al operador con detalles del digitador
$stmt = $pdo->prepare('
    SELECT m.id, m.titulo, m.fecha, m.estado, 
           m.descripcion, m.observaciones, m.repuestos, 
           c.id AS cliente_id, 
           i.id AS inventario_id, 
           u.usuario AS digitador
    FROM mantenimientos m
    LEFT JOIN clientes c ON c.id = m.cliente_id
    LEFT JOIN inventario i ON i.id = m.inventario_id
    LEFT JOIN usuarios u ON u.id = m.digitador_id
    WHERE m.operador_id = ?
    ORDER BY m.creado_en DESC
');
$stmt->execute([$_SESSION['usuario_id']]);
$rows = $stmt->fetchAll();
?>

<div class="container my-3">
  <h5 class="mb-3">Mis mantenimientos asignados</h5>

  <?php if ($rows): ?>
    <?php foreach($rows as $r): ?>
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <h6 class="card-title"><?= htmlspecialchars($r['titulo']) ?></h6>
          <p class="card-text mb-1"><b>Cliente ID:</b> <?= htmlspecialchars($r['cliente_id']) ?></p>
          <p class="card-text mb-1"><b>Inventario ID:</b> <?= htmlspecialchars($r['inventario_id']) ?></p>
          <p class="card-text mb-1"><b>Fecha:</b> <?= $r['fecha'] ?></p>
          <p class="card-text mb-1"><b>Estado:</b> <?= $r['estado'] ?></p>
          <p class="card-text mb-1"><b>Digitador:</b> <?= htmlspecialchars($r['digitador']) ?></p>

          <hr>
          <p class="card-text mb-1"><b>Descripción:</b> <?= nl2br(htmlspecialchars($r['descripcion'])) ?></p>
          <p class="card-text mb-1"><b>Observaciones:</b> <?= nl2br(htmlspecialchars($r['observaciones'])) ?></p>
          <p class="card-text mb-2"><b>Repuestos:</b> <?= nl2br(htmlspecialchars($r['repuestos'])) ?></p>

          <div class="d-flex gap-2 mt-2">
            <?php if ($r['estado'] !== 'finalizado'): ?>
              <a href="/mantenimientos/editar.php?id=<?= $r['id'] ?>" class="btn btn-primary btn-sm flex-fill">Actualizar</a>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline-success flex-fill" href="/operador/form_reporte.php?id=<?= $r['id'] ?>">Generar Reporte</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="alert alert-info">No tienes mantenimientos asignados.</div>
  <?php endif; ?>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
