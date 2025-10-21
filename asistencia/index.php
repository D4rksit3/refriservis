<?php include("db.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de Asistencia | Refriservis</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --color-bg: #f5f7fa;
      --color-primary: #004085;
      --color-border: #e0e3e7;
      --color-text: #2c3e50;
    }

    body {
      background-color: var(--color-bg);
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      margin: 0;
    }

    .attendance-card {
      background: #fff;
      border: 1px solid var(--color-border);
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
      padding: 2.5rem 2rem;
      width: 100%;
      max-width: 420px;
      transition: box-shadow 0.3s ease;
    }

    .attendance-card:hover {
      box-shadow: 0 6px 28px rgba(0, 0, 0, 0.08);
    }

    .attendance-card h4 {
      font-weight: 600;
      color: var(--color-text);
      text-align: center;
      margin-bottom: 1.8rem;
      letter-spacing: 0.3px;
    }

    .form-label {
      font-weight: 500;
      color: var(--color-text);
    }

    .form-control, .form-select {
      border-radius: 10px;
      border: 1px solid var(--color-border);
      box-shadow: none !important;
      font-size: 0.95rem;
    }

    .form-control:focus, .form-select:focus {
      border-color: var(--color-primary);
    }

    .btn-submit {
      background-color: var(--color-primary);
      color: #fff;
      font-weight: 600;
      border-radius: 10px;
      border: none;
      width: 100%;
      padding: 0.8rem;
      margin-top: 1rem;
      transition: background 0.3s ease;
    }

    .btn-submit:hover {
      background-color: #00336d;
    }

    .link-muted {
      text-decoration: none;
      color: #6c757d;
      font-size: 0.9rem;
    }

    .link-muted:hover {
      color: var(--color-primary);
    }

    .brand {
      text-align: center;
      margin-bottom: 1.2rem;
    }

    .brand img {
      height: 60px;
      border-radius: 12px;
      object-fit: contain;
    }
  </style>
</head>
<body>
  <div class="attendance-card">
    <div class="brand">
      <img src="logo.jpeg" alt="Logo">
    </div>

    <h4>Registro de Asistencia</h4>

    <form action="marcar.php" method="POST" autocomplete="off">
      <div class="mb-3">
        <label class="form-label">Documento del empleado</label>
        <input type="text" name="documento" class="form-control" placeholder="Ej. 12345678" required>
      </div>

      <div class="mb-3">
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

      <button type="submit" class="btn-submit">Marcar Asistencia</button>
    </form>

    <div class="text-center mt-3">
      <a href="historial.php" class="link-muted">📋 Ver historial</a>
    </div>
  </div>

  <script>
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        document.getElementById("latitud").value = pos.coords.latitude;
        document.getElementById("longitud").value = pos.coords.longitude;
      },
      () => alert("No se pudo obtener la ubicación. Active permisos de GPS.")
    );
  </script>
</body>
</html>
