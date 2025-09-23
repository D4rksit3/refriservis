<?php
// operador/reporte.php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: /index.php'); exit; }

require_once __DIR__.'/../config/db.php';
$reporte_id = (int)($_GET['id'] ?? 0);
if (!$reporte_id) die("Reporte no especificado.");

$stmt = $pdo->prepare("
  SELECT r.*, m.titulo AS orden_titulo, m.fecha AS orden_fecha,
         c.cliente AS cliente_nombre, c.direccion AS cliente_direccion, c.responsable AS cliente_responsable, c.telefono AS cliente_telefono,
         i.nombre AS equipo, i.marca, i.modelo, i.serie, i.gas, i.codigo,
         u.nombre AS tecnico
  FROM reportes r
  LEFT JOIN mantenimientos m ON m.id = r.mantenimiento_id
  LEFT JOIN clientes c ON c.id = m.cliente_id
  LEFT JOIN inventario i ON i.id = m.inventario_id
  LEFT JOIN usuarios u ON u.id = r.usuario_id
  WHERE r.id = ?
");
$stmt->execute([$reporte_id]);
$rep = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$rep) die("Reporte no encontrado.");

$params = $pdo->prepare("SELECT * FROM parametros_funcionamiento WHERE reporte_id=? ORDER BY id");
$params->execute([$reporte_id]);
$params = $params->fetchAll(PDO::FETCH_ASSOC);

$fotos = $pdo->prepare("SELECT * FROM fotos_reporte WHERE reporte_id=? ORDER BY id");
$fotos->execute([$reporte_id]);
$fotos = $fotos->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Reporte #<?= $rep['id'] ?> - <?= htmlspecialchars($rep['orden_titulo']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #fff; color: #111; }
    .report-header { border-bottom: 2px solid #eee; margin-bottom: 18px; padding-bottom: 12px; }
    .firma { width: 220px; height: 80px; border:1px solid #ddd; display:block; object-fit:contain; }
    @media print {
      .no-print { display:none; }
      .container { max-width: 100%; }
    }
  </style>
</head>
<body>
<div class="container my-4">
  <div class="d-flex justify-content-between report-header">
    <div>
      <h4>Reporte de Servicio Técnico</h4>
      <div><strong>Orden:</strong> <?= htmlspecialchars($rep['orden_titulo']) ?></div>
      <div><strong>Reporte ID:</strong> <?= $rep['id'] ?> — <strong>Fecha:</strong> <?= htmlspecialchars($rep['creado_en']) ?></div>
    </div>
    <div class="text-end">
      <h6>Datos del Cliente</h6>
      <div><strong><?= htmlspecialchars($rep['cliente_nombre']) ?></strong></div>
      <div><?= htmlspecialchars($rep['cliente_direccion']) ?></div>
      <div><?= htmlspecialchars($rep['cliente_responsable']) ?> — <?= htmlspecialchars($rep['cliente_telefono']) ?></div>
    </div>
  </div>

  <h5>Equipo</h5>
  <p><?= htmlspecialchars($rep['equipo']) ?> — Marca: <?= htmlspecialchars($rep['marca']) ?> / Modelo: <?= htmlspecialchars($rep['modelo']) ?> / Serie: <?= htmlspecialchars($rep['serie']) ?></p>

  <h5>Trabajos realizados</h5>
  <p><?= nl2br(htmlspecialchars($rep['trabajos'])) ?></p>

  <h5>Observaciones</h5>
  <p><?= nl2br(htmlspecialchars($rep['observaciones'])) ?></p>

  <?php if ($params): ?>
    <h5>Parámetros de funcionamiento</h5>
    <table class="table table-sm table-bordered">
      <thead class="table-light"><tr><th>Medida</th><th>Antes 1</th><th>Después 1</th><th>Antes 2</th><th>Después 2</th></tr></thead>
      <tbody>
        <?php foreach($params as $p): ?>
          <tr>
            <td><?=htmlspecialchars($p['medida'])?></td>
            <td><?=htmlspecialchars($p['antes1'])?></td>
            <td><?=htmlspecialchars($p['despues1'])?></td>
            <td><?=htmlspecialchars($p['antes2'])?></td>
            <td><?=htmlspecialchars($p['despues2'])?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <?php if ($fotos): ?>
    <h5>Fotos</h5>
    <div class="row">
      <?php foreach($fotos as $f): ?>
        <div class="col-md-3 mb-2">
          <img src="/uploads/<?=htmlspecialchars($f['archivo'])?>" class="img-fluid" style="max-height:160px;object-fit:cover;border:1px solid #ddd;padding:3px;">
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <h5 class="mt-4">Firmas</h5>
  <div class="d-flex gap-4 align-items-center">
    <div>
      <div>Cliente</div>
      <?php if ($rep['firma_cliente']): ?>
        <img src="/uploads/<?=htmlspecialchars($rep['firma_cliente'])?>" class="firma">
      <?php else: ?><div class="text-muted">No registrada</div><?php endif; ?>
    </div>
    <div>
      <div>Técnico</div>
      <?php if ($rep['firma_tecnico']): ?>
        <img src="/uploads/<?=htmlspecialchars($rep['firma_tecnico'])?>" class="firma">
      <?php else: ?><div class="text-muted">No registrada</div><?php endif; ?>
    </div>
    <div>
      <div>Supervisor</div>
      <?php if ($rep['firma_supervisor']): ?>
        <img src="/uploads/<?=htmlspecialchars($rep['firma_supervisor'])?>" class="firma">
      <?php else: ?><div class="text-muted">No registrada</div><?php endif; ?>
    </div>
  </div>

  <div class="mt-4 no-print">
    <a class="btn btn-primary" href="javascript:window.print()">Imprimir / PDF</a>
    <a class="btn btn-secondary" href="/operador/mis_mantenimientos.php">Volver</a>
  </div>
</div>
</body>
</html>
