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

// ============================
// CONFIGURACIÓN DE PAGINACIÓN
// ============================
$itemsPorPagina = 6; // ahora 6 cards por página
$pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $itemsPorPagina;

// ============================
// CONSULTA BASE
// ============================
$sql = '
    SELECT 
        m.id, 
        m.categoria,
        m.titulo, 
        m.fecha, 
        m.estado, 
        m.creado_en,
        m.reporte_generado,
        c.cliente AS cliente, 
        i.nombre AS inventario,
        u.nombre AS digitador
    FROM mantenimientos m
    LEFT JOIN clientes c ON c.id = m.cliente_id
    LEFT JOIN inventario i ON i.id = m.inventario_id
    LEFT JOIN usuarios u ON u.id = m.digitador_id
    WHERE m.operador_id = :operador_id
      AND m.creado_en >= (NOW() - INTERVAL 24 HOUR)
    ORDER BY m.creado_en DESC
    LIMIT :limit OFFSET :offset
';

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':operador_id', $_SESSION['usuario_id'], PDO::PARAM_INT);
$stmt->bindValue(':limit', $itemsPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total de registros (para paginación)
$stmtTotal = $pdo->prepare('
    SELECT COUNT(*) 
    FROM mantenimientos m
    WHERE m.operador_id = ?
      AND m.creado_en >= (NOW() - INTERVAL 24 HOUR)
');
$stmtTotal->execute([$_SESSION['usuario_id']]);
$totalRegistros = $stmtTotal->fetchColumn();
$totalPaginas = ceil($totalRegistros / $itemsPorPagina);

// Mapa de categorías → reportes
$mapaReportes = [
    'VRV - FORMATO DE CALIDAD'                            => '/operador/reportes/vrv.php',
    'VENTILACION MECANICA (VEX-VIN) - FORMATO DE CALIDAD' => '/operador/reportes/vex_vin.php',
    'UMA - FORMATO DE CALIDAD'                            => '/operador/reportes/uma.php',
    'SPLIT DECORATIVO - FORMATO DE CALIDAD'               => '/operador/reportes/split.php',
    'ROOFTOP - FORMATO DE CALIDAD'                        => '/operador/reportes/rooftop.php',
    'CORTINAS DE AIRE - FORMATO DE CALIDAD'               => '/operador/reportes/cortinas.php',
    'CHILLERS - FORMATO DE CALIDAD'                       => '/operador/reportes/chillers.php',
    'BOMBAS DE AGUA - FORMATO DE CALIDAD'                 => '/operador/reportes/bombas.php',
    'REPORTE DE SERVICIO TECNICO'                         => '/operador/reportes/servicio.php'
];
?>

<div class="container my-3">
  <h5 class="mb-3">Mis mantenimientos asignados (últimas 24h)</h5>

  <?php if ($rows): ?>
    <div class="row">
      <?php foreach($rows as $r): ?>
        <div class="col-md-6 mb-3">
          <div class="card shadow-sm h-100 border-0">
            <div class="card-body">
              <h6 class="card-title text-primary"><?= htmlspecialchars($r['titulo']) ?></h6>
              <p class="card-text mb-1"><b>Categoría:</b> <?= htmlspecialchars($r['categoria'] ?? '-') ?></p>
              <p class="card-text mb-1"><b>Cliente:</b> <?= htmlspecialchars($r['cliente'] ?? '-') ?></p>
              
              <p class="card-text mb-1"><b>Digitador:</b> <?= htmlspecialchars($r['digitador'] ?? '-') ?></p>
              <p class="card-text mb-1"><b>Fecha:</b> <?= $r['fecha'] ?></p>
              <p class="card-text mb-2"><b>Estado:</b> <?= ucfirst($r['estado']) ?></p>

              <div class="d-flex gap-2">
                <?php
                  $categoria = $r['categoria'];
                  $urlReporte = $mapaReportes[$categoria] ?? '/operador/form_reporte.php';
                ?>

                <?php if ($r['reporte_generado'] && $r['estado'] !== 'finalizado'): ?>
                  <a href="<?= $urlReporte ?>?id=<?= $r['id'] ?>" 
                    class="btn btn-secondary btn-sm w-100">Ver / Editar Reporte</a>

                <?php elseif ($r['estado'] === 'pendiente' || $r['estado'] === 'en proceso'): ?>
                  <a href="<?= $urlReporte ?>?id=<?= $r['id'] ?>" 
                    class="btn btn-outline-success btn-sm w-100">
                    Generar Reporte
                  </a>

                <?php elseif ($r['estado'] === 'finalizado'): ?>
                  <a href="<?= $urlDescarga ?>" 
                    class="btn btn-primary btn-sm w-100" target="_blank">
                    Descargar Reporte
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Paginación -->
    <nav>
      <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
          <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
            <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>

  <?php else: ?>
    <div class="alert alert-info">No tienes mantenimientos asignados en las últimas 24h.</div>
  <?php endif; ?>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
