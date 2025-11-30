<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['operador', 'digitador', 'admin'])) {
    header('Location: /../index.php');
    exit;
}

require_once __DIR__ . '/../../config/db.php';

// 🚩 Si viene un POST → Guardar reporte en BD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mantenimiento_id = $_POST['mantenimiento_id'];
    $trabajos = $_POST['trabajos'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    $parametros = $_POST['parametros'] ?? [];
    $equipos = $_POST['equipos'] ?? []; 

    // Guardar firmas
    function saveSignature($dataUrl, $name) {
        if (!$dataUrl) return null;
        $data = explode(',', $dataUrl);
        if (count($data) !== 2) return null;
        $decoded = base64_decode($data[1]);
        $dir = __DIR__ . "/../../uploads/firmas/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $file = $dir . "{$name}_" . time() . ".png";
        file_put_contents($file, $decoded);
        return basename($file);
    }
    $firma_cliente = saveSignature($_POST['firma_cliente'] ?? '', 'cliente');
    $firma_supervisor = saveSignature($_POST['firma_supervisor'] ?? '', 'supervisor');
    $firma_tecnico = saveSignature($_POST['firma_tecnico'] ?? '', 'tecnico');

    // Guardar fotos
    $fotos_guardadas = [];
    if (!empty($_FILES['fotos']['name'][0])) {
        $dir = __DIR__ . "/../../uploads/fotos/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        foreach ($_FILES['fotos']['tmp_name'] as $k => $tmp) {
            if (is_uploaded_file($tmp)) {
                $nombre = time() . "_" . $_FILES['fotos']['name'][$k];
                move_uploaded_file($tmp, $dir . $nombre);
                $fotos_guardadas[] = $nombre;
            }
        }
    }
    $equiposGuardados = [];
    for ($i = 1; $i <= 7; $i++) {
        $val = $equipos[$i]['id_equipo'] ?? null;
        $equiposGuardados[$i] = ($val === '' ? null : $val);
    }
        
    $nombre_cliente = $_POST['nombre_cliente'] ?? null;
    $nombre_supervisor = $_POST['nombre_supervisor'] ?? null;
    $nombre_digitador = $_POST['nombre_digitador'] ?? null;

    // ✅ UPDATE en la tabla mantenimientos
    $actividades = $_POST['actividades'] ?? [];
    $actividadesLimpias = [];

    foreach ($actividades as $idx => $act) {
        $dias = $act['dias'] ?? [];
        $frecuencia = $act['frecuencia'] ?? null;
        $actividadesLimpias[$idx] = [
            'dias' => array_keys($dias),
            'frecuencia' => $frecuencia
        ];
    }

    $stmt = $pdo->prepare("UPDATE mantenimientos SET 
        trabajos = ?, 
        observaciones = ?, 
        parametros = ?, 
        actividades = ?, 
        firma_cliente = ?, 
        firma_supervisor = ?, 
        firma_tecnico = ?, 
        nombre_cliente = ?, 
        nombre_supervisor = ?, 
        nombre_digitador = ?,
        fotos = ?, 
        equipo1 = ?, 
        equipo2 = ?, 
        equipo3 = ?, 
        equipo4 = ?, 
        equipo5 = ?, 
        equipo6 = ?, 
        equipo7 = ?, 
        reporte_generado = 1,
        modificado_en = NOW(),
        modificado_por = ?,
        estado = 'finalizado'
        WHERE id = ?");
    $stmt->execute([
        $trabajos,
        $observaciones,
        json_encode($parametros, JSON_UNESCAPED_UNICODE),
        json_encode($actividadesLimpias, JSON_UNESCAPED_UNICODE),
        $firma_cliente,
        $firma_supervisor,
        $firma_tecnico,
        $nombre_cliente,
        $nombre_supervisor,
        $nombre_digitador,
        json_encode($fotos_guardadas, JSON_UNESCAPED_UNICODE),
        $equiposGuardados[1],
        $equiposGuardados[2],
        $equiposGuardados[3],
        $equiposGuardados[4],
        $equiposGuardados[5],
        $equiposGuardados[6],
        $equiposGuardados[7],
        $_SESSION['usuario_id'],
        $mantenimiento_id
    ]);

    $confirmado = $_POST['confirmado'] ?? 'no';
    if($confirmado === 'si'){
        header("Location: https://refriservis.seguricloud.com/operador/mis_mantenimientos.php");
    } else {
        header("Location: guardar_reporte_cortinas.php?id=$mantenimiento_id");
    }
    exit;
}

