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

// 📌 Mapa de categorías → formularios (ver/generar)
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

// 📌 Mapa de categorías → archivos de descarga
$mapaDescargas = [
    'VRV - FORMATO DE CALIDAD'                            => '/operador/reportes/guardar_reporte_vrv.php',
    'VENTILACION MECANICA (VEX-VIN) - FORMATO DE CALIDAD' => '/operador/reportes/guardar_reporte_vex_vin.php',
    'UMA - FORMATO DE CALIDAD'                            => '/operador/reportes/guardar_reporte_uma.php',
    'SPLIT DECORATIVO - FORMATO DE CALIDAD'               => '/operador/reportes/guardar_reporte_split.php',
    'ROOFTOP - FORMATO DE CALIDAD'                        => '/operador/reportes/guardar_reporte_rooftop.php',
    'CORTINAS DE AIRE - FORMATO DE CALIDAD'               => '/operador/reportes/guardar_reporte_cortinas.php',
    'CHILLERS - FORMATO DE CALIDAD'                       => '/operador/reportes/guardar_reporte_chillers.php',
    'BOMBAS DE AGUA - FORMATO DE CALIDAD'                 => '/operador/reportes/guardar_reporte_bombas.php',
    'REPORTE DE SERVICIO TECNICO'                         => '/operador/reportes/guardar_reporte_servicio.php'
];
?>

<div class="container my-3">
  <h5 class="mb-3">Mis mantenimientos asignados (últimas 24h)</h5>

  <?php if ($rows): ?>
    <div class="row">
      <?php foreach($rows as $r): ?>
        <?php
            $categoria = $r['categoria'] ?? '';
            $id        = $r['id'] ?? 0;

            // Resolver URLs según categoría
            $urlReporte  = $mapaReportes[$categoria] ?? '/operador/form_reporte.php';
            $urlDescarga = $mapaDescargas[$categoria] ?? '/operador/reportes/guardar_reporte_servicio.php';
            $urlDescarga .= '?id=' . $id;
        ?>
        <div class="col-md-6 mb-3">
          <div class="card shadow-sm h-100 border-0">
            <div class="card-body">
              <h6 class="card-title text-primary"><?= htmlspecialchars($r['titulo']) ?></h6>
              <p class="card-text mb-1"><b>Categoría:</b> <?= htmlspecialchars($categoria) ?></p>
              <p class="card-text mb-1"><b>Cliente:</b> <?= htmlspecialchars($r['cliente'] ?? '-') ?></p>
              <p class="card-text mb-1"><b>Digitador:</b> <?= htmlspecialchars($r['digitador'] ?? '-') ?></p>
              <p class="card-text mb-1"><b>Fecha:</b> <?= htmlspecialchars($r['fecha'] ?? '-') ?></p>
              <p class="card-text mb-2"><b>Estado:</b> <?= ucfirst(htmlspecialchars($r['estado'] ?? '-')) ?></p>

              <div class="d-flex gap-2">
                 <?php if (!empty($r['reporte_generado']) && $r['estado'] !== 'finalizado'): ?>
                        <a href="<?= $urlReporte ?>?id=<?= $id ?>" 
                           class="btn btn-secondary btn-sm w-100">Ver / Editar Reporte</a>

                 <?php elseif ($r['estado'] === 'pendiente' || $r['estado'] === 'en proceso'): ?>
                        <a href="<?= $urlReporte ?>?id=<?= $id ?>" 
                           class="btn btn-outline-success btn-sm w-100">Generar Reporte</a>

                 <?php elseif ($r['estado'] === 'finalizado'): ?>
                        <a href="<?= $urlDescarga ?>" 
                           class="btn btn-primary btn-sm w-100" target="_blank">
                           <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-down" viewBox="0 0 16 16">
                              <path fill-rule="evenodd" d="M7.646 10.854a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 9.293V5.5a.5.5 0 0 0-1 0v3.793L6.354 8.146a.5.5 0 1 0-.708.708z"/>
                              <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 
                                      16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 
                                      1.266-3.223 2.942-3.593.143-.863.698-1.723 
                                      1.464-2.383m.653.757c-.757.653-1.153 
                                      1.44-1.153 2.056v.448l-.445.049C2.064 
                                      6.805 1 7.952 1 9.318 1 10.785 2.23 
                                      12 3.781 12h8.906C13.98 12 15 10.988 
                                      15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5
                                      C12.188 4.825 10.328 3 8 3a4.53 4.53 
                                      0 0 0-2.941 1.1z"/>
                           </svg> Descargar Reporte
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
