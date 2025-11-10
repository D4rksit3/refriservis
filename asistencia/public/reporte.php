<?php
// public/reporte.php
require_once __DIR__ . '/../api/conexion.php';
require_once __DIR__ . '/../api/funciones.php';

$usuario = $_GET['user'] ?? null;
$fecha = $_GET['date'] ?? date('Y-m-d');

// listado usuarios para filtro
$users = $pdo->query("SELECT id, nombre, dni FROM usuarios WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

$selectedUser = $usuario ?? ($users[0]['id'] ?? null);
$resumen = $selectedUser ? generarResumenDiario($pdo, $selectedUser, $fecha) : null;
$marcaciones = $selectedUser ? getMarcacionesByUserDate($pdo, $selectedUser, $fecha) : [];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Reporte Diario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <h3>Reporte Diario</h3>
  <form method="get" class="row g-2 mb-3">
    <div class="col-auto">
      <select name="user" class="form-select">
        <?php foreach($users as $u): ?>
        <option value="<?=$u['id']?>" <?=($u['id']==$selectedUser)?'selected':''?>><?=htmlspecialchars($u['nombre'].' ('.$u['dni'].')')?></option>
        <?php endforeach;?>
      </select>
    </div>
    <div class="col-auto">
      <input type="date" name="date" class="form-control" value="<?=$fecha?>">
    </div>
    <div class="col-auto">
      <button class="btn btn-primary">Ver</button>
    </div>
    <div class="col-auto">
      <a href="map_dia.php?user=<?=$selectedUser?>&date=<?=$fecha?>" class="btn btn-outline-secondary" target="_blank">Ver en Mapa</a>
    </div>
  </form>

  <?php if ($resumen): ?>
    <div class="card mb-3">
      <div class="card-body">
        <h5>Resumen (<?=$fecha?>)</h5>
        <p><strong>Horario asignado:</strong> <?=$resumen['horario_inicio']?> - <?=$resumen['horario_fin']?></p>
        <p><strong>Inicio:</strong> <?= $resumen['inicio'] ?? '-' ?> &nbsp; <strong>Fin:</strong> <?= $resumen['fin'] ?? '-' ?></p>
        <p><strong>Inicio Break:</strong> <?= $resumen['inicio_refrigerio'] ?? '-' ?> &nbsp; <strong>Fin Break:</strong> <?= $resumen['fin_refrigerio'] ?? '-' ?></p>
        <p><strong>Entrada campo:</strong> <?= $resumen['entrada_campo'] ?? '-' ?> &nbsp; <strong>Salida campo:</strong> <?= $resumen['salida_campo'] ?? '-' ?></p>
        <p><strong>Tardanza:</strong> <?= $resumen['tardanza_min'] ?> min &nbsp; <strong>Salida anticipada:</strong> <?= $resumen['salida_anticipada_min'] ?> min</p>
        <p><strong>Duración trabajo (min):</strong> <?= $resumen['duracion_trabajo_min'] ?> &nbsp; <strong>Break (min):</strong> <?= $resumen['duracion_break_min'] ?> &nbsp; <strong>Tiempo campo (min):</strong> <?= $resumen['tiempo_campo_min'] ?></p>
      </div>
    </div>

    <h5>Marcaciones del día</h5>
    <table class="table table-striped">
      <thead class="table-dark">
        <tr><th>Hora</th><th>Tipo</th><th>Dirección</th><th>Distrito</th><th>Mapa</th></tr>
      </thead>
      <tbody>
        <?php foreach($marcaciones as $m): ?>
        <tr>
          <td><?=$m['hora']?></td>
          <td><?=$m['tipo']?></td>
          <td><?=htmlspecialchars($m['direccion'])?></td>
          <td><?=htmlspecialchars($m['distrito'])?></td>
          <td><a href="https://www.google.com/maps?q=<?=$m['latitud']?>,<?=$m['longitud']?>" target="_blank">Ver</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="alert alert-warning">Seleccione un usuario válido.</div>
  <?php endif; ?>
</div>
</body>
</html>