// 🚩 Si es GET → Mostrar formulario
$id = $_GET['id'] ?? null;
if (!$id) die('ID no proporcionado');

$stmt = $pdo->prepare("
  SELECT 
      m.*, 
      c.cliente, 
      c.direccion, 
      c.responsable, 
      c.telefono,
      u.nombre AS nombre_tecnico,
      dig.nombre AS nombre_digitador
  FROM mantenimientos m
  LEFT JOIN clientes c ON c.id = m.cliente_id
  LEFT JOIN usuarios u ON u.id = m.operador_id
  LEFT JOIN usuarios dig ON dig.id = m.digitador_id
  WHERE m.id = ?
");
$stmt->execute([$id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$m) die('Mantenimiento no encontrado');

// Decodificar actividades guardadas
$actividadesGuardadas = [];
if (!empty($m['actividades'])) {
    $actividadesGuardadas = json_decode($m['actividades'], true) ?: [];
}

$nombre_tecnico = $m['nombre_tecnico'] ?? '';
$nombre_digitador = $m['nombre_digitador'] ?? '';

// Lista de equipos desde inventario
$equiposList = $pdo->query("SELECT id_equipo AS id_equipo, Nombre, Identificador, Marca, Modelo, Ubicacion, Voltaje 
                            FROM equipos ORDER BY Identificador ASC")->fetchAll(PDO::FETCH_ASSOC);

// Preparar equipos del mantenimiento
$equiposMantenimiento = [];
for ($i = 1; $i <= 7; $i++) {
    $eqId = $m["equipo$i"] ?? null;
    $eq = null;
    if ($eqId) {
        foreach ($equiposList as $e) {
            if ($e['id_equipo'] == $eqId) {
                $eq = $e;
                break;
            }
        }
    }
    $equiposMantenimiento[$i] = $eq;
}

include __DIR__ . '/modal_equipo.php';

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Generar Reporte - Mantenimiento #<?=htmlspecialchars($m['id'])?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<style>
  body {
    background: #f5f5f5;
    font-family: Arial, sans-serif;
  }

  .container {
    max-width: 1400px;
  }

  .main-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 20px;
    margin: 20px auto;
  }

  /* Cabecera estilo PDF */
  .pdf-header {
    display: grid;
    grid-template-columns: 80px 1fr 120px;
    border: 2px solid #0d6efd;
    margin-bottom: 20px;
  }

  .pdf-header-logo {
    border-right: 2px solid #0d6efd;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
  }

  .pdf-header-logo img {
    max-width: 100%;
    max-height: 60px;
  }

  .pdf-header-center {
    border-right: 2px solid #0d6efd;
    padding: 15px;
    text-align: center;
  }

  .pdf-header-title {
    background: #e3f2fd;
    padding: 4px;
    font-weight: bold;
    font-size: 11px;
    margin-bottom: 8px;
    color: #0d6efd;
  }

  .pdf-header-subtitle {
    font-weight: bold;
    font-size: 11px;
    line-height: 1.4;
    margin-bottom: 8px;
  }

  .pdf-header-contact {
    font-size: 9px;
    line-height: 1.3;
    color: #666;
  }

  .pdf-header-number {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 10px;
  }

  .pdf-header-number-title {
    background: #e3f2fd;
    padding: 4px 8px;
    font-size: 10px;
    font-weight: bold;
    color: #0d6efd;
    margin-bottom: 10px;
  }

  .pdf-header-number-value {
    font-size: 14px;
    font-weight: bold;
    color: #0d6efd;
  }

  /* Secciones */
  .section-header {
    background: #0d6efd;
    color: white;
    padding: 8px 15px;
    font-weight: bold;
    font-size: 13px;
    margin: 20px 0 10px 0;
    border-radius: 4px;
  }

  .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
    margin-bottom: 15px;
  }

  .info-item {
    display: flex;
    border: 1px solid #dee2e6;
    background: #f8f9fa;
  }

  .info-label {
    background: #e3f2fd;
    color: #0d6efd;
    font-weight: bold;
    font-size: 11px;
    padding: 8px 10px;
    min-width: 100px;
    border-right: 1px solid #dee2e6;
  }

  .info-value {
    padding: 8px 10px;
    font-size: 12px;
    flex: 1;
    background: white;
  }

  .info-value input {
    border: none;
    background: transparent;
    width: 100%;
    padding: 0;
    font-size: 12px;
  }

  .info-value input:focus {
    outline: none;
  }

  /* Tablas */
  .table-wrapper {
    overflow-x: auto;
    margin-bottom: 20px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
  }

  .table {
    margin: 0;
    font-size: 11px;
  }

  .table thead {
    background: #0d6efd;
    color: white;
  }

  .table thead th {
    border: 1px solid #0a58ca;
    padding: 8px 4px;
    font-weight: 600;
    text-align: center;
    font-size: 10px;
  }

  .table tbody td {
    border: 1px solid #dee2e6;
    padding: 6px 4px;
    vertical-align: middle;
  }

  .table tbody tr:nth-child(even) {
    background-color: #f8f9fa;
  }

  .table .form-control, .table .form-select {
    font-size: 10px;
    padding: 4px 6px;
    min-height: 28px;
  }

  /* Firmas */
  .firma-section {
    margin-top: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 4px;
  }

  .firma-box { 
    border: 2px dashed #cbd5e0;
    border-radius: 4px;
    height: 120px;
    background: white;
    cursor: crosshair;
    margin-bottom: 8px;
  }

  canvas { 
    width: 100%; 
    height: 120px;
  }

  /* Observaciones */
  .observacion-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
  }

  .observacion-card h6 {
    color: #0d6efd;
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e3f2fd;
  }

  /* Botones */
  .btn-icon {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
  }

  .btn-submit {
    padding: 12px 30px;
    font-size: 14px;
    font-weight: 600;
  }

  /* Preview de imágenes */
  .image-preview-container {
    position: relative;
    display: inline-block;
    margin: 5px;
  }
  
  .image-preview-container img {
    max-width: 100px;
    max-height: 100px;
    object-fit: cover;
    border: 2px solid #dee2e6;
    border-radius: 4px;
    cursor: pointer;
  }
  
  .image-preview-container img:hover {
    border-color: #0d6efd;
  }
  
  .btn-delete-image {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #dc3545;
    color: white;
    border: 2px solid white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  }
  
  .btn-delete-image:hover {
    background: #bb2d3b;
  }
  
  .loading-spinner {
    display: inline-block;
    width: 18px;
    height: 18px;
    border: 3px solid rgba(13, 110, 253, .3);
    border-radius: 50%;
    border-top-color: #0d6efd;
    animation: spin 1s ease-in-out infinite;
  }
  
  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  /* Responsive */
  @media (max-width: 768px) {
    .pdf-header {
      grid-template-columns: 1fr;
      grid-template-rows: auto auto auto;
    }

    .pdf-header-logo,
    .pdf-header-center,
    .pdf-header-number {
      border-right: none;
      border-bottom: 2px solid #0d6efd;
    }

    .pdf-header-number {
      border-bottom: none;
    }

    .info-grid {
      grid-template-columns: 1fr;
    }

    .table-wrapper {
      font-size: 9px;
    }

    .section-header {
      font-size: 12px;
      padding: 6px 12px;
    }

    .main-container {
      padding: 10px;
    }

    /* Mejoras para inputs en tablas en móviles */
    .table .form-control-sm {
      font-size: 14px !important;
      padding: 6px 4px !important;
      min-height: 32px !important;
    }

    .table input[type="text"] {
      font-size: 14px !important;
      min-width: 50px;
    }

    .table thead th {
      font-size: 9px;
      padding: 4px 2px;
    }

    .table tbody td {
      padding: 4px 2px;
    }

    /* Hacer la tabla de parámetros más grande en móvil */
    .parametros-table {
      min-width: 800px;
    }

    .parametros-table input {
      font-size: 14px !important;
      padding: 8px 4px !important;
      min-height: 36px !important;
      width: 100%;
    }
  }

  /* Para pantallas muy pequeñas */
  @media (max-width: 576px) {
    .table .form-control-sm,
    .table input[type="text"] {
      font-size: 16px !important; /* iOS no hace zoom con 16px+ */
      padding: 8px 6px !important;
      min-height: 38px !important;
    }

    .parametros-table input {
      font-size: 16px !important;
      padding: 10px 6px !important;
      min-height: 40px !important;
    }
  }

  /* Select2 adjustments */
  .select2-container--default .select2-selection--single {
    height: 32px;
    font-size: 11px;
  }

  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 30px;
    font-size: 11px;
  }

  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 30px;
  }

  /* Ajustes para inputs pequeños */
  .form-control-sm {
    font-size: 11px;
    padding: 4px 8px;
  }

  textarea.form-control {
    font-size: 12px;
  }
