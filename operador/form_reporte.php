<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/../includes/header.php';

session_start();
require_once __DIR__.'/../config/db.php';

$mantenimiento_id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("
    SELECT m.id, m.titulo, m.fecha, c.nombre AS cliente, c.direccion, 
           i.nombre AS equipo, i.marca, i.modelo, i.serie, i.gas, i.codigo
    FROM mantenimientos m
    LEFT JOIN clientes c ON m.cliente_id = c.id
    LEFT JOIN inventario i ON m.inventario_id = i.id
    WHERE m.id = ?
");
$stmt->execute([$mantenimiento_id]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$datos) {
    die("Mantenimiento no encontrado");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Generar Reporte</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    canvas {
      border: 1px solid #ccc;
      width: 100%;
      height: 200px;
    }
  </style>
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="card shadow-sm">
    <div class="card-body">
      <h3 class="mb-3">Reporte de Servicio Técnico</h3>
      
      <form action="guardar_informe.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="mantenimiento_id" value="<?= $datos['id'] ?>">

        <!-- Datos automáticos -->
        <div class="mb-3">
          <strong>Cliente:</strong> <?= htmlspecialchars($datos['cliente']) ?><br>
          <strong>Dirección:</strong> <?= htmlspecialchars($datos['direccion']) ?><br>
          <strong>Equipo:</strong> <?= htmlspecialchars($datos['equipo']) ?><br>
          <strong>Marca:</strong> <?= htmlspecialchars($datos['marca']) ?><br>
          <strong>Modelo:</strong> <?= htmlspecialchars($datos['modelo']) ?><br>
          <strong>Serie:</strong> <?= htmlspecialchars($datos['serie']) ?><br>
          <strong>Gas:</strong> <?= htmlspecialchars($datos['gas']) ?><br>
          <strong>Código:</strong> <?= htmlspecialchars($datos['codigo']) ?><br>
          <strong>Fecha:</strong> <?= $datos['fecha'] ?>
        </div>

        <!-- Trabajos -->
        <div class="mb-3">
          <label class="form-label">Trabajos Realizados</label>
          <textarea name="trabajos" class="form-control" rows="3" required></textarea>
        </div>

        <!-- Observaciones -->
        <div class="mb-3">
          <label class="form-label">Observaciones</label>
          <textarea name="observaciones" class="form-control" rows="3"></textarea>
        </div>

        <!-- Parámetros de funcionamiento -->
        <h5>Parámetros de Funcionamiento</h5>
        <div class="table-responsive">
          <table class="table table-bordered align-middle text-center">
            <thead class="table-light">
              <tr>
                <th>Medida</th>
                <th>Antes</th>
                <th>Después</th>
                <th>Antes</th>
                <th>Después</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $parametros = [
                "Corriente electrica nominal (A)",
                "Tension electrica nominal (V)",
                "Presion de descarga (PSI)",
                "Presion de succion (PSI)"
              ];
              foreach ($parametros as $p): ?>
                <tr>
                  <td><input type="hidden" name="medida[]" value="<?= $p ?>"><?= $p ?></td>
                  <td><input type="text" name="antes1[]" class="form-control"></td>
                  <td><input type="text" name="despues1[]" class="form-control"></td>
                  <td><input type="text" name="antes2[]" class="form-control"></td>
                  <td><input type="text" name="despues2[]" class="form-control"></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Fotos -->
        <div class="mb-3">
          <label class="form-label">Subir Fotos</label>
          <input type="file" name="fotos[]" multiple accept="image/*" class="form-control">
        </div>

        <!-- Firmas -->
        <h5>Firmas</h5>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Firma Cliente</label>
            <canvas id="firmaCliente"></canvas>
            <input type="hidden" name="firma_cliente" id="firmaClienteInput">
            <button type="button" class="btn btn-sm btn-secondary mt-1" onclick="limpiarFirma('firmaCliente')">Limpiar</button>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Firma Técnico</label>
            <canvas id="firmaTecnico"></canvas>
            <input type="hidden" name="firma_tecnico" id="firmaTecnicoInput">
            <button type="button" class="btn btn-sm btn-secondary mt-1" onclick="limpiarFirma('firmaTecnico')">Limpiar</button>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Firma Supervisor</label>
            <canvas id="firmaSupervisor"></canvas>
            <input type="hidden" name="firma_supervisor" id="firmaSupervisorInput">
            <button type="button" class="btn btn-sm btn-secondary mt-1" onclick="limpiarFirma('firmaSupervisor')">Limpiar</button>
          </div>
        </div>

        <button type="submit" class="btn btn-success w-100">Guardar Informe</button>
      </form>
    </div>
  </div>
</div>

<script>
// Lógica para firmar en canvas
function initFirma(canvasId, inputId) {
  const canvas = document.getElementById(canvasId);
  const input = document.getElementById(inputId);
  const ctx = canvas.getContext("2d");
  let dibujando = false;

  canvas.addEventListener("mousedown", () => dibujando = true);
  canvas.addEventListener("mouseup", () => {
    dibujando = false;
    input.value = canvas.toDataURL("image/png");
  });
  canvas.addEventListener("mousemove", (e) => {
    if (!dibujando) return;
    ctx.lineWidth = 2;
    ctx.lineCap = "round";
    ctx.strokeStyle = "black";
    ctx.lineTo(e.offsetX, e.offsetY);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(e.offsetX, e.offsetY);
  });
}

function limpiarFirma(canvasId) {
  const canvas = document.getElementById(canvasId);
  const ctx = canvas.getContext("2d");
  ctx.clearRect(0, 0, canvas.width, canvas.height);
}

initFirma("firmaCliente","firmaClienteInput");
initFirma("firmaTecnico","firmaTecnicoInput");
initFirma("firmaSupervisor","firmaSupervisorInput");
</script>
</body>
</html>
