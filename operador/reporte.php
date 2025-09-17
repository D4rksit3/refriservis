<?php
session_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/fpdf.php';

// Recibir ID del reporte
$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("
    SELECT r.*, 
           m.titulo, m.fecha, 
           c.nombre AS cliente, c.direccion, 
           i.nombre AS equipo, i.marca, i.modelo, i.serie, i.gas, i.codigo, i.ubicacion
    FROM reportes r
    LEFT JOIN mantenimientos m ON r.mantenimiento_id = m.id
    LEFT JOIN clientes c ON m.cliente_id = c.id
    LEFT JOIN inventario i ON m.inventario_id = i.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$reporte = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reporte) {
    die("Reporte no encontrado");
}

// Helper para codificación
function safeText($txt) {
    return mb_convert_encoding((string)$txt, 'ISO-8859-1', 'UTF-8');
}

$pdf = new FPDF();
$pdf->AddPage();

// Encabezado
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,8,'REFRISERVIS S.A.C.',0,1,'C');
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,'REPORTE DE SERVICIO TECNICO',0,1,'C');
$pdf->Cell(0,6,'Oficina: (01) 6557907 | Emergencias: +51 943 048 606 | ventas@refriservissac.com',0,1,'C');
$pdf->Ln(5);

// Datos principales
$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'N° Reporte:',1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(50,6,$reporte['id'],1);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Cliente:',1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(70,6,safeText($reporte['cliente']),1,1);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Equipo:',1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(50,6,safeText($reporte['equipo']),1);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Fecha:',1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(70,6,$reporte['fecha'],1,1);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Dirección:',1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(155,6,safeText($reporte['direccion']),1,1);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Ubicación:',1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(155,6,safeText($reporte['ubicacion']),1,1);
$pdf->Ln(6);

// Tabla de identificación de equipos
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'DATOS DE IDENTIFICACION DE LOS EQUIPOS A INTERVENIR',0,1);
$pdf->SetFont('Arial','B',8);
$headers = ['Marca','Modelo','Serie','Gas','Código'];
$widths  = [40,40,40,35,35];
foreach ($headers as $i=>$h) {
    $pdf->Cell($widths[$i],6,$h,1,0,'C');
}
$pdf->Ln();
$pdf->SetFont('Arial','',8);
$pdf->Cell(40,6,safeText($reporte['marca']),1);
$pdf->Cell(40,6,safeText($reporte['modelo']),1);
$pdf->Cell(40,6,safeText($reporte['serie']),1);
$pdf->Cell(35,6,safeText($reporte['gas']),1);
$pdf->Cell(35,6,safeText($reporte['codigo']),1);
$pdf->Ln(10);

// Parámetros de funcionamiento (placeholder: puedes adaptar para traer de otra tabla si ya los guardas)
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'PARAMETROS DE FUNCIONAMIENTO',0,1);
$pdf->SetFont('Arial','B',8);
$pdf->Cell(50,6,'Medida',1,0,'C');
$pdf->Cell(35,6,'Antes',1,0,'C');
$pdf->Cell(35,6,'Despues',1,0,'C');
$pdf->Cell(35,6,'Antes',1,0,'C');
$pdf->Cell(35,6,'Despues',1,1,'C');

$pdf->SetFont('Arial','',8);
$parametros = ['Corriente electrica nominal (A)','Tension electrica nominal (V)','Presion de descarga (PSI)','Presion de succion (PSI)'];
foreach ($parametros as $p) {
    $pdf->Cell(50,6,$p,1);
    $pdf->Cell(35,6,'',1);
    $pdf->Cell(35,6,'',1);
    $pdf->Cell(35,6,'',1);
    $pdf->Cell(35,6,'',1,1);
}
$pdf->Ln(6);

// Trabajos realizados
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'TRABAJOS REALIZADOS',0,1);
$pdf->SetFont('Arial','',9);
$pdf->MultiCell(0,6,safeText($reporte['trabajos'] ?? ''));
$pdf->Ln(6);

// Observaciones
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'OBSERVACIONES Y RECOMENDACIONES',0,1);
$pdf->SetFont('Arial','',9);
$pdf->MultiCell(0,6,safeText($reporte['observaciones'] ?? ''));
$pdf->Ln(10);

// Fotos (espacios en blanco para luego implementar)
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'FOTOS DEL/OS EQUIPOS',0,1);
$pdf->Ln(20);
$pdf->Rect(20,$pdf->GetY(),80,50);
$pdf->Rect(110,$pdf->GetY(),80,50);
$pdf->Ln(60);

// Firmas
$pdf->Ln(10);
$pdf->Cell(60,6,'_________________________',0,0,'C');
$pdf->Cell(60,6,'_________________________',0,0,'C');
$pdf->Cell(60,6,'_________________________',0,1,'C');
$pdf->Cell(60,6,'Firma del Cliente',0,0,'C');
$pdf->Cell(60,6,'Firma del Supervisor',0,0,'C');
$pdf->Cell(60,6,'Firma del Tecnico',0,1,'C');

// Incluir firmas si existen (guardadas como base64 o archivos)
$y = $pdf->GetY()-25;
if (!empty($reporte['firma_cliente'])) {
    $data = str_replace('data:image/png;base64,', '', $reporte['firma_cliente']);
    $img = __DIR__ . "/../uploads/firma_cliente_{$reporte['id']}.png";
    file_put_contents($img, base64_decode($data));
    $pdf->Image($img, 25, $y, 40);
}
if (!empty($reporte['firma_supervisor'])) {
    $data = str_replace('data:image/png;base64,', '', $reporte['firma_supervisor']);
    $img = __DIR__ . "/../uploads/firma_supervisor_{$reporte['id']}.png";
    file_put_contents($img, base64_decode($data));
    $pdf->Image($img, 85, $y, 40);
}
if (!empty($reporte['firma_tecnico'])) {
    $data = str_replace('data:image/png;base64,', '', $reporte['firma_tecnico']);
    $img = __DIR__ . "/../uploads/firma_tecnico_{$reporte['id']}.png";
    file_put_contents($img, base64_decode($data));
    $pdf->Image($img, 145, $y, 40);
}

$filename = "Reporte_" . $reporte['id'] . ".pdf";
$pdf->Output('I', $filename);
