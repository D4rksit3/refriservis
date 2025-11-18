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
      u.nombre AS nombre_tecnico
  FROM mantenimientos m
  LEFT JOIN clientes c ON c.id = m.cliente_id
  LEFT JOIN usuarios u ON u.id = m.operador_id
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
  :root {
    --primary-color: #0d6efd;
    --primary-dark: #0a58ca;
    --primary-light: #6ea8fe;
    --bg-light: #f8f9fa;
    --border-color: #dee2e6;
    --shadow-sm: 0 2px 4px rgba(13, 110, 253, 0.1);
    --shadow-md: 0 4px 12px rgba(13, 110, 253, 0.15);
    --shadow-lg: 0 8px 24px rgba(13, 110, 253, 0.2);
  }

  body {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
  }

  .container {
    max-width: 1400px;
  }

  /* Header mejorado */
  .page-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 2rem 0;
    margin: -1rem -15px 2rem;
    box-shadow: var(--shadow-lg);
    border-radius: 0 0 20px 20px;
  }

  .page-header h5 {
    font-weight: 600;
    margin: 0;
    font-size: 1.5rem;
  }

  .page-header .btn-back {
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: white;
    transition: all 0.3s ease;
  }

  .page-header .btn-back:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateX(-5px);
  }

  .page-header .btn-add-equipo {
    background: white;
    color: var(--primary-color);
    border: none;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  .page-header .btn-add-equipo:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.3);
  }

  /* Tabla del encabezado */
  .header-table {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow-md);
    margin-bottom: 1.5rem;
  }

  .header-table table {
    margin: 0;
  }

  .header-table td {
    padding: 1rem;
    vertical-align: middle;
  }

  .header-table .formato-badge {
    background: linear-gradient(135deg, #cfe2f3 0%, #b3d4ea 100%);
    padding: 0.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
  }

  /* Cards mejoradas */
  .info-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border: none;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
  }

  .info-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
  }

  .info-card .info-row {
    display: flex;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--bg-light);
  }

  .info-card .info-row:last-child {
    border-bottom: none;
  }

  .info-card .info-label {
    color: var(--primary-color);
    font-weight: 600;
    min-width: 120px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .info-card .info-value {
    color: #495057;
  }

  /* Sección de título */
  .section-title {
    background: linear-gradient(90deg, var(--primary-color) 0%, var(--primary-light) 100%);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin: 2rem 0 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    box-shadow: var(--shadow-sm);
  }

  /* Tablas mejoradas */
  .custom-table {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    margin-bottom: 2rem;
  }

  .custom-table table {
    margin: 0;
  }

  .custom-table thead {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: white;
  }

  .custom-table thead th {
    border: none;
    padding: 1rem;
    font-weight: 600;
    text-align: center;
  }

  .custom-table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
    border-color: var(--border-color);
  }

  .custom-table tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
  }

  /* Inputs mejorados */
  .form-control, .form-select {
    border: 2px solid var(--border-color);
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
    transition: all 0.3s ease;
  }

  .form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
  }

  /* Firma boxes */
  .firma-container {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
  }

  .firma-container:hover {
    box-shadow: var(--shadow-md);
  }

  .firma-label {
    color: var(--primary-color);
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .firma-box {
    border: 3px dashed var(--primary-light);
    border-radius: 12px;
    height: 150px;
    background: var(--bg-light);
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
  }

  .firma-box:hover {
    border-color: var(--primary-color);
    background: white;
  }

  canvas {
    width: 100%;
    height: 150px;
    cursor: crosshair;
  }

  /* Botones mejorados */
  .btn-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    border: none;
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: var(--shadow-sm);
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
  }

  .btn-secondary {
    background: #6c757d;
    border: none;
    border-radius: 8px;
    transition: all 0.3s ease;
  }

  .btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
  }

  .btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    border-radius: 12px;
    padding: 1rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    box-shadow: var(--shadow-md);
    transition: all 0.3s ease;
  }

  .btn-success:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
  }

  /* Observaciones multimedia */
  .observacion-card {
    background: white;
    border-radius: 12px;
    border: 2px solid var(--primary-light);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s ease;
  }

  .observacion-card:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--primary-color);
  }

  .observacion-card h6 {
    color: var(--primary-color);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }

  /* Preview de imágenes */
  .img-preview {
    max-width: 100%;
    max-height: 150px;
    object-fit: contain;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    padding: 0.25rem;
    background: white;
    transition: all 0.3s ease;
  }

  .img-preview:hover {
    border-color: var(--primary-color);
    transform: scale(1.05);
  }

  /* Checkboxes y radios personalizados */
  input[type="checkbox"], input[type="radio"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: var(--primary-color);
  }

  /* Responsive - Mobile First */
  @media (max-width: 768px) {
    body {
      font-size: 14px;
    }

    .container {
      padding: 0;
    }

    .page-header {
      padding: 1rem 0.5rem;
      margin: 0 0 1rem;
      border-radius: 0;
    }

    .page-header h5 {
      font-size: 1rem;
      line-height: 1.3;
    }

    .page-header .btn-back,
    .page-header .btn-add-equipo {
      font-size: 0.85rem;
      padding: 0.5rem 0.75rem;
    }

    .section-title {
      font-size: 0.9rem;
      padding: 0.75rem;
      margin: 1rem 0 0.75rem;
    }

    .section-title i {
      font-size: 0.85rem;
    }

    /* Tablas en móvil */
    .custom-table {
      border-radius: 10px;
      margin-bottom: 1rem;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    .custom-table table {
      font-size: 0.75rem;
      min-width: 800px;
    }

    .custom-table thead th {
      padding: 0.5rem 0.25rem;
      font-size: 0.7rem;
      white-space: nowrap;
    }

    .custom-table tbody td {
      padding: 0.4rem 0.2rem;
    }

    .custom-table input[type="text"] {
      font-size: 0.75rem;
      padding: 0.25rem;
      min-width: 60px;
    }

    .custom-table input[type="checkbox"],
    .custom-table input[type="radio"] {
      width: 16px;
      height: 16px;
    }

    /* Select más pequeño en móvil */
    .form-select-sm {
      font-size: 0.75rem;
      padding: 0.25rem 0.5rem;
    }

    /* Info card responsive */
    .info-card {
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: 10px;
    }

    .info-card .info-row {
      flex-direction: column;
      align-items: flex-start;
      padding: 0.5rem 0;
      gap: 0.25rem;
    }

    .info-card .info-label {
      min-width: auto;
      font-size: 0.85rem;
    }

    .info-card .info-value {
      font-size: 0.9rem;
      padding-left: 1.5rem;
    }

    /* Header table en móvil */
    .header-table {
      border-radius: 10px;
      margin-bottom: 1rem;
      overflow-x: auto;
    }

    .header-table table {
      font-size: 0.7rem;
      min-width: 600px;
    }

    .header-table td {
      padding: 0.5rem;
    }

    .header-table img {
      max-height: 40px !important;
    }

    .formato-badge {
      font-size: 0.7rem !important;
      padding: 0.25rem !important;
    }

    .report-number {
      font-size: 0.85rem;
      padding: 0.5rem;
    }

    /* Firmas en móvil */
    .firma-container {
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: 10px;
    }

    .firma-label {
      font-size: 0.9rem;
      margin-bottom: 0.75rem;
    }

    .firma-box {
      height: 120px;
      border-width: 2px;
    }

    canvas {
      height: 120px;
    }

    .firma-container input[type="text"] {
      font-size: 0.85rem;
      padding: 0.5rem;
    }

    .firma-container .btn {
      font-size: 0.85rem;
      padding: 0.5rem;
    }

    /* Observaciones en móvil */
    .observacion-card {
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: 10px;
    }

    .observacion-card h6 {
      font-size: 0.95rem;
      margin-bottom: 0.75rem;
    }

    .observacion-card textarea {
      font-size: 0.85rem;
    }

    .observacion-card .form-label {
      font-size: 0.85rem;
    }

    /* Textarea general */
    textarea.form-control {
      font-size: 0.85rem;
      min-height: 80px;
    }

    /* Botón principal */
    .btn-success {
      font-size: 0.95rem;
      padding: 0.875rem 1.25rem;
      width: 100%;
    }

    /* Preview de imágenes */
    .img-thumbnail {
      max-width: 80px !important;
      max-height: 80px !important;
    }

    /* Ajuste para scrollbar de tablas */
    .table-responsive {
      margin-bottom: 0.5rem;
    }

    .table-responsive::-webkit-scrollbar {
      height: 8px;
    }

    .table-responsive::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
      background: var(--primary-color);
      border-radius: 10px;
    }
  }

  @media (max-width: 576px) {
    .page-header h5 {
      font-size: 0.9rem;
    }

    .page-header .d-flex {
      gap: 0.5rem !important;
    }

    .btn-back {
      padding: 0.4rem 0.6rem !important;
    }

    .btn-back i {
      font-size: 0.9rem;
    }

    .btn-add-equipo {
      font-size: 0.75rem !important;
      padding: 0.4rem 0.6rem !important;
    }

    .section-title {
      font-size: 0.85rem;
      padding: 0.6rem;
    }

    .custom-table table {
      font-size: 0.7rem;
    }

    .info-card .info-label {
      font-size: 0.8rem;
    }

    .info-card .info-value {
      font-size: 0.85rem;
    }
  }

  /* Mejora de scroll horizontal para tablas */
  .table-responsive {
    position: relative;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  /* Indicador visual de scroll */
  @media (max-width: 768px) {
    .custom-table::after {
      content: '← Desliza para ver más →';
      display: block;
      text-align: center;
      padding: 0.5rem;
      font-size: 0.75rem;
      color: var(--primary-color);
      background: rgba(13, 110, 253, 0.05);
      border-radius: 0 0 10px 10px;
    }

    .custom-table.scrolled::after {
      content: '';
      display: none;
    }
  }

  /* Animaciones */
  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .info-card, .custom-table, .observacion-card, .firma-container {
    animation: fadeIn 0.5s ease-out;
  }

  /* Select2 personalizado */
  .select2-container--default .select2-selection--single {
    border: 2px solid var(--border-color);
    border-radius: 8px;
    height: auto;
    padding: 0.25rem;
  }

  .select2-container--default.select2-container--focus .select2-selection--single {
    border-color: var(--primary-color);
  }

  /* Textarea mejorado */
  textarea.form-control {
    resize: vertical;
    min-height: 100px;
  }

  /* Badge de número de reporte */
  .report-number {
    background: linear-gradient(135deg, #cfe2f3 0%, #b3d4ea 100%);
    padding: 0.75rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: 1rem;
    text-align: center;
  }
</style>
</head>
<body>

<div class="page-header">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="d-flex align-items-center gap-3">
        <a class="btn btn-back" href="/operador/mis_mantenimientos.php">
          <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Reporte de Mantenimiento #<?=htmlspecialchars($m['id'])?></h5>
      </div>
      <button class="btn btn-add-equipo" data-bs-toggle="modal" data-bs-target="#modalAgregarEquipo">
        <i class="bi bi-plus-circle"></i> Agregar Equipo
      </button>
    </div>
  </div>
</div>

<div class="container py-3">
  
  <!-- Tabla de encabezado -->
  <div class="header-table">
    <table class="table table-bordered mb-0">
      <tr>
        <td width="20%" class="text-center">
          <img src="/../../lib/logo.jpeg" alt="Logo" style="max-height:60px;">
        </td>
        <td width="60%" class="text-center">
          <div class="formato-badge mb-2">FORMATO DE CALIDAD</div>
          <strong style="font-size: 13px;">CHECK LIST DE MANTENIMIENTO PREVENTIVO DE EQUIPOS – BOMBA DE AGUA</strong><br>
          <small>
            Oficina: (01) 6557907 | Emergencias: +51 943 048 606<br>
            ventas@refriservissac.com
          </small>
        </td>
        <td width="20%" class="text-center">
          <div class="formato-badge mb-2">FORMATO DE CALIDAD</div>
          <div class="report-number">
            001-N°<?php echo str_pad($id, 6, "0", STR_PAD_LEFT); ?>
          </div>
        </td>
      </tr>
    </table>
  </div>

  <!-- Info del cliente -->
  <div class="info-card">
    <div class="info-row">
      <div class="info-label"><i class="bi bi-building"></i> CLIENTE:</div>
      <div class="info-value"><?=htmlspecialchars($m['cliente'] ?? '-')?></div>
    </div>
    <div class="info-row">
      <div class="info-label"><i class="bi bi-geo-alt"></i> DIRECCIÓN:</div>
      <div class="info-value"><?=htmlspecialchars($m['direccion'] ?? '-')?></div>
    </div>
    <div class="info-row">
      <div class="info-label"><i class="bi bi-person"></i> RESPONSABLE:</div>
      <div class="info-value"><?=htmlspecialchars($m['responsable'] ?? '-')?></div>
    </div>
    <div class="info-row">
      <div class="info-label"><i class="bi bi-calendar"></i> FECHA:</div>
      <div class="info-value"><?=htmlspecialchars($m['fecha'] ?? date('Y-m-d'))?></div>
    </div>
  </div>

  <form action="bombas.php" id="formReporte" method="post" enctype="multipart/form-data" class="mb-5">
    <input type="hidden" name="mantenimiento_id" value="<?=htmlspecialchars($m['id'])?>">

    <!-- EQUIPOS -->
    <div class="section-title">
      <i class="bi bi-gear-fill"></i>
      DATOS DE IDENTIFICACIÓN DE LOS EQUIPOS A INTERVENIR
    </div>

    <div class="custom-table">
      <div class="table-responsive">
        <table class="table table-bordered align-middle text-center mb-0">
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
            <?php for($i=1;$i<=7;$i++): 
              $eq = $equiposMantenimiento[$i];
            ?>
            <tr>
              <td><strong><?= $i ?></strong></td>
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
              <td><input type="text" class="form-control form-control-sm nombre-<?= $i ?>" name="equipos[<?= $i ?>][Nombre]" value="<?=htmlspecialchars($eq['Nombre'] ?? '')?>" readonly></td>
              <td><input type="text" class="form-control form-control-sm marca-<?= $i ?>" name="equipos[<?= $i ?>][marca]" value="<?=htmlspecialchars($eq['Marca'] ?? '')?>" readonly></td>
              <td><input type="text" class="form-control form-control-sm modelo-<?= $i ?>" name="equipos[<?= $i ?>][modelo]" value="<?=htmlspecialchars($eq['Modelo'] ?? '')?>" readonly></td>
              <td><input type="text" class="form-control form-control-sm ubicacion-<?= $i ?>" name="equipos[<?= $i ?>][ubicacion]" value="<?=htmlspecialchars($eq['Ubicacion'] ?? '')?>" readonly></td>
              <td><input type="text" class="form-control form-control-sm voltaje-<?= $i ?>" name="equipos[<?= $i ?>][voltaje]" value="<?=htmlspecialchars($eq['Voltaje'] ?? '')?>" readonly></td>
            </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- PARÁMETROS -->
    <div class="section-title">
      <i class="bi bi-speedometer2"></i>
      PARÁMETROS DE FUNCIONAMIENTO (Antes / Después)
    </div>

    <div class="custom-table">
      <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0">
          <thead>
            <tr>
              <th>Medida</th>
              <?php for($i=1;$i<=7;$i++): ?>
                <th colspan="2" class="text-center">Equipo <?= $i ?></th>
              <?php endfor; ?>
            </tr>
            <tr>
              <th></th>
              <?php for($i=1;$i<=7;$i++): ?>
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
                <td style="min-width:200px;"><strong><?=htmlspecialchars($p)?></strong></td>
                <?php for($i=1;$i<=7;$i++): ?>
                  <td><input type="text" class="form-control form-control-sm" name="parametros[<?= md5($p) ?>][<?= $i ?>][antes]"></td>
                  <td><input type="text" class="form-control form-control-sm" name="parametros[<?= md5($p) ?>][<?= $i ?>][despues]"></td>
                <?php endfor; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ACTIVIDADES -->
    <div class="section-title">
      <i class="bi bi-list-check"></i>
      ACTIVIDADES A REALIZAR
    </div>

    <div class="custom-table">
      <div class="table-responsive">
        <table class="table table-bordered table-sm text-center align-middle mb-0">
          <thead>
            <tr>
              <th rowspan="2" class="align-middle">ACTIVIDADES A REALIZAR</th>
              <th colspan="7">Equipos</th>
              <th colspan="4">Frecuencia</th>
            </tr>
            <tr>
              <?php for($i=1;$i<=7;$i++): ?>
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

              <?php for($i=1;$i<=7;$i++): ?>
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
    </div>

    <!-- TRABAJOS REALIZADOS -->
    <div class="section-title">
      <i class="bi bi-tools"></i>
      TRABAJOS REALIZADOS
    </div>

    <div class="mb-4">
      <textarea class="form-control" name="trabajos" rows="5" placeholder="Describa los trabajos realizados durante el mantenimiento..."></textarea>
    </div>

    <!-- OBSERVACIONES MULTIMEDIA -->
    <div class="section-title">
      <i class="bi bi-camera-fill"></i>
      OBSERVACIONES Y RECOMENDACIONES (Multimedia por equipo)
    </div>

    <div id="observacionesMultimedia"></div>
    <textarea name="observaciones" id="observacionesFinal" hidden></textarea>

    <!-- FIRMAS -->
    <div class="section-title">
      <i class="bi bi-pen-fill"></i>
      FIRMAS DE CONFORMIDAD
    </div>

    <div class="row g-3 mb-4">
      <div class="col-12 col-md-4">
        <div class="firma-container">
          <div class="firma-label">
            <i class="bi bi-person-check-fill"></i>
            Firma Cliente
          </div>
          <div class="firma-box">
            <canvas id="firmaClienteCanvas"></canvas>
          </div>
          <input type="text" id="nombreCliente" name="nombre_cliente" class="form-control mt-3" placeholder="Nombre del cliente">
          <button type="button" class="btn btn-secondary btn-sm mt-2 w-100" onclick="sigCliente.clear()">
            <i class="bi bi-eraser"></i> Limpiar
          </button>
          <input type="hidden" name="firma_cliente" id="firma_cliente_input">
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="firma-container">
          <div class="firma-label">
            <i class="bi bi-person-badge-fill"></i>
            Firma Supervisor
          </div>
          <div class="firma-box">
            <canvas id="firmaSupervisorCanvas"></canvas>
          </div>
          <input type="text" id="nombreSupervisor" name="nombre_supervisor" class="form-control mt-3" placeholder="Nombre del supervisor">
          <button type="button" class="btn btn-secondary btn-sm mt-2 w-100" onclick="sigSupervisor.clear()">
            <i class="bi bi-eraser"></i> Limpiar
          </button>
          <input type="hidden" name="firma_supervisor" id="firma_supervisor_input">
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="firma-container">
          <div class="firma-label">
            <i class="bi bi-tools"></i>
            Firma Técnico
          </div>
          <div class="firma-box">
            <canvas id="firmaTecnicoCanvas"></canvas>
          </div>
          <input type="text" class="form-control mt-3" id="nombre_tecnico" name="nombre_tecnico" 
                 value="<?= htmlspecialchars($nombre_tecnico ?? '') ?>" readonly>
          <button type="button" class="btn btn-secondary btn-sm mt-2 w-100" onclick="sigTecnico.clear()">
            <i class="bi bi-eraser"></i> Limpiar
          </button>
          <input type="hidden" name="firma_tecnico" id="firma_tecnico_input">
        </div>
      </div>
    </div>

    <!-- BOTÓN ENVIAR -->
    <div class="text-center mt-5">
      <button type="submit" class="btn btn-success btn-lg">
        <i class="bi bi-save"></i> Guardar y Generar Reporte (PDF)
      </button>
    </div>
  </form>
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

// Detectar cuando se hace scroll en tablas (para ocultar el indicador)
document.addEventListener('DOMContentLoaded', function() {
  const tablas = document.querySelectorAll('.table-responsive');
  tablas.forEach(tabla => {
    tabla.addEventListener('scroll', function() {
      if (this.scrollLeft > 10) {
        this.closest('.custom-table')?.classList.add('scrolled');
      } else {
        this.closest('.custom-table')?.classList.remove('scrolled');
      }
    });
  });
});

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
  $('.equipo-select').select2({ 
    placeholder:"Buscar equipo...", 
    allowClear:true, 
    width:'100%',
    dropdownAutoWidth: true
  });

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
    const nombre = $(this).data('nombre') || $(`.nombre-${index}`).val() || '';

    if (id && texto && texto !== '-- Seleccione --') {
      const bloque = document.createElement('div');
      bloque.className = 'observacion-card';
      bloque.innerHTML = `
        <h6><i class="bi bi-wrench-adjustable"></i> ${texto}${nombre ? ' - ' + nombre : ''}</h6>
        <div class="mb-3">
          <label class="form-label"><i class="bi bi-text-left"></i> Observaciones y recomendaciones:</label>
          <textarea class="form-control observacion-texto" data-index="${index}" rows="3"
            placeholder="Escribe observaciones específicas para ${texto}..." required></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label"><i class="bi bi-images"></i> Imágenes del equipo:</label>
          <input 
            type="file" 
            class="form-control observacion-imagen" 
            data-index="${index}" 
            accept="image/*" 
            capture="camera" 
            multiple>
          <div id="preview-${index}" class="d-flex flex-wrap gap-2 mt-3"></div>
        </div>
      `;
      contenedor.appendChild(bloque);
    }
  });
}

