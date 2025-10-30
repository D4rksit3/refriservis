<?php
// ========================
// Cabecera y validaciones
// ========================
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// Contadores (por si los usas en dashboard)
$cuentas = [
    'usuarios' => $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(),
    'clientes' => $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn(),
    'equipos' => $pdo->query('SELECT COUNT(*) FROM equipos')->fetchColumn(),
];

// ==========================
// FILTROS
// ==========================
$filtros = [];
$condiciones = [];
if (!empty($_GET['categoria'])) {
    $condiciones[] = 'e.Categoria = :categoria';
    $filtros['categoria'] = $_GET['categoria'];
}
if (!empty($_GET['marca'])) {
    $condiciones[] = 'e.marca = :marca';
    $filtros['marca'] = $_GET['marca'];
}
if (!empty($_GET['fecha_inicio']) && !empty($_GET['fecha_fin'])) {
    $condiciones[] = 'm.fecha BETWEEN :fecha_inicio AND :fecha_fin';
    $filtros['fecha_inicio'] = $_GET['fecha_inicio'];
    $filtros['fecha_fin'] = $_GET['fecha_fin'];
}
$where = count($condiciones) ? 'WHERE ' . implode(' AND ', $condiciones) : '';

// ==========================
// CONSULTA PRINCIPAL
// ==========================
$sql = "
SELECT 
    e.id_equipo,
    e.Identificador,
    e.Nombre AS nombre_equipo,
    e.marca,
    e.modelo,
    e.ubicacion,
    e.Categoria,
    e.Descripcion,
    COUNT(m.id) AS total_mantenimientos,
    MAX(m.fecha) AS ultima_fecha
FROM equipos e
LEFT JOIN mantenimientos m 
    ON e.id_equipo IN (
        m.equipo1, m.equipo2, m.equipo3,
        m.equipo4, m.equipo5, m.equipo6, m.equipo7
    )
$where
GROUP BY e.id_equipo
ORDER BY total_mantenimientos DESC, ultima_fecha DESC;
";

$stmt = $pdo->prepare($sql);
foreach ($filtros as $clave => $valor) {
    $stmt->bindValue(":$clave", $valor);
}
$stmt->execute();
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar opciones de filtros dinámicamente
$categorias = $pdo->query("SELECT DISTINCT Categoria FROM equipos WHERE Categoria IS NOT NULL AND Categoria <> ''")->fetchAll(PDO::FETCH_COLUMN);
$marcas = $pdo->query("SELECT DISTINCT marca FROM equipos WHERE marca IS NOT NULL AND marca <> ''")->fetchAll(PDO::FETCH_COLUMN);
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Análisis de Equipos - RefriServis</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .card { border-radius: 12px; }
    .table thead { background: #0d6efd; color: white; }
    .filter-box { background: #fff; border-radius: 12px; box-shadow: 0 3px 6px rgba(0,0,0,.1); }
  </style>
</head>
<body>

<div class="container py-4">
  <h2 class="text-center fw-bold text-primary mb-4">📈 Análisis de Equipos y Mantenimientos</h2>

  <!-- FILTROS -->
  <div class="filter-box p-3 mb-4">
    <form class="row g-3" method="GET">
      <div class="col-md-4">
        <label class="form-label">Categoría</label>
        <select class="form-select" name="categoria">
          <option value="">Todas</option>
          <?php foreach ($categorias as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= (($_GET['categoria'] ?? '') == $c) ? 'selected' : '' ?>>
              <?= htmlspecialchars($c) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">Marca</label>
        <select class="form-select" name="marca">
          <option value="">Todas</option>
          <?php foreach ($marcas as $m): ?>
            <option value="<?= htmlspecialchars($m) ?>" <?= (($_GET['marca'] ?? '') == $m) ? 'selected' : '' ?>>
              <?= htmlspecialchars($m) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">Rango de fechas</label>
        <div class="input-group">
          <input type="date" class="form-control" name="fecha_inicio" value="<?= $_GET['fecha_inicio'] ?? '' ?>">
          <input type="date" class="form-control" name="fecha_fin" value="<?= $_GET['fecha_fin'] ?? '' ?>">
        </div>
      </div>

      <div class="col-12 text-end">
        <button class="btn btn-primary">🔍 Filtrar</button>
        <a href="equipos_analisis.php" class="btn btn-secondary">Limpiar</a>
      </div>
    </form>
  </div>

  <!-- TABLA PRINCIPAL -->
  <div class="card shadow">
    <div class="card-body">
      <?php if ($equipos): ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>Identificador</th>
              <th>Nombre</th>
              <th>Marca</th>
              <th>Modelo</th>
              <th>Categoría</th>
              <th>Ubicación</th>
              <th>Último Mantenimiento</th>
              <th>Total Mantenimientos</th>
              <th>Historial</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($equipos as $i => $eq): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($eq['Identificador']) ?></td>
              <td><?= htmlspecialchars($eq['nombre_equipo']) ?></td>
              <td><?= htmlspecialchars($eq['marca']) ?></td>
              <td><?= htmlspecialchars($eq['modelo']) ?></td>
              <td><?= htmlspecialchars($eq['Categoria']) ?></td>
              <td><?= htmlspecialchars($eq['ubicacion']) ?></td>
              <td><?= $eq['ultima_fecha'] ? date('d/m/Y', strtotime($eq['ultima_fecha'])) : '—' ?></td>
              <td class="fw-bold text-primary text-center"><?= $eq['total_mantenimientos'] ?></td>
              <td>
                <a href="equipo_historial.php?id=<?= $eq['id_equipo'] ?>" class="btn btn-sm btn-outline-primary">📄 Ver</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <div class="alert alert-warning text-center">No hay resultados con los filtros seleccionados.</div>
      <?php endif; ?>
    </div>
  </div>

</div>
</body>
</html>
