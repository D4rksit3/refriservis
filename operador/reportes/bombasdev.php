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
        header("Location: guardar_reporte_bombas.php?id=$mantenimiento_id");
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
$equiposList = $pdo->query("SELECT id_equipo AS id_equipo, Nombre ,Identificador, Marca, Modelo, Ubicacion, Voltaje 
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
  :root {
    --primary-color: #0d6efd;
    --secondary-color: #6c757d;
    --success-color: #198754;
    --danger-color: #dc3545;
  }

  body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px 0;
  }

  .main-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    padding: 30px;
    margin-bottom: 30px;
  }

  .header-card {
    background: linear-gradient(135deg, var(--primary-color) 0%, #0a58ca 100%);
    color: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
  }

  .info-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    border-left: 4px solid var(--primary-color);
  }

  .info-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 10px;
  }

  .info-item {
    display: flex;
    flex-direction: column;
  }

  .info-label {
    font-weight: 600;
    color: var(--primary-color);
    font-size: 0.85rem;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .info-value {
    font-size: 1rem;
    color: #2d3748;
    padding: 8px 12px;
    background: white;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
  }

  .section-title {
    background: linear-gradient(135deg, var(--primary-color) 0%, #0a58ca 100%);
    color: white;
    padding: 12px 20px;
    border-radius: 10px;
    margin: 25px 0 15px 0;
    font-weight: 600;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .table-responsive {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }

  .table {
    margin-bottom: 0;
  }

  .table thead {
    background: var(--primary-color);
    color: white;
  }

  .table thead th {
    border: none;
    padding: 12px;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
  }

  .table tbody tr:hover {
    background-color: #f8f9fa;
  }

  .form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    padding: 10px 15px;
    transition: all 0.3s ease;
  }

  .form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
  }

  .firma-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-top: 30px;
  }

  .firma-box { 
    border: 2px dashed #cbd5e0;
    border-radius: 10px;
    height: 150px;
    background: white;
    cursor: crosshair;
    transition: all 0.3s ease;
  }

  .firma-box:hover {
    border-color: var(--primary-color);
    background: #f8f9fa;
  }

  canvas { 
    width: 100%; 
    height: 150px;
    border-radius: 8px;
  }

  .btn-submit {
    background: linear-gradient(135deg, var(--success-color) 0%, #146c43 100%);
    border: none;
    padding: 15px 40px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 50px;
    box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
    transition: all 0.3s ease;
  }

  .btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(25, 135, 84, 0.4);
  }

  .image-preview-container {
    position: relative;
    display: inline-block;
    margin: 5px;
  }
  
  .image-preview-container img {
    max-width: 120px;
    max-height: 120px;
    object-fit: cover;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
  }
  
  .image-preview-container img:hover {
    border-color: var(--primary-color);
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  }
  
  .btn-delete-image {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--danger-color);
    color: white;
    border: 2px solid white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
  }
  
  .btn-delete-image:hover {
    background: #bb2d3b;
    transform: scale(1.1);
  }
  
  .loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
  }
  
  @keyframes spin {
    to { transform: rotate(360deg); }
  }

  .observacion-card {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
  }

  .observacion-card:hover {
    border-color: var(--primary-color);
    box-shadow: 0 5px 15px rgba(13, 110, 253, 0.1);
  }

  .btn-icon {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
  }

  .btn-icon:hover {
    transform: translateY(-2px);
  }

  @media (max-width: 768px) {
    .main-container {
      padding: 15px;
      border-radius: 10px;
    }

    .header-card {
      padding: 15px;
    }

    .info-row {
      grid-template-columns: 1fr;
    }

    .section-title {
      font-size: 1rem;
      padding: 10px 15px;
    }

    .btn-submit {
      width: 100%;
      padding: 12px 30px;
    }

    .firma-box, canvas {
      height: 120px;
    }
  }