const imagenesGuardadas = {};

$(document).on('change', '.observacion-imagen', function() {
  const index = $(this).data('index');
  const files = this.files;
  const preview = document.getElementById(`preview-${index}`);
  if (!imagenesGuardadas[index]) imagenesGuardadas[index] = [];

  if (files.length === 0) return;

  const formData = new FormData();
  for (const f of files) formData.append('imagenes[]', f);

  console.log('📸 Subiendo nueva imagen de equipo', index);

  fetch('subir_imagen.php', { method: 'POST', body: formData })
    .then(res => {
      if (!res.ok) throw new Error('Error HTTP ' + res.status);
      return res.json();
    })
    .then(rutas => {
      console.log('🟢 Rutas devueltas:', rutas);

      if (!Array.isArray(rutas) || rutas.length === 0) {
        console.warn('⚠️ No se devolvieron rutas válidas');
        return;
      }

      imagenesGuardadas[index].push(...rutas);
      preview.dataset.rutas = JSON.stringify(imagenesGuardadas[index]);

      rutas.forEach(ruta => {
        const img = document.createElement('img');
        img.src = ruta;
        img.className = 'img-thumbnail';
        img.style.maxWidth = '120px';
        img.style.maxHeight = '120px';
        preview.appendChild(img);
      });
    })
    .catch(err => {
      console.error('🔴 Error subiendo imagen:', err);
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
    const preview = document.getElementById(`preview-${index}`);
    const rutas = preview?.dataset?.rutas ? JSON.parse(preview.dataset.rutas) : [];

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