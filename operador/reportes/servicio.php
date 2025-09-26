<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header('Location: /../index.php');
    exit;
}
require_once __DIR__ . '/../../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) die('ID no proporcionado');

// Traer datos del mantenimiento + cliente
$stmt = $pdo->prepare("
  SELECT m.*, c.cliente, c.direccion, c.responsable, c.telefono
  FROM mantenimientos m
  LEFT JOIN clientes c ON c.id = m.cliente_id
  WHERE m.id = ?
");
$stmt->execute([$id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$m) die('Mantenimiento no encontrado');

// Lista de equipos desde inventario
$equiposList = $pdo->query("SELECT id_equipo AS id_equipo, Identificador, Marca, Modelo, Ubicacion, Voltaje 
                            FROM equipos ORDER BY Identificador ASC")->fetchAll(PDO::FETCH_ASSOC);

// Preparar array con equipos del mantenimiento (equipo1..equipo7)
$equiposMantenimiento = [];
for ($i = 1; $i <= 7; $i++) {
    $eqId = $m["equipo$i"] ?? null;
    $eq = null;
    if ($eqId) {
        foreach ($equiposList as $e) {
            if ($e['id_equipo'] == $eqId) {
                $eq = $e;
                break;
            }
        }
    }
    $equiposMantenimiento[$i] = $eq;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Generar Reporte - Mantenimiento #<?=htmlspecialchars($m['id'])?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  .firma-box { border:1px solid #000000ff; height:150px; background:#fff; }
  canvas { width:100%; height:150px; }
  .img-preview { max-width:100%; max-height:150px; object-fit:contain; border:1px solid #000000ff; padding:4px; background:#fff; }
  @media (max-width:576px){ .firma-box { height:120px } canvas{ height:120px } }
</style>
</head>
<body class="bg-light">
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5>Reporte de Servicio Técnico — Mantenimiento #<?=htmlspecialchars($m['id'])?></h5>
    <a class="btn btn-secondary btn-sm" href="/operador/mis_mantenimientos.php">Volver</a>
  </div>

  <div class="card mb-3 p-3">
    <div><strong>CLIENTE:</strong> <?=htmlspecialchars($m['cliente'] ?? '-')?></div>
    <div><strong>DIRECCIÓN:</strong> <?=htmlspecialchars($m['direccion'] ?? '-')?></div>
    <div><strong>RESPONSABLE:</strong> <?=htmlspecialchars($m['responsable'] ?? '-')?></div>
    <div><strong>FECHA:</strong> <?=htmlspecialchars($m['fecha'] ?? date('Y-m-d'))?></div>
  </div>

  <form id="formReporte" method="post" action="guardar_reporte.php" enctype="multipart/form-data" class="mb-5">
    <input type="hidden" name="mantenimiento_id" value="<?=htmlspecialchars($m['id'])?>">

    <h6>DATOS DE IDENTIFICACIÓN DE LOS EQUIPOS A INTERVENIR</h6>
    <div class="table-responsive mb-3">
      <table class="table table-bordered align-middle text-center">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Identificador</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Ubicación</th>
            <th>Voltaje</th>
          </tr>
        </thead>
        <tbody>
          <?php for($i=1;$i<=7;$i++): 
            $eq = $equiposMantenimiento[$i];
          ?>
          <tr>
            <td><?= $i ?></td>
            <td>
              <select class="form-select form-select-sm equipo-select" 
                      name="equipos[<?= $i ?>][id_equipo]" 
                      data-index="<?= $i ?>">
                <option value="">-- Seleccione --</option>
                <?php foreach($equiposList as $e): ?>
                    <option value="<?= $e['id_equipo'] ?>" <?= ($eq && $eq['id_equipo']==$e['id_equipo'] ? 'selected' : '') ?>>
                        <?= htmlspecialchars($e['Identificador']) ?>
                    </option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input type="text" class="form-control form-control-sm marca-<?= $i ?>" name="equipos[<?= $i ?>][marca]" value="<?=htmlspecialchars($eq['Marca'] ?? '')?>" readonly></td>
            <td><input type="text" class="form-control form-control-sm modelo-<?= $i ?>" name="equipos[<?= $i ?>][modelo]" value="<?=htmlspecialchars($eq['Modelo'] ?? '')?>" readonly></td>
            <td><input type="text" class="form-control form-control-sm ubicacion-<?= $i ?>" name="equipos[<?= $i ?>][ubicacion]" value="<?=htmlspecialchars($eq['Ubicacion'] ?? '')?>" readonly></td>
            <td><input type="text" class="form-control form-control-sm voltaje-<?= $i ?>" name="equipos[<?= $i ?>][voltaje]" value="<?=htmlspecialchars($eq['Voltaje'] ?? '')?>" readonly></td>
          </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>

    <h6>PARÁMETROS DE FUNCIONAMIENTO (Antes / Después)</h6>
    <div class="table-responsive mb-3">
      <table class="table table-bordered table-sm">
        <thead class="table-light">
          <tr>
            <th>Medida</th>
            <?php for($i=1;$i<=7;$i++): ?>
              <th colspan="2" class="text-center">Equipo <?= $i ?></th>
            <?php endfor; ?>
          </tr>
          <tr>
            <th></th>
            <?php for($i=1;$i<=7;$i++): ?>
              <th>Antes</th><th>Desp.</th>
            <?php endfor; ?>
          </tr>
        </thead>
        <tbody>
          <?php
          $parametros = [
            'Corriente eléctrica nominal (Amperios) L1',
            'Corriente L2','Corriente L3',
            'Tensión eléctrica nominal V1','Tensión V2','Tensión V3',
            'Presión de descarga (PSI)','Presión de succión (PSI)'
          ];
          foreach($parametros as $p): ?>
            <tr>
              <td style="min-width:200px;"><?=htmlspecialchars($p)?></td>
              <?php for($i=1;$i<=7;$i++): ?>
                <td><input type="text" class="form-control form-control-sm" name="parametros[<?= md5($p) ?>][<?= $i ?>][antes]"></td>
                <td><input type="text" class="form-control form-control-sm" name="parametros[<?= md5($p) ?>][<?= $i ?>][despues]"></td>
              <?php endfor; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="mb-3">
      <label>Trabajos realizados</label>
      <textarea class="form-control" name="trabajos" rows="4"></textarea>
    </div>

    <div class="mb-3">
      <label>Observaciones y recomendaciones</label>
      <textarea class="form-control" name="observaciones" rows="3"></textarea>
    </div>

    <div class="mb-3">
      <label>Fotos del/los equipos (múltiples)</label>
      <input type="file" class="form-control" name="fotos[]" accept="image/*" multiple>
    </div>

    <h6>Firmas</h6>
    <div class="row g-3">
      <div class="col-12 col-md-4">
        <label class="form-label">Firma Cliente</label>
        <div class="firma-box"><canvas id="firmaClienteCanvas"></canvas></div>
        <input type="hidden" name="firma_cliente" id="firma_cliente_input">
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label">Firma Supervisor</label>
        <div class="firma-box"><canvas id="firmaSupervisorCanvas"></canvas></div>
        <input type="hidden" name="firma_supervisor" id="firma_supervisor_input">
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label">Firma Técnico</label>
        <div class="firma-box"><canvas id="firmaTecnicoCanvas"></canvas></div>
        <input type="hidden" name="firma_tecnico" id="firma_tecnico_input">
      </div>
    </div>

    <div class="text-center mt-4">
      <button type="submit" class="btn btn-success btn-lg">Guardar y Generar Reporte (PDF)</button>
    </div>
  </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

<script>
// Firma
const sigCliente = new SignaturePad(document.getElementById('firmaClienteCanvas'));
const sigSupervisor = new SignaturePad(document.getElementById('firmaSupervisorCanvas'));
const sigTecnico = new SignaturePad(document.getElementById('firmaTecnicoCanvas'));

document.getElementById('formReporte').addEventListener('submit', function(){
  if (!sigCliente.isEmpty()) document.getElementById('firma_cliente_input').value = sigCliente.toDataURL();
  if (!sigSupervisor.isEmpty()) document.getElementById('firma_supervisor_input').value = sigSupervisor.toDataURL();
  if (!sigTecnico.isEmpty()) document.getElementById('firma_tecnico_input').value = sigTecnico.toDataURL();
});

// Select2 + cargar datos de equipo
$(document).ready(function(){
  $('.equipo-select').select2({ placeholder:"Buscar equipo...", allowClear:true, width:'100%' });

  $('.equipo-select').each(function(){
    let id = $(this).val();
    let index = $(this).data('index');
    if(id){
      $.getJSON('/operador/ajax_get_equipo.php', { id }, function(data){
        if(data){
          $(`.marca-${index}`).val(data.Marca||'');
          $(`.modelo-${index}`).val(data.Modelo||'');
          $(`.ubicacion-${index}`).val(data.Ubicacion||'');
          $(`.voltaje-${index}`).val(data.Voltaje||'');
        }
      });
    }
  });

  $('.equipo-select').on('change', function(){
    let id = $(this).val();
    let index = $(this).data('index');
    if(!id) {
      $(`.marca-${index}, .modelo-${index}, .ubicacion-${index}, .voltaje-${index}`).val('');
      return;
    }
    $.getJSON('/operador/ajax_get_equipo.php', { id }, function(data){
      if(data){
        $(`.marca-${index}`).val(data.Marca||'');
        $(`.modelo-${index}`).val(data.Modelo||'');
        $(`.ubicacion-${index}`).val(data.Ubicacion||'');
        $(`.voltaje-${index}`).val(data.Voltaje||'');
      }
    });
  });
});
</script>
</body>
</html>