</style>
</head>
<body>
<div class="container">
  <div class="main-container">
    <!-- Botones superiores -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <a class="btn btn-secondary btn-sm btn-icon" href="/operador/mis_mantenimientos.php">
        ← Volver
      </a>
      <button class="btn btn-primary btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#modalAgregarEquipo">
        ➕ Agregar Equipo
      </button>
    </div>

    <!-- Cabecera estilo PDF -->
    <div class="pdf-header">
      <div class="pdf-header-logo">
        <img src="/../../lib/logo.jpeg" alt="Logo">
      </div>
      <div class="pdf-header-center">
        <div class="pdf-header-title">FORMATO DE CALIDAD</div>
        <div class="pdf-header-subtitle">
          CHECK LIST DE MANTENIMIENTO PREVENTIVO DE EQUIPOS – CORTINAS DE AIRE
        </div>
        <div class="pdf-header-contact">
          Oficina: (01) 6557907 | Emergencias: +51 943 048 606<br>
          ventas@refriservissac.com
        </div>
      </div>
      <div class="pdf-header-number">
        <div class="pdf-header-number-title">FORMATO</div>
        <div class="pdf-header-number-value">N°<?= str_pad($id, 6, "0", STR_PAD_LEFT) ?></div>
      </div>
    </div>

    <!-- Datos del Cliente -->
    <div class="section-header">DATOS DEL CLIENTE</div>
    <div class="info-grid">
      <div class="info-item">
        <div class="info-label">Cliente:</div>
        <div class="info-value"><?= htmlspecialchars($m['cliente'] ?? '-') ?></div>
      </div>
      <div class="info-item">
        <div class="info-label">Responsable:</div>
        <div class="info-value"><?= htmlspecialchars($m['responsable'] ?? '-') ?></div>
      </div>
      <div class="info-item">
        <div class="info-label">Dirección:</div>
        <div class="info-value"><?= htmlspecialchars($m['direccion'] ?? '-') ?></div>
      </div>
      <div class="info-item">
        <div class="info-label">Teléfono:</div>
        <div class="info-value"><?= htmlspecialchars($m['telefono'] ?? '-') ?></div>
      </div>
      <div class="info-item">
        <div class="info-label">Fecha:</div>
        <div class="info-value"><?= htmlspecialchars($m['fecha'] ?? date('Y-m-d')) ?></div>
      </div>
      <div class="info-item">
        <div class="info-label">MT - Digitador:</div>
        <div class="info-value">
          <input type="text" id="nombre_digitador" name="nombre_digitador" 
                 value="<?= htmlspecialchars($nombre_digitador ?? '') ?>" 
                 placeholder="Nombre y Apellido" form="formReporte">
        </div>
      </div>
    </div>

    <form action="cortinas.php" id="formReporte" method="post" enctype="multipart/form-data" class="mb-4">
      <input type="hidden" name="mantenimiento_id" value="<?= htmlspecialchars($m['id']) ?>">

      <!-- TABLA DE EQUIPOS -->
      <div class="section-header">DATOS DE IDENTIFICACIÓN DE LOS EQUIPOS</div>
      <div class="table-wrapper">
        <table class="table table-bordered mb-0">
          <thead>
            <tr>
              <th style="width:30px;">#</th>
              <th style="width:150px;">Identificador</th>
              <th style="width:150px;">Nombre</th>
              <th>Marca</th>
              <th>Modelo</th>
              <th>Ubicación</th>
              <th style="width:80px;">Voltaje</th>
            </tr>
          </thead>
          <tbody>
            <?php for($i=1; $i<=7; $i++): 
              $eq = $equiposMantenimiento[$i];
            ?>
            <tr>
              <td class="text-center"><?= $i ?></td>
              <td>
                <select class="form-select form-select-sm equipo-select" 
                        name="equipos[<?= $i ?>][id_equipo]" 
                        data-index="<?= $i ?>">
                  <option value="">-- Seleccione --</option>
                  <?php foreach($equiposList as $e): ?>
                      <option value="<?= $e['id_equipo'] ?>" <?= ($eq && $eq['id_equipo']==$e['id_equipo'] ? 'selected' : '') ?>>
                          <?= htmlspecialchars($e['Identificador']) ?>
                      </option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input type="text" class="form-control form-control-sm nombre-<?= $i ?>" name="equipos[<?= $i ?>][Nombre]" value="<?= htmlspecialchars($eq['Nombre'] ?? '') ?>" readonly></td>
              <td><input type="text" class="form-control form-control-sm marca-<?= $i ?>" name="equipos[<?= $i ?>][marca]" value="<?= htmlspecialchars($eq['Marca'] ?? '') ?>" readonly></td>
              <td><input type="text" class="form-control form-control-sm modelo-<?= $i ?>" name="equipos[<?= $i ?>][modelo]" value="<?= htmlspecialchars($eq['Modelo'] ?? '') ?>" readonly></td>
              <td><input type="text" class="form-control form-control-sm ubicacion-<?= $i ?>" name="equipos[<?= $i ?>][ubicacion]" value="<?= htmlspecialchars($eq['Ubicacion'] ?? '') ?>" readonly></td>
              <td><input type="text" class="form-control form-control-sm voltaje-<?= $i ?>" name="equipos[<?= $i ?>][voltaje]" value="<?= htmlspecialchars($eq['Voltaje'] ?? '') ?>" readonly></td>
            </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>

      <!-- TABLA DE PARÁMETROS -->
      <div class="section-header">PARÁMETROS DE FUNCIONAMIENTO (Antes / Después)</div>
      <div class="table-wrapper">
        <table class="table table-bordered mb-0 parametros-table">
          <thead>
            <tr>
              <th style="min-width:180px;">Medida</th>
              <?php for($i=1; $i<=7; $i++): ?>
                <th colspan="2" class="text-center">Eq. <?= $i ?></th>
              <?php endfor; ?>
            </tr>
            <tr>
              <th></th>
              <?php for($i=1; $i<=7; $i++): ?>
                <th style="width:50px;">A</th><th style="width:50px;">D</th>
              <?php endfor; ?>
            </tr>
          </thead>
          <tbody>
            <?php
            $parametros = [
              'Corriente eléctrica nominal (Amperios) L1',
              'Corriente L2',
              'Tensión eléctrica nominal V1','Tensión V2'
            ];
            foreach($parametros as $p): ?>
              <tr>
                <td><?= htmlspecialchars($p) ?></td>
                <?php for($i=1; $i<=7; $i++): ?>
                  <td>
                    <input type="text" 
                           class="form-control form-control-sm" 
                           name="parametros[<?= md5($p) ?>][<?= $i ?>][antes]"
                           inputmode="decimal">
                  </td>
                  <td>
                    <input type="text" 
                           class="form-control form-control-sm" 
                           name="parametros[<?= md5($p) ?>][<?= $i ?>][despues]"
                           inputmode="decimal">
                  </td>
                <?php endfor; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- ACTIVIDADES A REALIZAR -->
      <div class="section-header">ACTIVIDADES A REALIZAR</div>
      <div class="table-wrapper">
        <table class="table table-bordered mb-0">
          <thead>
            <tr>
              <th rowspan="2" style="min-width:300px;">ACTIVIDADES</th>
              <th colspan="7">Equipos</th>
              <th colspan="4">Frecuencia</th>
            </tr>
            <tr>
              <?php for($i=1; $i<=7; $i++): ?>
                <th style="width:30px;"><?= str_pad($i, 2, "0", STR_PAD_LEFT) ?></th>
              <?php endfor; ?>
              <th style="width:30px;">B</th>
              <th style="width:30px;">T</th>
              <th style="width:30px;">S</th>
              <th style="width:30px;">A</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $actividades = [
              "Desmontaje de equipo",
              "Limpieza de carcasa de cortina con paño húmedo",
              "Limpieza de motor con aspiradora",
              "Lavado de turbina",
              "Lubricación de componentes mecánicos",
              "Limpieza de parrillas laterales",
              "Verificación y ajuste de componentes electromecánicos",
              "Limpieza de componentes electrónicos",
              "Pruebas de funcionamiento y toma de parámetros"
            ];

            foreach($actividades as $index => $act):
            ?>
            <tr>
              <td style="font-size:10px;"><?= htmlspecialchars($act) ?></td>
              <?php for($i=1; $i<=7; $i++): ?>
                <td class="text-center">
                  <input type="checkbox"
                  name="actividades[<?= $index ?>][dias][<?= $i ?>]" 
                  value="1"
                  <?= (isset($actividadesGuardadas[$index]['dias']) && in_array($i, $actividadesGuardadas[$index]['dias'])) ? 'checked' : '' ?>>
                </td>
              <?php endfor; ?>
              <?php foreach(["B","T","S","A"] as $f): ?>
                <td class="text-center">
                  <input type="radio" 
                  name="actividades[<?= $index ?>][frecuencia]" 
                  value="<?= $f ?>"
                  <?= (isset($actividadesGuardadas[$index]['frecuencia']) && $actividadesGuardadas[$index]['frecuencia'] == $f) ? 'checked' : '' ?>>
                </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- TRABAJOS REALIZADOS -->
      <div class="section-header">TRABAJOS REALIZADOS</div>
      <div class="mb-3">
        <textarea class="form-control" name="trabajos" rows="4" 
                  placeholder="Describa los trabajos realizados durante el mantenimiento..."></textarea>
      </div>

      <!-- OBSERVACIONES MULTIMEDIA -->
      <div class="section-header">OBSERVACIONES Y RECOMENDACIONES</div>
      <div id="observacionesMultimedia" class="mb-3"></div>
      <textarea name="observaciones" id="observacionesFinal" hidden></textarea>

      <!-- FIRMAS -->
      <div class="firma-section">
        <div class="section-header">FIRMAS Y CONFORMIDAD</div>
        <div class="row g-3">
          <div class="col-12 col-md-4">
            <label class="form-label fw-bold small">Firma Cliente</label>
            <div class="firma-box"><canvas id="firmaClienteCanvas"></canvas></div>
            <input type="text" id="nombreCliente" name="nombre_cliente" 
                   class="form-control form-control-sm" placeholder="Nombre del cliente">
            <button type="button" class="btn btn-sm btn-secondary mt-2 w-100" 
                    onclick="sigCliente.clear()">Limpiar</button>
            <input type="hidden" name="firma_cliente" id="firma_cliente_input">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-bold small">Firma Supervisor</label>
            <div class="firma-box"><canvas id="firmaSupervisorCanvas"></canvas></div>
            <input type="text" id="nombreSupervisor" name="nombre_supervisor" 
                   class="form-control form-control-sm" placeholder="Nombre del supervisor">
            <button type="button" class="btn btn-sm btn-secondary mt-2 w-100" 
                    onclick="sigSupervisor.clear()">Limpiar</button>
            <input type="hidden" name="firma_supervisor" id="firma_supervisor_input">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-bold small">Firma Técnico</label>
            <div class="firma-box"><canvas id="firmaTecnicoCanvas"></canvas></div>
            <input type="text" class="form-control form-control-sm" id="nombre_tecnico" name="nombre_tecnico" 
                   value="<?= htmlspecialchars($nombre_tecnico ?? '') ?>" readonly>
            <button type="button" class="btn btn-sm btn-secondary mt-2 w-100" 
                    onclick="sigTecnico.clear()">Limpiar</button>
            <input type="hidden" name="firma_tecnico" id="firma_tecnico_input">
          </div>
        </div>
      </div>

      <div class="text-center mt-4">
        <button type="submit" class="btn btn-success btn-submit">
          Guardar y Generar Reporte PDF
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Firmas
const sigCliente = new SignaturePad(document.getElementById('firmaClienteCanvas'));
const sigSupervisor = new SignaturePad(document.getElementById('firmaSupervisorCanvas'));
const sigTecnico = new SignaturePad(document.getElementById('firmaTecnicoCanvas'));

