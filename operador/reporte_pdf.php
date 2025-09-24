<?php
// operador/reporte_pdf.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header('Location: /index.php'); exit;
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/fpdf.php';

$id = $_GET['id'] ?? null;
if (!$id) die('ID no proporcionado');

$stmt = $pdo->prepare("SELECT r.*, m.*, c.cliente, c.direccion, c.responsable FROM reportes r JOIN mantenimientos m ON m.id=r.mantenimiento_id LEFT JOIN clientes c ON c.id=m.cliente_id WHERE r.id=?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) die('Reporte no encontrado');

// decode JSON fields
$parametros = json_decode($data['parametros'] ?? '[]', true);
$fotos = json_decode($data['fotos'] ?? '[]', true);
$equipos_saved = []; // We didn't save equipos JSON separately in this version; we'll try get from mantenimiento
$inventarioRows = $pdo->query("SELECT id, nombre, marca, modelo, serie, gas, codigo FROM inventario")->fetchAll(PDO::FETCH_UNIQUE);
for ($i=1;$i<=7;$i++){
  $eid = $data["equipo$i"] ?? null;
  if ($eid && isset($inventarioRows[$eid])) $equipos_saved[] = $inventarioRows[$eid];
}

// PDF
$pdf = new FPDF('P','mm','A4');
$pdf->SetAutoPageBreak(true,15);
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,7,utf8_decode('FORMATO DE CALIDAD'),0,1,'C');
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,7,utf8_decode('REFRISERVIS S.A.C.'),0,1,'C');
$pdf->Ln(3);
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,utf8_decode('REPORTE DE SERVICIO TECNICO'),0,1,'C');
$pdf->Ln(2);
$pdf->SetFont('Arial','',9);
$pdf->Cell(0,5,utf8_decode('Oficina: (01) 6557907    Emergencias: +51 943 048 606'),0,1);
$pdf->Cell(0,5,utf8_decode('ventas@refriservissac.com'),0,1);
$pdf->Ln(4);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,utf8_decode('Reporte N°: '.$data['numero_reporte']),0,1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,utf8_decode('Cliente: '.($data['cliente'] ?? '-')),0,1);
$pdf->Cell(0,6,utf8_decode('Dirección: '.($data['direccion'] ?? '-')),0,1);
$pdf->Cell(0,6,utf8_decode('Coordinador/Responsable: '.($data['responsable'] ?? '-')),0,1);
$pdf->Cell(0,6,utf8_decode('Fecha: '.($data['fecha'] ?? '-')),0,1);
$pdf->Ln(4);

// Equipos: dibujar tabla con 7 columnas verticales como en tu documento
$pdf->SetFont('Arial','B',9);
$pdf->Cell(12,6,'#',1,0,'C');
$pdf->Cell(30,6,'Tipo',1,0,'C');
$pdf->Cell(30,6,'Marca',1,0,'C');
$pdf->Cell(30,6,'Modelo',1,0,'C');
$pdf->Cell(30,6,'Ubicación/Serie',1,0,'C');
$pdf->Cell(26,6,'Tipo gas',1,0,'C');
$pdf->Cell(22,6,'Código',1,1,'C');

$pdf->SetFont('Arial','',9);
if ($equipos_saved) {
  foreach($equipos_saved as $i=>$eq){
    $pdf->Cell(12,6,($i+1),1,0,'C');
    $pdf->Cell(30,6,utf8_decode($eq['nombre'] ?? ''),1,0);
    $pdf->Cell(30,6,utf8_decode($eq['marca'] ?? ''),1,0);
    $pdf->Cell(30,6,utf8_decode($eq['modelo'] ?? ''),1,0);
    $pdf->Cell(30,6,utf8_decode($eq['serie'] ?? ''),1,0);
    $pdf->Cell(26,6,utf8_decode($eq['gas'] ?? ''),1,0);
    $pdf->Cell(22,6,utf8_decode($eq['codigo'] ?? ''),1,1);
  }
} else {
  for($i=0;$i<7;$i++){
    $pdf->Cell(12,6,($i+1),1,0,'C');
    $pdf->Cell(30,6,'',1,0);
    $pdf->Cell(30,6,'',1,0);
    $pdf->Cell(30,6,'',1,0);
    $pdf->Cell(30,6,'',1,0);
    $pdf->Cell(26,6,'',1,0);
    $pdf->Cell(22,6,'',1,1);
  }
}

$pdf->Ln(4);

// Parámetros: mostramos algunas filas (si existen en JSON, las imprimimos)
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,utf8_decode('PARAMETROS DE FUNCIONAMIENTO (Antes / Después)'),0,1);
$pdf->SetFont('Arial','',9);

