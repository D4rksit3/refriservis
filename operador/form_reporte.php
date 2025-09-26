<?php
// operador/form_reporte.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header('Location: /index.php');
    exit;
}
require_once __DIR__ . '/../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) die('ID no proporcionado');

// --- obtener mantenimiento con cliente ---
$stmt = $pdo->prepare("
  SELECT m.*, c.cliente, c.direccion, c.responsable, c.telefono
  FROM mantenimientos m
  LEFT JOIN clientes c ON c.id = m.cliente_id
  WHERE m.id = ?
");
$stmt->execute([$id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$m) die('Mantenimiento no encontrado');

// si ya tiene reporte generado, no permitir editar
if ($m['reporte_generado'] == 1) {
    header("Location: ver_reporte.php?id=" . $m['id']);
    exit;
}

// --- inventario para equipos vinculados ---
$inventarioRows = $pdo->query("SELECT id, nombre, marca, modelo, serie, gas, codigo FROM inventario")->fetchAll(PDO::FETCH_UNIQUE);

// --- equipos ya vinculados al mantenimiento ---
$equipos = [];
for ($i = 1; $i <= 7; $i++) {
    $eqId = $m["equipo$i"];
    if ($eqId && isset($inventarioRows[$eqId])) $equipos[] = $inventarioRows[$eqId];
}

// lista de códigos de equipos disponibles
$equiposList = $pdo->query("SELECT Identificador FROM equipos ORDER BY Identificador")->fetchAll(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Generar Reporte - Mantenimiento #<?=htmlspecialchars($m['id'])?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  .firma-box { border:1px solid #ccc; height:150px; background:#fff; }
  canvas { width:100%; height:150px; }
  .img-preview { max-width:100%; max-height:150px; object-fit:contain; border:1px solid #ddd; padding:4px; background:#fff; }
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
    <div><strong>TELÉFONO:</strong> <?=htmlspecialchars($m['telefono'] ?? '-')?></div>
    <div><strong>FECHA:</strong> <?=htmlspecialchars($m['fecha'] ?? date('Y-m-d'))?></div>
  </div>

  <form id="formReporte" method="post" action="guardar_reporte.php" enctype="multipart/form-data" class="mb-5">
    <input type="hidden" name="mantenimiento_id" value="<?=htmlspecialchars($m['id'])?>">

    <h6>DATOS DE IDENTIFICACIÓN DE LOS EQUIPOS A INTERVENIR</h6>
    <div class="table-responsive mb-3">
      <table class="table table-bordered">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Tipo</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>Ubicación/Serie</th>
            <th>Tipo de gas</th>
            <th>Código</th>
          </tr>
        </thead>
        <tbody>
          <?php for($i=0;$i<7;$i++): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><input type="text" class="form-control form-control-sm" name="equipos[<?= $i ?>][tipo]" readonly></td>
            <td><input type="text" class="form-control form-control-sm" name="equipos[<?= $i ?>][marca]" readonly></td>
            <td><input type="text" class="form-control form-control-sm" name="equipos[<?= $i ?>][modelo]" readonly></td>
            <td><input type="text" class="form-control form-control-sm" name="equipos[<?= $i ?>][ubicacion]" readonly></td>
            <td><input type="text" class="form-control form-control-sm" name="equipos[<?= $i ?>][gas]" readonly></td>
            <td>
              <select class="form-select form-select-sm equipo-select" name="equipos[<?= $i ?>][codigo]" data-index="<?= $i ?>">
                <option value="">-- Seleccione --</option>
                <?php foreach($equiposList as $ident): ?>
                  <option value="<?=htmlspecialchars($ident)?>"><?=htmlspecialchars($ident)?></option>
                <?php endforeach; ?>
              </select>
            </td>
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
            <?php $equipoCount = max(1, count($equipos)); ?>
            <?php for($i=1;$i<=$equipoCount;$i++): ?>
              <th colspan="2" class="text-center">Equipo <?= $i ?></th>
            <?php endfor; ?>
          </tr>
          <tr>
            <th></th>
            <?php for($i=1;$i<=$equipoCount;$i++): ?>
              <th>Antes</th><th>Desp.</th>
            <?php endfor; ?>
          </tr>
        </thead>
        <tbody>
          <?php
          $parametros = ['Corriente eléctrica nominal (Amperios) L1','Corriente L2','Corriente L3',
                         'Tensión eléctrica nominal V1','Tensión V2','Tensión V3',
                         'Presión de descarga (PSI)','Presión de succión (PSI)'];
          foreach($parametros as $p): ?>
            <tr>
              <td style="min-width:200px;"><?=htmlspecialchars($p)?></td>
              <?php for($i=0;$i<$equipoCount;$i++): ?>
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
      <small class="text-muted">Se guardarán las imágenes y se incluirán en el PDF.</small>
    </div>

    <h6>Firmas (tocar o dibujar)</h6>
    <div class="row g-3">
      <div class="col-12 col-md-4">
        <label class="form-label">Firma Cliente</label>
        <div class="firma-box"><canvas id="firmaClienteCanvas"></canvas></div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="clearFirma('cliente')">Limpiar</button>
        <input type="hidden" name="firma_cliente" id="firma_cliente_input">
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label">Firma Supervisor</label>
        <div class="firma-box"><canvas id="firmaSupervisorCanvas"></canvas></div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="clearFirma('supervisor')">Limpiar</button>
        <input type="hidden" name="firma_supervisor" id="firma_supervisor_input">
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label">Firma Técnico</label>
        <div class="firma-box"><canvas id="firmaTecnicoCanvas"></canvas></div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="clearFirma('tecnico')">Limpiar</button>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
const sigCliente = new SignaturePad(document.getElementById('firmaClienteCanvas'), { backgroundColor: 'rgba(255,255,255,1)' });
const sigSupervisor = new SignaturePad(document.getElementById('firmaSupervisorCanvas'), { backgroundColor: 'rgba(255,255,255,1)' });
const sigTecnico = new SignaturePad(document.getElementById('firmaTecnicoCanvas'), { backgroundColor: 'rgba(255,255,255,1)' });

function resizeCanvases() {
  ['firmaClienteCanvas','firmaSupervisorCanvas','firmaTecnicoCanvas'].forEach(id=>{
    const canvas = document.getElementById(id);
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const w = canvas.clientWidth;
    canvas.width = w * ratio;
    canvas.height = 150 * ratio;
    canvas.getContext("2d").scale(ratio, ratio);
  });
  sigCliente.clear(); sigSupervisor.clear(); sigTecnico.clear();
}
window.addEventListener('resize', resizeCanvases);
resizeCanvases();

function clearFirma(tipo){
  if(tipo==='cliente') sigCliente.clear();
  if(tipo==='supervisor') sigSupervisor.clear();
  if(tipo==='tecnico') sigTecnico.clear();
}

document.getElementById('formReporte').addEventListener('submit', function(){
  if (!sigCliente.isEmpty()) document.getElementById('firma_cliente_input').value = sigCliente.toDataURL('image/png');
  if (!sigSupervisor.isEmpty()) document.getElementById('firma_supervisor_input').value = sigSupervisor.toDataURL('image/png');
  if (!sigTecnico.isEmpty()) document.getElementById('firma_tecnico_input').value = sigTecnico.toDataURL('image/png');
});

$(function(){
  $('.equipo-select').select2({ placeholder:"Buscar equipo...", allowClear:true, width:'100%' });
  $('.equipo-select').on('change', function(){
    let codigo = $(this).val(), index = $(this).data('index');
    if(!codigo) return;
    $.getJSON('/operador/ajax_get_equipo.php', { codigo }, function(data){
      if(data){
        $(`[name="equipos[${index}][tipo]"]`).val(data.tipo||'');
        $(`[name="equipos[${index}][marca]"]`).val(data.marca||'');
        $(`[name="equipos[${index}][modelo]"]`).val(data.modelo||'');
        $(`[name="equipos[${index}][ubicacion]"]`).val(data.ubicacion||'');
        $(`[name="equipos[${index}][gas]"]`).val(data.gas||'');
      }
    });
  });
});
</script>
</body>
</html>
