<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$id_equipo = $_GET['id'] ?? null;
if (!$id_equipo) { die("ID de equipo no especificado."); }

$stmt = $pdo->prepare("
SELECT 
    m.id,
    m.titulo,
    m.fecha,
    m.estado,
    m.descripcion,
    m.nombre_cliente,
    m.nombre_supervisor,
    m.creado_en
FROM mantenimientos m
WHERE :id_equipo IN (m.equipo1, m.equipo2, m.equipo3, m.equipo4, m.equipo5, m.equipo6, m.equipo7)
ORDER BY m.fecha DESC
");
$stmt->execute(['id_equipo' => $id_equipo]);
$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

$eq = $pdo->prepare("SELECT * FROM equipos WHERE id_equipo = ?");
$eq->execute([$id_equipo]);
$equipo = $eq->fetch(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Historial del Equipo - RefriServis</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
  <h3 class="text-primary fw-bold mb-3">📘 Historial del Equipo: <?= htmlspecialchars($equipo['Nombre']) ?></h3>
  <div class="card mb-3 shadow-sm">
    <div class="card-body">
      <p><strong>Identificador:</strong> <?= htmlspecialchars($equipo['Identificador']) ?></p>
      <p><strong>Marca:</strong> <?= htmlspecialchars($equipo['marca']) ?> | <strong>Modelo:</strong> <?= htmlspecialchars($equipo['modelo']) ?></p>
      <p><strong>Categoría:</strong> <?= htmlspecialchars($equipo['Categoria']) ?> | <strong>Ubicación:</strong> <?= htmlspecialchars($equipo['ubicacion']) ?></p>
    </div>
  </div>

  <?php if ($historial): ?>
  <table class="table table-striped table-hover">
    <thead class="table-dark">
      <tr>
        <th>#</th>
        <th>Título</th>
        <th>Fecha</th>
        <th>Estado</th>
        <th>Cliente</th>
        <th>Supervisor</th>
        <th>Detalles</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($historial as $i => $m): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($m['titulo']) ?></td>
        <td><?= htmlspecialchars(date('d/m/Y', strtotime($m['fecha']))) ?></td>
        <td><?= htmlspecialchars($m['estado']) ?></td>
        <td><?= htmlspecialchars($m['nombre_cliente']) ?></td>
        <td><?= htmlspecialchars($m['nombre_supervisor']) ?></td>
        <td><button class="btn btn-sm btn-outline-info" onclick="alert(`<?= addslashes($m['descripcion']) ?>`)">Ver</button></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <div class="alert alert-warning text-center">No se encontraron mantenimientos asociados a este equipo.</div>
  <?php endif; ?>

  <a href="equipos_analisis.php" class="btn btn-secondary mt-3">⬅ Volver</a>
</div>
</body>
</html>