$labels = [
  'Corriente eléctrica nominal (Amperios) L1',
  'Corriente L2',
  'Corriente L3',
  'Tensión eléctrica nominal V1',
  'Tensión V2',
  'Tensión V3',
  'Presión de descarga (PSI)',
  'Presión de succión (PSI)'
];

// Si hay parámetros guardados, imprimimos por medida
if (!empty($parametros) && is_array($parametros)) {
  foreach($labels as $lab){
    $pdf->Cell(0,5,utf8_decode($lab),0,1);
    // intentamos leer usando md5 key como en el form
    $key = md5($lab);
    if (isset($parametros[$key]) && is_array($parametros[$key])) {
      foreach($parametros[$key] as $eqIndex => $vals) {
        $antes = $vals['antes'] ?? '';
        $desp = $vals['despues'] ?? '';
        $pdf->Cell(40,5,utf8_decode('Equipo '.($eqIndex+1).' Antes: '.$antes),0,0);
        $pdf->Cell(40,5,utf8_decode('Desp.: '.$desp),0,1);
      }
    } else {
      $pdf->Cell(0,5,utf8_decode('No hay mediciones registradas'),0,1);
    }
    $pdf->Ln(1);
  }
} else {
  $pdf->Cell(0,5,utf8_decode('No hay parámetros registrados'),0,1);
}
$pdf->Ln(4);

// Trabajos realizados
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,utf8_decode('TRABAJOS REALIZADOS'),0,1);
$pdf->SetFont('Arial','',9);
$pdf->MultiCell(0,5,utf8_decode($data['trabajos_realizados'] ?? ''));
$pdf->Ln(4);

// Observaciones
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,utf8_decode('OBSERVACIONES Y RECOMENDACIONES'),0,1);
$pdf->SetFont('Arial','',9);
$pdf->MultiCell(0,5,utf8_decode($data['observaciones'] ?? ''));
$pdf->Ln(4);

// Fotos: mostrar en varias filas
if (!empty($fotos) && is_array($fotos)) {
  $pdf->SetFont('Arial','B',10);
  $pdf->Cell(0,6,utf8_decode('FOTOS DEL/OS EQUIPOS'),0,1);
  $x = $pdf->GetX(); $y = $pdf->GetY();
  $count = 0;
  foreach($fotos as $f) {
    $path = __DIR__ . '/..' . $f; // f is like /uploads/reportes/...
    if (file_exists($path)) {
      // colocar 2 fotos por fila
      if ($count % 2 == 0) {
        $pdf->Ln(2);
      }
      $imgW = 90; $imgH = 60;
      $pdf->Image($path, $pdf->GetX(), $pdf->GetY(), $imgW, $imgH);
      if ($count % 2 == 0) $pdf->SetX($pdf->GetX() + $imgW + 5);
      else $pdf->Ln($imgH + 3);
      $count++;
    }
  }
  $pdf->Ln(6);
}

// Firmas
$pdf->Ln(6);
$pdf->SetFont('Arial','',9);
$colW = 60;
$pdf->Cell($colW,6,utf8_decode('Firma del Cliente'),0,0,'C');
$pdf->Cell($colW,6,utf8_decode('V°B° Supervisor R.F.S S.A.C'),0,0,'C');
$pdf->Cell($colW,6,utf8_decode('Firma del técnico ejecutor'),0,1,'C');
$pdf->Ln(4);

// Mostrar imágenes de firma si existen
$yBefore = $pdf->GetY();
$h = 30;
$colX = $pdf->GetX();
$startX = $pdf->GetX();

$signPaths = [
  $data['firma_cliente'] ?? null,
  $data['firma_supervisor'] ?? null,
  $data['firma_tecnico'] ?? null
];
$pdf->SetFont('Arial','',8);
for($i=0;$i<3;$i++){
  $sp = $signPaths[$i];
  $pdf->SetX($startX + ($i*$colW));
  if ($sp && file_exists(__DIR__ . '/..' . $sp)) {
    $pdf->Image(__DIR__ . '/..' . $sp, $pdf->GetX()+5, $pdf->GetY(), $colW-10, $h);
  } else {
    $pdf->Cell($colW, $h, '', 1, 0, 'C');
  }
}
$pdf->Ln($h + 6);

// Pie
$pdf->SetFont('Arial','I',8);
$pdf->Cell(0,5,utf8_decode('Documento generado por Refriservis S.A.C.'),0,1,'C');

// Descargar
$pdf->Output('D', 'reporte_'.$data['numero_reporte'].'.pdf');
exit;
