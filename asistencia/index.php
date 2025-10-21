<?php include("db.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Sistema de Asistencia</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <h2 class="text-center mb-4">Registro de Asistencia</h2>
  <form action="marcar.php" method="POST" class="card p-4 shadow">
    <div class="mb-3">
      <label>Documento del empleado:</label>
      <input type="text" name="documento" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Tipo de marcación:</label>
      <select name="tipo" class="form-select" required>
        <option value="entrada">Entrada</option>
        <option value="salida">Salida</option>
        <option value="visita">Visita</option>
      </select>
    </div>

    <input type="hidden" name="latitud" id="latitud">
    <input type="hidden" name="longitud" id="longitud">

    <button type="submit" class="btn btn-primary w-100">Marcar Asistencia</button>
  </form>

  <div class="text-center mt-3">
    <a href="historial.php" class="btn btn-outline-secondary">Ver Historial</a>
  </div>
</div>

<script>
navigator.geolocation.getCurrentPosition(
  (pos) => {
    document.getElementById("latitud").value = pos.coords.latitude;
    document.getElementById("longitud").value = pos.coords.longitude;
  },
  (err) => alert("No se pudo obtener la ubicación. Habilite el GPS o permisos de ubicación.")
);
</script>
</body>
</html>
