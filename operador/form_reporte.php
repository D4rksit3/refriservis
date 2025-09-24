<?php
// operador/form_reporte.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header('Location: /index.php');
    exit;
}
require_once __DIR__.'/../config/db.php';

// Obtener datos del mantenimiento
$id = $_GET['id'] ?? null;
if (!$id) { die("ID no proporcionado"); }

$stmt = $pdo->prepare("
    SELECT m.*, c.cliente, c.direccion, c.responsable
    FROM mantenimientos m
    LEFT JOIN clientes c ON c.id = m.cliente_id
    WHERE m.id = ?
");
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m) { die("Mantenimiento no encontrado"); }

// Obtener equipos relacionados
$equiposAll = $pdo->query("SELECT id, nombre, marca, modelo, serie, gas, codigo FROM inventario")->fetchAll(PDO::FETCH_UNIQUE);
$equipos = [];
for ($i=1; $i<=7; $i++) {
    $eqId = $m['equipo'.$i];
    if ($eqId && isset($equiposAll[$eqId])) {
        $equipos[] = $equiposAll[$eqId];
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Reporte de Servicio Técnico</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .firma-box {border:1px solid #ccc; height:120px; display:flex; justify-content:center; align-items:center; background:#f9f9f9;}
    canvas {width:100%; height:100%;}
  </style>
</head>
<body class="bg-light">
<div class="container my-4">

  <h5 class="mb-3 text-center">FORMATO DE CALIDAD - REFRISERVIS S.A.C.</h5>
  <p class="text-center">REPORTE DE SERVICIO TÉCNICO</p>

  <div class="card p-3 mb-3">
    <p><b>N° Reporte:</b> (autogenerado al guardar)</p>
    <p><b>Cliente:</b> <?= htmlspecialchars($m['cliente']) ?></p>
    <p><b>Dirección:</b> <?= htmlspecialchars($m['direccion']) ?></p>
    <p><b>Responsable:</b> <?= htmlspecialchars($m['responsable']) ?></p>
    <p><b>Fecha:</b> <?= htmlspecialchars($m['fecha']) ?></p>
  </div>

  <form method="post" action="guardar_reporte.php" enctype="multipart/form-data">
    <input type="hidden" name="mantenimiento_id" value="<?= $m['id'] ?>">

    <h6>DATOS DE IDENTIFICACIÓN DE LOS EQUIPOS</h6>
    <div class="table-responsive mb-3">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>#</th><th>Tipo</th><th>Marca</th><th>Modelo</th><th>Serie</th><th>Gas</th><th>Código</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($equipos as $i=>$eq): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($eq['nombre']) ?></td>
            <td><?= htmlspecialchars($eq['marca']) ?></td>
            <td><?= htmlspecialchars($eq['modelo']) ?></td>
            <td><?= htmlspecialchars($eq['serie']) ?></td>
            <td><?= htmlspecialchars($eq['gas']) ?></td>
            <td><?= htmlspecialchars($eq['codigo']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <h6>PARÁMETROS DE FUNCIONAMIENTO</h6>
    <div class="table-responsive mb-3">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>Medida</th>
            <?php foreach($equipos as $i=>$eq): ?>
              <th><?= $i+1 ?> Antes</th>
              <th><?= $i+1 ?> Después</th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php 
          $parametros = [
            "Corriente L1","Corriente L2","Corriente L3",
            "Tensión V1","Tensión V2","Tensión V3",
            "Presión de descarga (PSI)","Presión de succión (PSI)"
          ];
          foreach($parametros as $p): ?>
          <tr>
            <td><?= $p ?></td>
            <?php foreach($equipos as $i=>$eq): ?>
              <td><input type="text" class="form-control form-control-sm" name="parametros[<?= $p ?>][<?= $i ?>][antes]"></td>
              <td><input type="text" class="form-control form-control-sm" name="parametros[<?= $p ?>][<?= $i ?>][despues]"></td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="mb-3">
      <label class="form-label">Trabajos Realizados</label>
      <textarea class="form-control" name="trabajos" rows="3"></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Observaciones y Recomendaciones</label>
      <textarea class="form-control" name="observaciones" rows="3"></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Fotos de los equipos</label>
      <input type="file" class="form-control" name="fotos[]" multiple accept="image/*">
    </div>

    <h6>Firmas</h6>
    <div class="row g-2">
      <div class="col-12 col-md-4">
        <label>Firma Cliente</label>
        <input type="file" class="form-control" name="firma_cliente" accept="image/*">
      </div>
      <div class="col-12 col-md-4">
        <label>Firma Supervisor</label>
        <input type="file" class="form-control" name="firma_supervisor" accept="image/*">
      </div>
      <div class="col-12 col-md-4">
        <label>Firma Técnico</label>
        <input type="file" class="form-control" name="firma_tecnico" accept="image/*">
      </div>
    </div>

    <div class="text-center mt-3">
      <button type="submit" class="btn btn-success">Guardar Reporte</button>
    </div>
  </form>

</div>
</body>
</html>