</style>
</head>
<body>
<div class="container">
  <div class="main-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <a class="btn btn-secondary btn-icon" href="/operador/mis_mantenimientos.php">
        ← Volver
      </a>
      <button class="btn btn-primary btn-icon" data-bs-toggle="modal" data-bs-target="#modalAgregarEquipo">
        ➕ Agregar Equipo
      </button>
    </div>

    <!-- Logo Header -->
    <div class="header-card text-center">
      <img src="/../../lib/logo.jpeg" alt="Logo" style="max-height:80px; margin-bottom: 15px;">
      <h5 class="mb-2">FORMATO DE CALIDAD</h5>
      <h6 class="mb-3">CHECK LIST DE MANTENIMIENTO PREVENTIVO DE EQUIPOS – BOMBA DE AGUA</h6>
      <div style="font-size: 0.9rem;">
        📞 Oficina: (01) 6557907 | 📱 Emergencias: +51 943 048 606<br>
        📧 ventas@refriservissac.com
      </div>
      <div class="mt-3">
        <span class="badge bg-light text-dark fs-6">
          N°<?= str_pad($id, 6, "0", STR_PAD_LEFT) ?>
        </span>
      </div>
    </div>

    <!-- Datos del Cliente -->
    <div class="info-card">
      <h6 class="text-primary mb-3">📋 DATOS DEL CLIENTE</h6>
      <div class="info-row">
        <div class="info-item">
          <span class="info-label">Cliente</span>
          <span class="info-value"><?= htmlspecialchars($m['cliente'] ?? '-') ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Responsable</span>
          <span class="info-value"><?= htmlspecialchars($m['responsable'] ?? '-') ?></span>
        </div>
      </div>
      <div class="info-row">
        <div class="info-item">
          <span class="info-label">Dirección</span>
          <span class="info-value"><?= htmlspecialchars($m['direccion'] ?? '-') ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">Teléfono</span>
          <span class="info-value"><?= htmlspecialchars($m['telefono'] ?? '-') ?></span>
        </div>
      </div>
      <div class="info-row">
        <div class="info-item">
          <span class="info-label">Fecha</span>
          <span class="info-value"><?= htmlspecialchars($m['fecha'] ?? date('Y-m-d')) ?></span>
        </div>
        <div class="info-item">
          <span class="info-label">MT - Digitador (Nombre y Apellido)</span>
          <input type="text" class="form-control" id="nombre_digitador" name="nombre_digitador" 
                 value="<?= htmlspecialchars($nombre_digitador ?? '') ?>" 
                 placeholder="Ingrese nombre del digitador" form="formReporte">
        </div>
      </div>
    </div>

    <form action="bombas.php" id="formReporte" method="post" enctype="multipart/form-data" class="mb-5">
      <input type="hidden" name="mantenimiento_id" value="<?= htmlspecialchars($m['id']) ?>">

      <!-- TABLA DE EQUIPOS -->
      <h6 class="section-title">🔧 DATOS DE IDENTIFICACIÓN DE LOS EQUIPOS</h6>
      <div class="table-responsive mb-3">
        <table class="table table-bordered align-middle text-center">
          <thead>
            <tr>
              <th>#</th>
              <th>Identificador</th>
              <th>Nombre</th>
              <th>Marca</th>
              <th>Modelo</th>
              <th>Ubicación</th>
              <th>Voltaje</th>
            </tr>
          </thead>
          <tbody>
            <?php for($i=1; $i<=7; $i++): 
              $eq = $equiposMantenimiento[$i];
            ?>
            <tr>
              <td><?= $i ?></td>
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
      <h6 class="section-title">📊 PARÁMETROS DE FUNCIONAMIENTO</h6>
      <div class="table-responsive mb-3">
        <table class="table table-bordered table-sm">
          <thead>
            <tr>
              <th>Medida</th>
              <?php for($i=1; $i<=7; $i++): ?>
                <th colspan="2" class="text-center">Equipo <?= $i ?></th>
              <?php endfor; ?>
            </tr>
            <tr>
              <th></th>
              <?php for($i=1; $i<=7; $i++): ?>
                <th>Antes</th><th>Desp.</th>
              <?php endfor; ?>
            </tr>
          </thead>
          <tbody>
            <?php
            $parametros = [
              'Corriente eléctrica nominal (Amperios) L1',
              'Corriente L2','Corriente L3',
              'Tensión eléctrica nominal V1','Tensión V2','Tensión V3',
              'Presión de descarga (PSI)','Presión de succión (PSI)'
            ];
            foreach($parametros as $p): ?>
              <tr>
                <td style="min-width:200px;"><?= htmlspecialchars($p) ?></td>
                <?php for($i=1; $i<=7; $i++): ?>
                  <td><input type="text" class="form-control form-control-sm" name="parametros[<?= md5($p) ?>][<?= $i ?>][antes]"></td>
                  <td><input type="text" class="form-control form-control-sm" name="parametros[<?= md5($p) ?>][<?= $i ?>][despues]"></td>
                <?php endfor; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- ACTIVIDADES A REALIZAR -->
      <h6 class="section-title">✅ ACTIVIDADES A REALIZAR</h6>
      <div class="table-responsive mb-3">
        <table class="table table-bordered table-sm text-center align-middle">
          <thead class="table-primary">
            <tr>
              <th rowspan="2" class="align-middle">ACTIVIDADES A REALIZAR</th>
              <th colspan="7">Equipos</th>
              <th colspan="4">Frecuencia</th>
            </tr>
            <tr>
              <?php for($i=1; $i<=7; $i++): ?>
                <th><?= str_pad($i, 2, "0", STR_PAD_LEFT) ?></th>
              <?php endfor; ?>
              <th>B.</th>
              <th>T.</th>
              <th>S.</th>
              <th>A.</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $actividades = [
              "Inspección ocular del equipo en funcionamiento",
              "Verificación del estado de superficies y aseo general del equipo",
              "Medición y registro de parámetros de operación (amperaje, voltaje, potencia)",
              "Inspección de estado del sello mecánico",
              "Inspección de manómetros y termómetros",
              "Inspección de rodamientos de motor y bomba centrifuga",
              "Inspección del acoplamiento y ajuste de prisioneros",
              "Medición y registro de consumos eléctricos",
              "Ajuste de conexiones eléctricas del motor",
              "Revisión de variador de velocidad",
              "Lubricación de rodamientos de acuerdo a recomendaciones del fabricante",
              "Revisión de los pernos de la base y motor (requiere uso de torquímetro)",
              "Pintado externo del motor y bomba manteniendo color original (dieléctrica)",
              "Prueba de funcionamiento y verificación de condiciones operativas",
              "Lubricación y engrase de la bomba.",
              "Revisión y Ajuste de la prensa estopa y/o sello mecánico",
              "Revisión y/o cambio de empaquetaduras de O-rings",
              "Revisión y cambio de borneras eléctricas",
              "Cambio de empaquetaduras, sellos y rodamientos en caso se requiera",
              "Pintado de las válvulas y de las tuberías de distribución si lo requiere",
              "Megar y registrar el estado del aislamiento del motor eléctrico"
            ];

            foreach($actividades as $index => $act):
            ?>
            <tr>
              <td class="text-start"><?= htmlspecialchars($act) ?></td>
              <?php for($i=1; $i<=7; $i++): ?>
                <td>
                  <input type="checkbox"
                  name="actividades[<?= $index ?>][dias][<?= $i ?>]" 
                  value="1"
                  <?= (isset($actividadesGuardadas[$index]['dias']) && in_array($i, $actividadesGuardadas[$index]['dias'])) ? 'checked' : '' ?>>
                </td>
              <?php endfor; ?>
              <?php foreach(["B","T","S","A"] as $f): ?>
                <td>
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
      <h6 class="section-title">📝 TRABAJOS REALIZADOS</h6>
      <div class="mb-4">
        <textarea class="form-control" name="trabajos" rows="5" 
                  placeholder="Describa los trabajos realizados durante el mantenimiento..."></textarea>
      </div>

      <!-- OBSERVACIONES MULTIMEDIA -->
      <h6 class="section-title">📸 OBSERVACIONES Y RECOMENDACIONES</h6>
      <div id="observacionesMultimedia" class="mb-4"></div>
      <textarea name="observaciones" id="observacionesFinal" hidden></textarea>

      <!-- FIRMAS -->
      <div class="firma-section">
        <h6 class="section-title">✍️ FIRMAS Y CONFORMIDAD</h6>
        <div class="row g-3">
          <div class="col-12 col-md-4">
            <label class="form-label fw-bold">Firma Cliente</label>
            <div class="firma-box"><canvas id="firmaClienteCanvas"></canvas></div>
            <input type="text" id="nombreCliente" name="nombre_cliente" 
                   class="form-control mt-2" placeholder="Nombre del cliente">
            <button type="button" class="btn btn-sm btn-secondary mt-2 w-100" 
                    onclick="sigCliente.clear()">🗑️ Limpiar</button>
            <input type="hidden" name="firma_cliente" id="firma_cliente_input">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-bold">Firma Supervisor</label>
            <div class="firma-box"><canvas id="firmaSupervisorCanvas"></canvas></div>
            <input type="text" id="nombreSupervisor" name="nombre_supervisor" 
                   class="form-control mt-2" placeholder="Nombre del supervisor">
            <button type="button" class="btn btn-sm btn-secondary mt-2 w-100" 
                    onclick="sigSupervisor.clear()">🗑️ Limpiar</button>
            <input type="hidden" name="firma_supervisor" id="firma_supervisor_input">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-bold">Firma Técnico</label>
            <div class="firma-box"><canvas id="firmaTecnicoCanvas"></canvas></div>
            <input type="text" class="form-control mt-2" id="nombre_tecnico" name="nombre_tecnico" 
                   value="<?= htmlspecialchars($nombre_tecnico ?? '') ?>" readonly>
            <button type="button" class="btn btn-sm btn-secondary mt-2 w-100" 
                    onclick="sigTecnico.clear()">🗑️ Limpiar</button>
            <input type="hidden" name="firma_tecnico" id="firma_tecnico_input">
          </div>
        </div>
      </div>

      <div class="text-center mt-5">
        <button type="submit" class="btn btn-success btn-submit">
          📄 Guardar y Generar Reporte PDF
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
        <h6 class="text-primary mb-3">🔧 ${texto}${nombre ? ' - ' + nombre : ''}</h6>
        <div class="mb-3">
          <label class="form-label fw-bold">Texto / Recomendación:</label>
          <textarea class="form-control observacion-texto" data-index="${index}" rows="3"
            placeholder="Escribe observaciones específicas para ${texto}..." required></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label fw-bold">Imágenes:</label>
          <div class="d-flex gap-2 mb-3 flex-wrap">
            <button type="button" class="btn btn-primary btn-icon btn-select-image" data-index="${index}">
              📁 Seleccionar de Galería
            </button>
            <button type="button" class="btn btn-success btn-icon btn-camera-image" data-index="${index}">
              📷 Tomar Foto
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
          <div id="preview-${index}" class="d-flex flex-wrap gap-2 mt-3" data-rutas="[]"></div>
        </div>
      `;
      contenedor.appendChild(bloque);
    }
  });
}

// === Manejadores de botones para galería y cámara ===
$(document).on('click', '.btn-select-image', function() {
  const index = $(this).data('index');
  $(`#input-imagen-${index}`).click();
});