document.getElementById('formReporte').addEventListener('submit', function(e){
  if (!sigCliente.isEmpty()) {
    document.getElementById('firma_cliente_input').value = sigCliente.toDataURL();
  }
  if (!sigSupervisor.isEmpty()) {
    document.getElementById('firma_supervisor_input').value = sigSupervisor.toDataURL();
  }
  if (!sigTecnico.isEmpty()) {
    document.getElementById('firma_tecnico_input').value = sigTecnico.toDataURL();
  }

  if(!confirm("¿Estás seguro de guardar el reporte?")){
    e.preventDefault();
  } else {
    let input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'confirmado';
    input.value = 'si';
    this.appendChild(input);
  }
});

$(document).ready(function(){
  $('.equipo-select').select2({ placeholder:"Buscar equipo...", allowClear:true, width:'100%' });

  $('.equipo-select').each(function(){
    let id = $(this).val();
    let index = $(this).data('index');
    if(id){
      $.getJSON('/operador/ajax_get_equipo.php', { id_equipo: id }, function(data){
        if(data.success){
          $(`.nombre-${index}`).val(data.nombre || '');
          $(`.marca-${index}`).val(data.marca || '');
          $(`.modelo-${index}`).val(data.modelo || '');
          $(`.ubicacion-${index}`).val(data.ubicacion || '');
          $(`.voltaje-${index}`).val(data.voltaje || '');
          generarObservacionesMultimedia();
        }
      });
    }
  });

  $('.equipo-select').on('change', function(){
    let id = $(this).val();
    let index = $(this).data('index');
    if(!id) {
      $(`.nombre-${index}, .marca-${index}, .modelo-${index}, .ubicacion-${index}, .voltaje-${index}`).val('');
      generarObservacionesMultimedia();
      return;
    }
    $.getJSON('/operador/ajax_get_equipo.php', { id_equipo: id }, function(data){
      if(data.success){
        $(`.nombre-${index}`).val(data.nombre || '');
        $(`.marca-${index}`).val(data.marca || '');
        $(`.modelo-${index}`).val(data.modelo || '');
        $(`.ubicacion-${index}`).val(data.ubicacion || '');
        $(`.voltaje-${index}`).val(data.voltaje || '');
        generarObservacionesMultimedia();
      }
    });
  });
});

