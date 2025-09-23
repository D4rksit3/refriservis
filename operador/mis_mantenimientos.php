<?php
// operador/mis_mantenimientos.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header('Location: /index.php');
    exit;
}

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

// =============================
// PAGINACIÓN
// =============================
$por_pagina = 10; // cantidad de registros por página
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $por_pagina;

// Contar total de registros
$stmt_count = $pdo->prepare('
    SELECT COUNT(*) 
    FROM mantenimientos 
    WHERE operador_id = :operador_id
');
$stmt_count->execute([':operador_id' => $_SESSION['usuario_id']]);
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $por_pagina);

// =============================
// CONSULTA PRINCIPAL
// =============================
$stmt = $pdo->prepare('
    SELECT m.id, m.titulo, m.fecha, m.estado, m.creado_en,
           c.nombre AS cliente, c.direccion,
           i.nombre AS inventario, i.marca, i.modelo
    FROM mantenimientos m
    LEFT JOIN clientes c ON c.id = m.cliente_id
    LEFT JOIN inventario i ON i.id = m.inventario_id
    WHERE m.operador_id = :operador_id
    ORDER BY m.creado_en DESC
    LIMIT :limit OFFSET :offset
');

$stmt->bindValue(':operador_id', $_SESSION['usuario_id'], PDO::PARAM_INT);
$stmt->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container my-3">
  <h5 class="mb-3">Mis mantenimientos asignados</h5>

  <?php if ($rows): ?>
    <?php foreach($rows as $r): ?>
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <h6 class="card-title"><?= htmlspecialchars($r['titulo']) ?></h6>
          <p class="card-text mb-1"><b>Cliente:</b> <?= htmlspecialchars($r['cliente']) ?></p>
          <p class="card-text mb-1"><b>Dirección:</b> <?= htmlspecialchars($r['direccion']) ?></p>
          <p class="card-text mb-1"><b>Inventario:</b> <?= htmlspecialchars($r['inventario']) ?> (<?= htmlspecialchars($r['marca']) ?> - <?= htmlspecialchars($r['modelo']) ?>)</p>
          <p class="card-text mb-1"><b>Fecha:</b> <?= $r['fecha'] ?></p>
          <p class="card-text mb-2"><b>Estado:</b> <?= $r['estado'] ?></p>
          <div class="d-flex gap-2">
            <a href="/mantenimientos/editar.php?id=<?= $r['id'] ?>" class="btn btn-primary btn-sm flex-fill">Actualizar</a>
            <a class="btn btn-sm btn-outline-success" href="/operador/form_reporte.php?id=<?= $r['id'] ?>">Generar Reporte</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- =============================
         Paginación
    ============================== -->
    <nav>
      <ul class="pagination">
        <?php if ($pagina > 1): ?>
          <li class="page-item">
            <a class="page-link" href="?pagina=<?= $pagina - 1 ?>">Anterior</a>
          </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
          <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
            <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>

        <?php if ($pagina < $total_paginas): ?>
          <li class="page-item">
            <a class="page-link" href="?pagina=<?= $pagina + 1 ?>">Siguiente</a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>

  <?php else: ?>
    <div class="alert alert-info">No tienes mantenimientos asignados.</div>
  <?php endif; ?>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
