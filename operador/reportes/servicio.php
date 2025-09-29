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

// 🚩 Si viene un POST → Guardar reporte en BD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mantenimiento_id = $_POST['mantenimiento_id'];
    $trabajos = $_POST['trabajos'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    $parametros = $_POST['parametros'] ?? [];

    // Guardar firmas
    function saveSignature($dataUrl, $name) {
        if (!$dataUrl) return null;
        $data = explode(',', $dataUrl);
        if (count($data) !== 2) return null;
        $decoded = base64_decode($data[1]);
        $dir = __DIR__ . "/../../uploads/firmas/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $file = $dir . "{$name}_" . time() . ".png";
        file_put_contents($file, $decoded);
        return basename($file);
    }
    $firma_cliente = saveSignature($_POST['firma_cliente'] ?? '', 'cliente');
    $firma_supervisor = saveSignature($_POST['firma_supervisor'] ?? '', 'supervisor');
    $firma_tecnico = saveSignature($_POST['firma_tecnico'] ?? '', 'tecnico');

    // Guardar fotos
    $fotos_guardadas = [];
    if (!empty($_FILES['fotos']['name'][0])) {
        $dir = __DIR__ . "/../../uploads/fotos/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        foreach ($_FILES['fotos']['tmp_name'] as $k => $tmp) {
            if (is_uploaded_file($tmp)) {
                $nombre = time() . "_" . $_FILES['fotos']['name'][$k];
                move_uploaded_file($tmp, $dir . $nombre);
                $fotos_guardadas[] = $nombre;
            }
        }
    }

    // ✅ UPDATE en la tabla mantenimientos
    $stmt = $pdo->prepare("UPDATE mantenimientos SET 
        trabajos = ?, 
        observaciones = ?, 
        parametros = ?, 
        firma_cliente = ?, 
        firma_supervisor = ?, 
        firma_tecnico = ?, 
        fotos = ?, 
        reporte_generado = 1,
        modificado_en = NOW(),
        modificado_por = ?
        WHERE id = ?");
    $stmt->execute([
        $trabajos,
        $observaciones,
        json_encode($parametros),
        $firma_cliente,
        $firma_supervisor,
        $firma_tecnico,
        json_encode($fotos_guardadas),
        $_SESSION['usuario_id'],
        $mantenimiento_id
    ]);

    // Redirigir a PDF
    header("Location: guardar_reporte_servicio.php?id=$mantenimiento_id");
    exit;
}


// 🚩 Si es GET → Mostrar formulario
$id = $_GET['id'] ?? null;
if (!$id) die('ID no proporcionado');

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

// Preparar equipos del mantenimiento
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

<table border="1" cellspacing="0" cellpadding="4" width="100%" style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px;">
  <tr>
    <!-- Logo -->
    <td width="20%" align="center">
      <img src="/../../lib/logo.jpeg" alt="Logo" style="max-height:60px;">
    </td>
    
    <!-- Título y datos -->
    <td width="60%" align="center" style="font-weight: bold; font-size: 13px;">
      <div style="background:#cfe2f3; padding:2px; margin-bottom:3px;">FORMATO DE CALIDAD</div>
      REPORTE DE SERVICIO TECNICO <br>
      <span style="font-weight: normal;">
        Oficina: (01) 6557907 <br>
        Emergencias: +51 943 048 606 <br>
        ventas@refriservissac.com
      </span>
    </td>
    
    <!-- Número de reporte -->
     
    <td width="20%" align="center" style="font-size: 12px;">
        <div style="background:#cfe2f3; padding:2px; margin-bottom:3px;">FORMATO DE CALIDAD</div>
        <br>
        <br>
      001-N°<?php echo str_pad($id, 6, "0", STR_PAD_LEFT); ?>
      <br>
      <br>
      <br>
    </td>
  </tr>
</table>



  <div class="card mb-3 p-3">
    <div><strong>CLIENTE:</strong> <?=htmlspecialchars($m['cliente'] ?? '-')?></div>
    <div><strong>DIRECCIÓN:</strong> <?=htmlspecialchars($m['direccion'] ?? '-')?></div>
    <div><strong>RESPONSABLE:</strong> <?=htmlspecialchars($m['responsable'] ?? '-')?></div>
    <div><strong>FECHA:</strong> <?=htmlspecialchars($m['fecha'] ?? date('Y-m-d'))?></div>
  </div>

  <form action="servicio.php"  id="formReporte" method="post" enctype="multipart/form-data" class="mb-5">
    <input type="hidden" name="mantenimiento_id" value="<?=htmlspecialchars($m['id'])?>">

    <!-- TABLA DE EQUIPOS -->
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

    <!-- TABLA DE PARÁMETROS -->
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

    <!-- TRABAJOS / OBSERVACIONES -->
    <div class="mb-3">
      <label>Trabajos realizados</label>
      <textarea class="form-control" name="trabajos" rows="4"></textarea>
    </div>

    <div class="mb-3">
      <label>Observaciones y recomendaciones</label>
      <textarea class="form-control" name="observaciones" rows="3"></textarea>
    </div>

    <!-- FOTOS -->
    <div class="mb-3">
      <label>Fotos del/los equipos (múltiples)</label>
      <input type="file" class="form-control" name="fotos[]" accept="image/*" multiple>
    </div>

    <!-- FIRMAS -->
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

$(document).ready(function(){
  $('.equipo-select').select2({ placeholder:"Buscar equipo...", allowClear:true, width:'100%' });

  // Cargar datos de equipos seleccionados
  $('.equipo-select').each(function(){
    let id = $(this).val();
    let index = $(this).data('index');
    if(id){
      $.getJSON('/operador/ajax_get_equipo.php', { id_equipo: id }, function(data){
        if(data.success){
          $(`.marca-${index}`).val(data.marca || '');
          $(`.modelo-${index}`).val(data.modelo || '');
          $(`.ubicacion-${index}`).val(data.ubicacion || '');
          $(`.voltaje-${index}`).val(data.voltaje || '');
        }
      });
    }
  });

  // Cuando cambia un equipo
  $('.equipo-select').on('change', function(){
    let id = $(this).val();
    let index = $(this).data('index');
    if(!id) {
      $(`.marca-${index}, .modelo-${index}, .ubicacion-${index}, .voltaje-${index}`).val('');
      return;
    }
    $.getJSON('/operador/ajax_get_equipo.php', { id_equipo: id }, function(data){
      if(data.success){
        $(`.marca-${index}`).val(data.marca || '');
        $(`.modelo-${index}`).val(data.modelo || '');
        $(`.ubicacion-${index}`).val(data.ubicacion || '');
        $(`.voltaje-${index}`).val(data.voltaje || '');
      }
    });
  });
});
</script>
</body>
</html>