$(document).on('click', '.btn-camera-image', function() {
  const index = $(this).data('index');
  $(`#input-camera-${index}`).click();
});

// === Almacenamiento de imágenes por equipo ===
const imagenesGuardadas = {};

// === Función para eliminar imagen ===
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
  }).catch(err => console.error('Error eliminando imagen del servidor:', err));

  actualizarPreview(index);
}

// === Función para actualizar el preview ===
function actualizarPreview(index) {
  const preview = document.getElementById(`preview-${index}`);
  const rutas = imagenesGuardadas[index] || [];
  
  preview.innerHTML = '';
  
  rutas.forEach(ruta => {
    const container = document.createElement('div');
    container.className = 'image-preview-container';
    
    const img = document.createElement('img');
    img.src = ruta;
    img.className = 'img-thumbnail';
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

// === Manejo de carga de imágenes ===
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
    .then(res => {
      if (!res.ok) throw new Error('Error HTTP ' + res.status);
      return res.json();
    })
    .then(rutas => {
      loadingDiv.remove();

      if (!Array.isArray(rutas) || rutas.length === 0) {
        alert('⚠️ No se pudieron subir las imágenes');
        return;
      }

      imagenesGuardadas[index].push(...rutas);
      preview.dataset.rutas = JSON.stringify(imagenesGuardadas[index]);

      actualizarPreview(index);

      const msg = document.createElement('div');
      msg.className = 'alert alert-success alert-dismissible fade show mt-2';
      msg.innerHTML = `
        ✅ ${rutas.length} imagen(es) cargada(s) correctamente
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;
      preview.parentElement.insertBefore(msg, preview);
      setTimeout(() => msg.remove(), 3000);
    })
    .catch(err => {
      loadingDiv.remove();
      console.error('Error subiendo imagen:', err);
      alert('❌ Error al subir las imágenes. Intenta nuevamente.');
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