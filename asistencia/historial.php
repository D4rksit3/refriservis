<?php include("db.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Historial de Asistencias</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h3 class="text-center mb-4">Historial de Asistencias</h3>

  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>Empleado</th>
        <th>Documento</th>
        <th>Tipo</th>
        <th>Fecha</th>
        <th>Ubicación</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $stmt = $pdo->query("SELECT a.*, u.nombre, u.documento 
                           FROM asistencias a 
                           JOIN usuarios u ON a.usuario_id = u.id 
                           ORDER BY a.fecha DESC");
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>
                <td>{$row['nombre']}</td>
                <td>{$row['documento']}</td>
                <td><span class='badge bg-" . 
                  ($row['tipo']=='entrada'?'success':($row['tipo']=='salida'?'danger':'info')) . 
                "'>{$row['tipo']}</span></td>
                <td>{$row['fecha']}</td>
                <td>
                  <a href='https://www.google.com/maps?q={$row['latitud']},{$row['longitud']}' target='_blank'>
                    Ver mapa
                  </a>
                </td>
              </tr>";
      }
      ?>
    </tbody>
  </table>

  <div class="text-center mt-3">
    <a href="index.php" class="btn btn-outline-primary">Volver</a>
  </div>
</div>
</body>
</html>
