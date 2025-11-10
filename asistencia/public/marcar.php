<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Marcar Asistencia</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <h3>Marcación de Asistencia</h3>

  <div class="mb-3">
    <label for="tipo" class="form-label">Tipo de marcación</label>
    <select id="tipo" class="form-select" style="max-width:420px;">
      <option value="entrada">Entrada</option>
      <option value="salida">Salida</option>
      <option value="inicio_refrigerio">Inicio Refrigerio</option>
      <option value="fin_refrigerio">Fin Refrigerio</option>
      <option value="entrada_campo">Entrada a Campo</option>
      <option value="salida_campo">Salida de Campo</option>
      <option value="entrada_tienda">Entrada a Tienda</option>
      <option value="salida_tienda">Salida de Tienda</option>
    </select>
  </div>

  <button id="btnMarcar" class="btn btn-primary">Marcar</button>

  <div id="resultado" class="mt-3"></div>
</div>

<script src="/assets/js/marcar.js"></script>
</body>
</html>