$('#formAgregarEquipo').on('submit', function(e) {
  e.preventDefault();
  $.ajax({
    url: '/../../admin/equipos_add_crud.php',
    type: 'POST',
    data: $(this).serialize(),
    success: function(resp) {
      try {
        const r = JSON.parse(resp);
        if (r.success) {
          alert('✅ Equipo agregado correctamente');
          $('#modalAgregarEquipo').modal('hide');
          $('#formAgregarEquipo')[0].reset();
        } else {
          alert('⚠️ Error: ' + (r.message || 'No se pudo agregar.'));
        }
      } catch {
        console.log(resp);
        alert('✅ Equipo agregado correctamente');
        location.reload();
      }
    }
  });
});

function generarObservacionesMultimedia() {
  const contenedor = document.getElementById('observacionesMultimedia');
  contenedor.innerHTML = '';

  $('.equipo-select').each(function() {
    const index = $(this).data('index');
    const id = $(this).val();
    const texto = $(this).find('option:selected').text().trim();
    const nombre = $(`.nombre-${index}`).val() || '';

    if (id && texto && texto !== '-- Seleccione --') {
      const bloque = document.createElement('div');
      bloque.className = 'observacion-card';
      bloque.innerHTML = `
        <h6>🔧 ${texto}${nombre ? ' - ' + nombre : ''}</h6>
        <div class="mb-2">
          <label class="form-label small fw-bold">Texto / Recomendación:</label>
          <textarea class="form-control form-control-sm observacion-texto" data-index="${index}" rows="3"
            placeholder="Escribe observaciones específicas..." required></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-bold">Imágenes:</label>
          <div class="d-flex gap-2 mb-2 flex-wrap">
            <button type="button" class="btn btn-sm btn-primary btn-select-image" data-index="${index}">
              📁 Galería
            </button>
            <button type="button" class="btn btn-sm btn-success btn-camera-image" data-index="${index}">
              📷 Cámara
            </button>
          </div>
          <input 
            type="file" 
            class="d-none observacion-imagen" 
            id="input-imagen-${index}"
            data-index="${index}" 
            accept="image/*" 
            multiple>
          <input 
            type="file" 
            class="d-none observacion-camera" 
            id="input-camera-${index}"
            data-index="${index}" 
            accept="image/*" 
            capture="environment">
          <div id="preview-${index}" class="d-flex flex-wrap gap-2 mt-2" data-rutas="[]"></div>
        </div>
      `;
      contenedor.appendChild(bloque);
    }
  });
}

