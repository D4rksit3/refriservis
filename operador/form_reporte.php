<?php
// operador/form_reporte.php
session_start();
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] !== 'operador' && $_SESSION['rol'] !== 'tecnico')) {
    header('Location: /index.php'); exit;
}

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

$mantenimiento_id = (int)($_GET['id'] ?? 0);
if (!$mantenimiento_id) {
    die("Mantenimiento no especificado.");
}

/* Traer datos del mantenimiento + cliente + inventario */
$stmt = $pdo->prepare("
  SELECT m.*, c.cliente AS cliente_nombre, c.direccion AS cliente_direccion, c.telefono AS cliente_telefono, c.responsable AS cliente_responsable, 
         c.email AS cliente_email,
         i.nombre AS equipo, i.marca, i.modelo, i.serie, i.gas, i.codigo
  FROM mantenimientos m
  LEFT JOIN clientes c ON c.id = m.cliente_id
  LEFT JOIN inventario i ON i.id = m.inventario_id
  WHERE m.id = ?
");
$stmt->execute([$mantenimiento_id]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$datos) { die("Mantenimiento no encontrado."); }

/* Ver si ya existe un reporte para esta orden (carga automática) */
$reporte = null;
$stmt = $pdo->prepare("SELECT * FROM reportes WHERE mantenimiento_id = ? LIMIT 1");
$stmt->execute([$mantenimiento_id]);
$reporte = $stmt->fetch(PDO::FETCH_ASSOC);

$parametros = [];
if ($reporte) {
    $stmt = $pdo->prepare("SELECT * FROM parametros_funcionamiento WHERE reporte_id = ? ORDER BY id");
    $stmt->execute([$reporte['id']]);
    $parametros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Plantilla de medidas por defecto
    $parametros = [
      ['medida'=>'Corriente electrica nominal (A)'],
      ['medida'=>'Tension electrica nominal (V)'],
      ['medida'=>'Presion de descarga (PSI)'],
      ['medida'=>'Presion de succion (PSI)']
    ];
}
?>

<div class="container py-4">
  <div class="card">
    <div class="card-body">
      <h3 class="mb-3">Reporte de Servicio Técnico
        <?php if ($reporte): ?><small class="text-muted"> — Reporte existente #<?= $reporte['id'] ?></small><?php endif; ?>
      </h3>

      <form action="/operador/guardar_informe.php" method="post" enctype="multipart/form-data" id="formReporte">
        <input type="hidden" name="mantenimiento_id" value="<?= htmlspecialchars($mantenimiento_id) ?>">
        <?php if ($reporte): ?>
          <input type="hidden" name="reporte_id" value="<?= htmlspecialchars($reporte['id']) ?>">
        <?php endif; ?>

        <!-- Datos auto -->
        <div class="row mb-3">
          <div class="col-md-6">
            <h6>Cliente</h6>
            <p class="mb-1"><strong><?= htmlspecialchars($datos['cliente_nombre']) ?></strong></p>
            <p class="mb-1"><?= htmlspecialchars($datos['cliente_direccion']) ?></p>
            <p class="mb-1">Contacto: <?= htmlspecialchars($datos['cliente_responsable']) ?> — <?= htmlspecialchars($datos['cliente_telefono']) ?></p>
            <p class="mb-1">Email: <?= htmlspecialchars($datos['cliente_email']) ?></p>
          </div>
          <div class="col-md-6">
            <h6>Equipo</h6>
            <p class="mb-1"><strong><?= htmlspecialchars($datos['equipo']) ?></strong></p>
            <p class="mb-1">Marca: <?= htmlspecialchars($datos['marca']) ?> — Modelo: <?= htmlspecialchars($datos['modelo']) ?></p>
            <p class="mb-1">Serie: <?= htmlspecialchars($datos['serie']) ?> — Gas: <?= htmlspecialchars($datos['gas']) ?></p>
            <p class="mb-1">Código: <?= htmlspecialchars($datos['codigo']) ?></p>
            <p class="mb-1">Fecha de orden: <?= htmlspecialchars($datos['fecha']) ?></p>
          </div>
        </div>

        <!-- Trabajos y observaciones -->
        <div class="mb-3">
          <label class="form-label">Trabajos realizados</label>
          <textarea name="trabajos" class="form-control" rows="4" required><?= $reporte['trabajos'] ?? '' ?></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Observaciones</label>
          <textarea name="observaciones" class="form-control" rows="3"><?= $reporte['observaciones'] ?? '' ?></textarea>
        </div>

        <!-- Parámetros -->
        <h5 class="mt-4">Parámetros de funcionamiento</h5>
        <div class="table-responsive mb-3">
          <table class="table table-bordered align-middle text-center">
            <thead class="table-light">
              <tr>
                <th>Medida</th>
                <th>Antes 1</th>
                <th>Después 1</th>
                <th>Antes 2</th>
                <th>Después 2</th>
              </tr>
            </thead>
            <tbody id="parametrosBody">
              <?php
                if ($parametros) {
                  foreach ($parametros as $p):
                    // para compatibilidad: si vienen columnas, usarlas
                    $antes1 = $p['antes1'] ?? '';
                    $despues1 = $p['despues1'] ?? '';
                    $antes2 = $p['antes2'] ?? '';
                    $despues2 = $p['despues2'] ?? '';
                    $medida = $p['medida'] ?? ($p['medida'] ?? '');
              ?>
                <tr>
                  <td>
                    <input type="hidden" name="medida[]" value="<?= htmlspecialchars($medida) ?>">
                    <?= htmlspecialchars($medida) ?>
                  </td>
                  <td><input class="form-control" name="antes1[]" value="<?=htmlspecialchars($antes1)?>"></td>
                  <td><input class="form-control" name="despues1[]" value="<?=htmlspecialchars($despues1)?>"></td>
                  <td><input class="form-control" name="antes2[]" value="<?=htmlspecialchars($antes2)?>"></td>
                  <td><input class="form-control" name="despues2[]" value="<?=htmlspecialchars($despues2)?>"></td>
                </tr>
              <?php endforeach; } ?>
            </tbody>
          </table>
        </div>

        <!-- Fotos (files) -->
        <div class="mb-3">
          <label class="form-label">Fotos (puedes subir varias)</label>
          <input class="form-control" type="file" name="fotos[]" accept="image/*" multiple>
        </div>

        <!-- Firmas: canvas -->
        <h5 class="mt-4">Firmas</h5>
        <div class="row">
          <?php
            // Si ya hay firmas guardadas, las mostramos como imagen de referencia
            $firma_cliente_src = $reporte['firma_cliente'] ? "/uploads/".basename($reporte['firma_cliente']) : '';
            $firma_tecnico_src = $reporte['firma_tecnico'] ? "/uploads/".basename($reporte['firma_tecnico']) : '';
            $firma_supervisor_src = $reporte['firma_supervisor'] ? "/uploads/".basename($reporte['firma_supervisor']) : '';
          ?>
          <div class="col-md-4 mb-3">
            <label>Firma Cliente</label>
            <canvas id="firmaCliente" class="border" width="400" height="160"></canvas>
            <input type="hidden" id="firmaClienteInput" name="firma_cliente_dataurl" value="">
            <?php if ($firma_cliente_src): ?><div class="mt-2"><small>Firma guardada:</small><br><img src="<?=htmlspecialchars($firma_cliente_src)?>" style="max-width:100%;height:70px"></div><?php endif; ?>
            <div class="mt-1">
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limpiarFirma('firmaCliente')">Limpiar</button>
            </div>
          </div>

          <div class="col-md-4 mb-3">
            <label>Firma Técnico</label>
            <canvas id="firmaTecnico" class="border" width="400" height="160"></canvas>
            <input type="hidden" id="firmaTecnicoInput" name="firma_tecnico_dataurl" value="">
            <?php if ($firma_tecnico_src): ?><div class="mt-2"><small>Firma guardada:</small><br><img src="<?=htmlspecialchars($firma_tecnico_src)?>" style="max-width:100%;height:70px"></div><?php endif; ?>
            <div class="mt-1">
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limpiarFirma('firmaTecnico')">Limpiar</button>
            </div>
          </div>

          <div class="col-md-4 mb-3">
            <label>Firma Supervisor</label>
            <canvas id="firmaSupervisor" class="border" width="400" height="160"></canvas>
            <input type="hidden" id="firmaSupervisorInput" name="firma_supervisor_dataurl" value="">
            <?php if ($firma_supervisor_src): ?><div class="mt-2"><small>Firma guardada:</small><br><img src="<?=htmlspecialchars($firma_supervisor_src)?>" style="max-width:100%;height:70px"></div><?php endif; ?>
            <div class="mt-1">
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limpiarFirma('firmaSupervisor')">Limpiar</button>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <button class="btn btn-success" type="submit">Guardar Informe</button>
          <?php if ($reporte): ?>
            <a class="btn btn-outline-primary" href="/operador/reporte.php?id=<?= $reporte['id'] ?>" target="_blank">Ver Reporte</a>
          <?php endif; ?>
        </div>

      </form>
    </div>
  </div>
</div>

<!-- scripts -->
<script>
/* Canvas signing helper */
function initFirma(canvasId, inputId) {
  const canvas = document.getElementById(canvasId);
  const input = document.getElementById(inputId);
  const ctx = canvas.getContext("2d");
  ctx.lineWidth = 2;
  ctx.lineCap = "round";
  let drawing = false;
  let last = {x:0,y:0};

  function pos(e) {
    const rect = canvas.getBoundingClientRect();
    let clientX = e.touches ? e.touches[0].clientX : e.clientX;
    let clientY = e.touches ? e.touches[0].clientY : e.clientY;
    return { x: clientX - rect.left, y: clientY - rect.top };
  }

  function start(e) {
    drawing = true;
    const p = pos(e);
    last = p;
    ctx.beginPath();
    ctx.moveTo(p.x, p.y);
    e.preventDefault();
  }
  function move(e) {
    if (!drawing) return;
    const p = pos(e);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
    last = p;
    e.preventDefault();
  }
  function end(e) {
    drawing = false;
    input.value = canvas.toDataURL("image/png");
    ctx.beginPath();
  }

  canvas.addEventListener("mousedown", start);
  canvas.addEventListener("mousemove", move);
  canvas.addEventListener("mouseup", end);
  canvas.addEventListener("mouseout", end);
  canvas.addEventListener("touchstart", start, {passive:false});
  canvas.addEventListener("touchmove", move, {passive:false});
  canvas.addEventListener("touchend", end, {passive:false});
}

function limpiarFirma(canvasId) {
  const canvas = document.getElementById(canvasId);
  const ctx = canvas.getContext("2d");
  ctx.clearRect(0,0,canvas.width,canvas.height);
  const input = document.getElementById(canvasId + 'Input');
  if (input) input.value = '';
}

initFirma('firmaCliente','firmaClienteInput');
initFirma('firmaTecnico','firmaTecnicoInput');
initFirma('firmaSupervisor','firmaSupervisorInput');
</script>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
