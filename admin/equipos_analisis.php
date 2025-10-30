<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// =============================
// Cabecera y sesión
// =============================
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
  header('Location: /index.php');
  exit;
}
require_once __DIR__.'/../config/db.php';


// =============================
// Datos generales
// =============================
$cuentas = [
  'usuarios' => $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(),
  'clientes' => $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn(),
  'equipos' => $pdo->query('SELECT COUNT(*) FROM equipos')->fetchColumn(),
];

// =============================
// FILTROS
// =============================
$filtroCliente = $_GET['cliente'] ?? '';
$filtroFechaInicio = $_GET['inicio'] ?? '';
$filtroFechaFin = $_GET['fin'] ?? '';
$filtroEstado = $_GET['estado'] ?? '';

$where = [];
$params = [];

if ($filtroCliente !== '') {
  $where[] = 'c.id = ?';
  $params[] = $filtroCliente;
}
if ($filtroFechaInicio && $filtroFechaFin) {
  $where[] = 'm.fecha BETWEEN ? AND ?';
  $params[] = $filtroFechaInicio;
  $params[] = $filtroFechaFin;
}
if ($filtroEstado !== '') {
  $where[] = 'm.estado = ?';
  $params[] = $filtroEstado;
}

$whereSQL = $where ? 'WHERE '.implode(' AND ', $where) : '';

// =============================
// Ranking de equipos más usados
// =============================
$sqlRanking = "
SELECT e.id_equipo, e.Identificador, e.Nombre, COUNT(m.id) AS total_mantenimientos
FROM equipos e
JOIN mantenimientos m
  ON e.id_equipo IN (m.equipo1, m.equipo2, m.equipo3, m.equipo4, m.equipo5, m.equipo6, m.equipo7)
LEFT JOIN clientes c ON c.id = m.cliente_id
$whereSQL
GROUP BY e.id_equipo
ORDER BY total_mantenimientos DESC
LIMIT 10
";
$stmtRanking = $pdo->prepare($sqlRanking);
$stmtRanking->execute($params);
$rankingEquipos = $stmtRanking->fetchAll(PDO::FETCH_ASSOC);

// =============================
// Historial detallado de mantenimientos
// =============================
$sqlHistorial = "
SELECT m.id, m.titulo, m.fecha, m.estado, m.nombre_tecnico, c.nombre AS cliente,
GROUP_CONCAT(e.Nombre SEPARATOR ', ') AS equipos
FROM mantenimientos m
LEFT JOIN clientes c ON c.id = m.cliente_id
LEFT JOIN equipos e ON e.id_equipo IN (m.equipo1, m.equipo2, m.equipo3, m.equipo4, m.equipo5, m.equipo6, m.equipo7)
$whereSQL
GROUP BY m.id
ORDER BY m.fecha DESC
LIMIT 50
";
$stmtHistorial = $pdo->prepare($sqlHistorial);
$stmtHistorial->execute($params);
$historial = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);

// =============================
// Datos para gráfico
// =============================
$labels = [];
$values = [];
foreach ($rankingEquipos as $r) {
  $labels[] = $r['Nombre'] ?: $r['Identificador'];
  $values[] = $r['total_mantenimientos'];
}
?>

<div class="container my-4">
  <h2 class="mb-4">📊 Análisis de Equipos</h2>

  <!-- Filtros -->
  <form method="GET" class="row g-3 mb-4">
    <div class="col-md-3">
      <label class="form-label">Cliente</label>
      <select name="cliente" class="form-select">
        <option value="">Todos</option>
        <?php
        $clientes = $pdo->query("SELECT id, nombre FROM clientes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($clientes as $cl) {
          $sel = ($filtroCliente == $cl['id']) ? 'selected' : '';
          echo "<option value='{$cl['id']}' $sel>{$cl['nombre']}</option>";
        }
        ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Desde</label>
      <input type="date" name="inicio" class="form-control" value="<?=htmlspecialchars($filtroFechaInicio)?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Hasta</label>
      <input type="date" name="fin" class="form-control" value="<?=htmlspecialchars($filtroFechaFin)?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Estado</label>
      <select name="estado" class="form-select">
        <option value="">Todos</option>
        <option value="pendiente" <?=($filtroEstado=='pendiente'?'selected':'')?>>Pendiente</option>
        <option value="en proceso" <?=($filtroEstado=='en proceso'?'selected':'')?>>En proceso</option>
        <option value="finalizado" <?=($filtroEstado=='finalizado'?'selected':'')?>>Finalizado</option>
      </select>
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button class="btn btn-primary w-100">Filtrar</button>
    </div>
  </form>

  <!-- Ranking de equipos -->
  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white">🏆 Equipos más usados en mantenimientos</div>
    <div class="card-body">
      <canvas id="graficoEquipos" height="100"></canvas>
      <table class="table table-striped table-hover mt-3">
        <thead>
          <tr>
            <th>#</th>
            <th>Identificador</th>
            <th>Nombre</th>
            <th>Total mantenimientos</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rankingEquipos as $i => $eq): ?>
            <tr>
              <td><?=$i+1?></td>
              <td><?=htmlspecialchars($eq['Identificador'])?></td>
              <td><?=htmlspecialchars($eq['Nombre'])?></td>
              <td><?=$eq['total_mantenimientos']?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Historial -->
  <div class="card shadow-sm">
    <div class="card-header bg-secondary text-white">📘 Historial de Mantenimientos</div>
    <div class="card-body">
      <table class="table table-bordered table-hover table-sm">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Título</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Cliente</th>
            <th>Equipos involucrados</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($historial as $h): ?>
            <tr>
              <td><?=$h['id']?></td>
              <td><?=htmlspecialchars($h['titulo'])?></td>
              <td><?=$h['fecha']?></td>
              <td><?=ucfirst($h['estado'])?></td>
              <td><?=htmlspecialchars($h['cliente'])?></td>
              <td><?=htmlspecialchars($h['equipos'])?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- =============================
     Gráfico (Chart.js)
============================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('graficoEquipos');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?=json_encode($labels)?>,
    datasets: [{
      label: 'Mantenimientos',
      data: <?=json_encode($values)?>,
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    scales: { y: { beginAtZero: true } }
  }
});
</script>
