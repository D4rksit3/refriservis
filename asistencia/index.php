<?php include("db.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Sistema de Asistencia</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #4e73df 0%, #1cc88a 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: "Segoe UI", sans-serif;
    }
    .card {
      max-width: 400px;
      width: 100%;
      border: none;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.15);
      animation: fadeIn 0.6s ease;
    }
    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(20px);}
      to {opacity: 1; transform: translateY(0);}
    }
    .btn-primary {
      background-color: #4e73df;
      border: none;
      transition: background-color 0.3s;
    }
    .btn-primary:hover {
      background-color: #2e59d9;
    }
    .text-muted a {
      text-decoration: none;
    }
    .text-muted a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="card p-4 text-center bg-white">
    <h4 class="mb-3 text-dark fw-bold">Registro de Asistencia</h4>
    <form action="marcar.php" method="POST">
      <div class="mb-3 text-start">
        <label class="form-label">Documento del empleado</label>
        <input type="text" name="documento" class="form-control" placeholder="Ingrese su DNI o código" required>
      </div>

      <div class="mb-3 text-start">
        <label class="form-label">Tipo de marcación</label>
        <select name="tipo" class="form-select" required>
          <option value="">Seleccione una opción</option>
          <option value="entrada">Entrada</option>
          <option value="salida">Salida</option>
          <option value="visita">Visita</option>
        </select>
      </div>

      <input type="hidden" name="latitud" id="latitud">
      <input type="hidden" name="longitud" id="longitud">

      <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Marcar Asistencia</button>
    </form>

    <div class="mt-3 text-muted">
      <a href="historial.php">📋 Ver historial</a>
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
