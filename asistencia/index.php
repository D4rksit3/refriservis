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
      --color-bg: #f9fafb;
      --color-primary: #004085;
      --color-text: #1a1a1a;
      --color-placeholder: #999;
    }

    body {
      background-color: #ffffff;
      font-family: "Inter", system-ui, -apple-system, sans-serif;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      padding: 20px;
    }

    .container-form {
      width: 100%;
      max-width: 420px;
    }

    .brand {
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .brand img {
      height: 100px;
      object-fit: contain;
    }

    h4 {
      font-weight: 600;
      color: var(--color-text);
      text-align: center;
      margin-bottom: 2rem;
    }

    .form-control, .form-select {
      border: none;
      border-bottom: 1.5px solid #f0e9e9ff;
      border-radius: 0;
      background: transparent;
      font-size: 1rem;
      padding: 10px 5px;
      box-shadow: none !important;
      color: var(--color-text);
    }

    .form-control:focus, .form-select:focus {
      border-color: var(--color-primary);
      outline: none;
    }

    label {
      font-size: 0.9rem;
      color: var(--color-placeholder);
      margin-top: 10px;
    }

    .btn-submit {
      background-color: var(--color-primary);
      color: #ffffffff;
      border: none;
      border-radius: 8px;
      padding: 12px;
      width: 100%;
      font-weight: 600;
      margin-top: 1.8rem;
      transition: background 0.3s ease;
    }

    .btn-submit:hover {
      background-color: #002e66;
    }

    .link-muted {
      text-decoration: none;
      color: #777;
      font-size: 0.9rem;
      display: inline-block;
      margin-top: 15px;
    }

    .link-muted:hover {
      color: var(--color-primary);
    }

    @media (max-width: 576px) {
      body {
        height: auto;
        padding: 40px 20px;
      }
      h4 {
        font-size: 1.3rem;
        margin-bottom: 1.5rem;
      }
      .btn-submit {
        font-size: 1rem;
      }
    }
  </style>
</head>
<body>

  <div class="container-form">
    <div class="brand">
      <img src="logo.jpeg" alt="Logo">
    </div>

    <h4>Registro de Asistencia</h4>

    <form action="marcar.php" method="POST" autocomplete="off">
      <div class="mb-3">
        <label>Documento del empleado</label>
        <input type="text" name="documento" class="form-control" placeholder="Ingrese su DNI o código" required>
      </div>

      <div class="mb-3">
        <label>Tipo de marcación</label>
        <select name="tipo" class="form-select" required>
          <option value="">Seleccione...</option>
          <option value="entrada">Entrada</option>
          <option value="salida">Salida</option>
          <option value="visita">Visita</option>
        </select>
      </div>

      <input type="hidden" name="latitud" id="latitud">
      <input type="hidden" name="longitud" id="longitud">

      <button type="submit" class="btn-submit">Marcar Asistencia</button>
    </form>

    <div class="text-center">
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