$(document).on('click', '.btn-select-image', function() {
  const index = $(this).data('index');
  $(`#input-imagen-${index}`).click();
});

$(document).on('click', '.btn-camera-image', function() {
  const index = $(this).data('index');
  $(`#input-camera-${index}`).click();
});

const imagenesGuardadas = {};

function eliminarImagen(index, rutaImagen) {
  if (!confirm('¿Deseas eliminar esta imagen?')) return;

  const rutasActuales = imagenesGuardadas[index] || [];
  const nuevasRutas = rutasActuales.filter(r => r !== rutaImagen);
  imagenesGuardadas[index] = nuevasRutas;

  const preview = document.getElementById(`preview-${index}`);
  preview.dataset.rutas = JSON.stringify(nuevasRutas);

  fetch('eliminar_imagen.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ruta: rutaImagen })
  }).catch(err => console.error('Error:', err));

  actualizarPreview(index);
}

function actualizarPreview(index) {
  const preview = document.getElementById(`preview-${index}`);
  const rutas = imagenesGuardadas[index] || [];
  
  preview.innerHTML = '';
  
  rutas.forEach(ruta => {
    const container = document.createElement('div');
    container.className = 'image-preview-container';
    
    const img = document.createElement('img');
    img.src = ruta;
    img.onclick = () => window.open(ruta, '_blank');
    
    const btnDelete = document.createElement('button');
    btnDelete.className = 'btn-delete-image';
    btnDelete.innerHTML = '×';
    btnDelete.type = 'button';
    btnDelete.onclick = (e) => {
      e.stopPropagation();
      eliminarImagen(index, ruta);
    };
    
    container.appendChild(img);
    container.appendChild(btnDelete);
    preview.appendChild(container);
  });
}

