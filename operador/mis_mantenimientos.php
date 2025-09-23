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

if (!isset($_SESSION['usuario_id'])) {
    die("Error: usuario_id no definido en la sesión.");
}

try {
    // Paginación
    $por_pagina = 10;
    $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    $offset = ($pagina - 1) * $por_pagina;

    // Total registros
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM mantenimientos WHERE operador_id = ?");
    $stmtTotal->execute([$_SESSION['usuario_id']]);
    $total = $stmtTotal->fetchColumn();
    if ($total === false) {
        throw new Exception("No se pudo obtener el total de mantenimientos.");
    }
    $total_paginas = ceil($total / $por_pagina);

    // Query principal
    $stmt = $pdo->prepare('
        SELECT m.id, m.titulo, m.fecha, m.estado, m.creado_en,
               c.cliente AS cliente, c.direccion,
               i.nombre AS inventario, i.marca, i.modelo
        FROM mantenimientos m
        LEFT JOIN clientes c ON c.id = m.cliente_id
        LEFT JOIN inventario i ON i.id = m.inventario_id
        WHERE m.operador_id = ?
        ORDER BY m.creado_en DESC
        LIMIT :limit OFFSET :offset
    ');
    $stmt->bindValue(1, $_SESSION['usuario_id'], PDO::PARAM_INT);
    $stmt->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<div class="container my-3">
  <h5 class="mb-3">📋 Mis mantenimientos asignados</h5>

  <?php if ($rows && count($rows) > 0): ?>
    <?php foreach($rows as $r): ?>
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <h6 class="card-title"><?= htmlspecialchars($r['titulo']) ?></h6>
          <p class="card-text mb-1"><b>Cliente:</b> <?= htmlspecialchars($r['cliente']) ?></p>
          <p class="card-text mb-1"><b>Equipo:</b> <?= htmlspecialchars($r['inventario']) ?> / <?= htmlspecialchars($r['marca']) ?> <?= htmlspecialchars($r['modelo']) ?></p>
          <p class="card-text mb-1"><b>Fecha:</b> <?= htmlspecialchars($r['fecha']) ?></p>
          <p class="card-text mb-2"><b>Estado:</b> <?= htmlspecialchars($r['estado']) ?></p>
          <div class="d-flex gap-2">
            <a href="/mantenimientos/editar.php?id=<?= $r['id'] ?>" class="btn btn-primary btn-sm flex-fill">Actualizar</a>
            <a href="/operador/form_reporte.php?id=<?= $r['id'] ?>" class="btn btn-outline-success btn-sm flex-fill">Generar Reporte</a>
          </div>
        </div>
        <div class="card-footer text-muted small">
          Creado el <?= date("d/m/Y H:i", strtotime($r['creado_en'])) ?>
        </div>
      </div>
    <?php endforeach; ?>

    <nav>
      <ul class="pagination justify-content-center">
        <?php if ($pagina > 1): ?>
          <li class="page-item"><a class="page-link" href="?pagina=<?= $pagina-1 ?>">« Anterior</a></li>
        <?php endif; ?>
        <?php for($i=1; $i<=$total_paginas; $i++): ?>
          <li class="page-item <?= $i==$pagina ? 'active':'' ?>">
            <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
        <?php if ($pagina < $total_paginas): ?>
          <li class="page-item"><a class="page-link" href="?pagina=<?= $pagina+1 ?>">Siguiente »</a></li>
        <?php endif; ?>
      </ul>
    </nav>

  <?php else: ?>
    <div class="alert alert-info">No tienes mantenimientos asignados.</div>
  <?php endif; ?>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
