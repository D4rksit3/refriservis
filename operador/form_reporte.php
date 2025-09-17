<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header("Location: /index.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT m.id, m.titulo, c.nombre AS cliente, i.nombre AS inventario, m.fecha 
                       FROM mantenimientos m
                       LEFT JOIN clientes c ON c.id = m.cliente_id
                       LEFT JOIN inventario i ON i.id = m.inventario_id
                       WHERE m.id = ?");
$stmt->execute([$id]);
$mantenimiento = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$mantenimiento) {
    die("Mantenimiento no encontrado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Informe de Mantenimiento</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    #firma { border: 2px solid #000; border-radius: 8px; touch-action: none; width: 100%; height: 180px; }
  </style>
</head>
<body class="bg-light">
<div class="container py-3">
  <div class="card shadow-sm">
    <div class="card-body">
      <h5 class="card-title mb-3">Informe de Mantenimiento</h5>

      <form method="POST" action="guardar_informe.php">
        <input type="hidden" name="mantenimiento_id" value="<?= $mantenimiento['id'] ?>">

        <div class="mb-2">
          <label class="form-label">Cliente</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($mantenimiento['cliente']) ?>" disabled>
        </div>

        <div class="mb-2">
          <label class="form-label">Equipo</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($mantenimiento['inventario']) ?>" disabled>
        </div>

        <div class="mb-2">
          <label class="form-label">Fecha</label>
          <input type="text" class="form-control" value="<?= $mantenimiento['fecha'] ?>" disabled>
        </div>

        <div class="mb-2">
          <label for="trabajos" class="form-label">Trabajos Realizados</label>
          <textarea class="form-control" name="trabajos" id="trabajos" required></textarea>
        </div>

        <div class="mb-2">
          <label for="observaciones" class="form-label">Observaciones</label>
          <textarea class="form-control" name="observaciones" id="observaciones"></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Firma</label><br>
          <canvas id="firma"></canvas>
          <input type="hidden" name="firma" id="firmaInput">
          <div class="mt-2 d-flex gap-2">
            <button type="button" id="borrarFirma" class="btn btn-sm btn-danger">Borrar</button>
          </div>
        </div>

        <button type="submit" class="btn btn-success w-100">Guardar y Generar PDF</button>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
  const canvas = document.getElementById('firma');
  const signaturePad = new SignaturePad(canvas);

  document.querySelector("form").addEventListener("submit", function(e) {
    if (!signaturePad.isEmpty()) {
      document.getElementById('firmaInput').value = signaturePad.toDataURL('image/png');
    } else {
      alert("Por favor, firme antes de guardar el informe.");
      e.preventDefault();
    }
  });

  document.getElementById('borrarFirma').addEventListener('click', function() {
    signaturePad.clear();
  });
</script>
</body>
</html>