$(document).on('change', '.observacion-imagen, .observacion-camera', function() {
  const index = $(this).data('index');
  const files = this.files;

  if (files.length === 0) return;

  if (!imagenesGuardadas[index]) imagenesGuardadas[index] = [];

  const formData = new FormData();
  for (const f of files) formData.append('imagenes[]', f);

  const preview = document.getElementById(`preview-${index}`);
  const loadingDiv = document.createElement('div');
  loadingDiv.className = 'text-center p-2';
  loadingDiv.innerHTML = '<div class="loading-spinner"></div> Subiendo...';
  preview.appendChild(loadingDiv);

  fetch('subir_imagen.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(rutas => {
      loadingDiv.remove();

      if (!Array.isArray(rutas) || rutas.length === 0) {
        alert('⚠️ No se pudieron subir las imágenes');
        return;
      }

      imagenesGuardadas[index].push(...rutas);
      preview.dataset.rutas = JSON.stringify(imagenesGuardadas[index]);

      actualizarPreview(index);
    })
    .catch(err => {
      loadingDiv.remove();
      console.error('Error:', err);
      alert('❌ Error al subir las imágenes');
    })
    .finally(() => {
      this.value = '';
    });
});

$(document).ready(generarObservacionesMultimedia);

document.getElementById('formReporte').addEventListener('submit', function(e) {
  const data = [];

  document.querySelectorAll('.observacion-texto').forEach(txt => {
    const index = txt.dataset.index;
    const nombre = $(`.equipo-select[data-index='${index}'] option:selected`).text().trim();
    const rutas = imagenesGuardadas[index] || [];

    if (nombre && txt.value.trim()) {
      data.push({
        equipo: nombre,
        texto: txt.value.trim(),
        imagenes: rutas
      });
    }
  });

  document.getElementById('observacionesFinal').value = JSON.stringify(data, null, 2);
});
</script>
</body>
</html>