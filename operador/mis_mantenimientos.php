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

// Traemos los mantenimientos asignados al operador logueado
$stmt = $pdo->prepare('
    SELECT 
        m.id,
        categoria 
        m.titulo, 
        m.fecha, 
        m.estado, 
        m.creado_en,
        c.cliente AS cliente, 
        i.nombre AS inventario,
        u.nombre AS digitador
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
          <p class="card-text mb-1"><b>Cliente:</b> <?= htmlspecialchars($r['cliente'] ?? '-') ?></p>
          <p class="card-text mb-1"><b>Categoria:</b> <?= htmlspecialchars($r['categoria'] ?? '-') ?></p>
          <p class="card-text mb-1"><b>Digitador:</b> <?= htmlspecialchars($r['digitador'] ?? '-') ?></p>
          <p class="card-text mb-1"><b>Fecha:</b> <?= $r['fecha'] ?></p>
          <p class="card-text mb-2"><b>Estado:</b> <?= $r['estado'] ?></p>
          <div class="d-flex gap-2">

            <?php if ($r['estado'] === 'pendiente' || $r['estado'] === 'en proceso'): ?>
              <!-- Siempre se pueden generar reportes en pendientes y en proceso -->
              <a class="btn btn-sm btn-outline-success flex-fill" href="/operador/form_reporte.php?id=<?= $r['id'] ?>">Generar Reporte</a>
            <?php elseif ($r['estado'] === 'finalizado'): ?>
              <?php
                // Calcular si han pasado menos de 24hrs desde creado_en
                $creado = new DateTime($r['creado_en']);
                $ahora = new DateTime();
                $diffHoras = ($ahora->getTimestamp() - $creado->getTimestamp()) / 3600;
              ?>
              <?php if ($diffHoras <= 24): ?>
                <a href="/mantenimientos/editar.php?id=<?= $r['id'] ?>" class="btn btn-primary btn-sm flex-fill">Editar</a>
              <?php else: ?>
                <span class="text-muted small">Edición bloqueada (más de 24h)</span>
              <?php endif; ?>
            <?php endif; ?>

          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="alert alert-info">No tienes mantenimientos asignados.</div>
  <?php endif; ?>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
