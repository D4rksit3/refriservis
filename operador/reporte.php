<?php
session_start();
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/fpdf.php';

// Recibir ID del reporte
$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("
    SELECT r.*, m.titulo, m.fecha, c.nombre AS cliente, i.nombre AS inventario
    FROM reportes r
    LEFT JOIN mantenimientos m ON r.mantenimiento_id = m.id
    LEFT JOIN clientes c ON m.cliente_id = c.id
    LEFT JOIN inventario i ON m.inventario_id = i.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$reporte = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reporte) die("Reporte no encontrado");

// Helper para codificación y valores seguros
function safeText($txt) {
    return mb_convert_encoding((string)($txt ?? ''), 'ISO-8859-1', 'UTF-8');
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
$pdf->Cell(50,6,safeText($reporte['id']),1);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Cliente:',1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(70,6,safeText($reporte['cliente']),1,1);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Equipo:',1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(50,6,safeText($reporte['inventario']),1);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Fecha:',1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(70,6,safeText($reporte['fecha']),1,1);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Coordinador:',1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(50,6,safeText($_SESSION['usuario'] ?? ''),1);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(35,6,'Local:',1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(70,6,'-',1,1);
$pdf->Ln(6);

// Tabla de identificación de equipos
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'DATOS DE IDENTIFICACION DE LOS EQUIPOS A INTERVENIR',0,1);
$pdf->SetFont('Arial','B',8);
$headers = ['Tipo','Marca','Modelo','Ubicación/Serie','Gas','Código'];
$widths  = [30,30,30,40,30,30];
foreach ($headers as $i=>$h) $pdf->Cell($widths[$i],6,$h,1,0,'C');
$pdf->Ln();
$pdf->SetFont('Arial','',8);

// Simulación de filas (rellenar según tu inventario real si tienes otra tabla)
for ($i=0;$i<3;$i++) {
    $pdf->Cell(30,6,'---',1);
    $pdf->Cell(30,6,'---',1);
    $pdf->Cell(30,6,'---',1);
    $pdf->Cell(40,6,'---',1);
    $pdf->Cell(30,6,'---',1);
    $pdf->Cell(30,6,'---',1);
    $pdf->Ln();
}
$pdf->Ln(6);

// Parámetros de funcionamiento
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
    $pdf->Cell(50,6,safeText($p),1);
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

// Fotos (simulación de espacio)
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
$pdf->Cell(60,6,'V°B° Supervisor R.F.S',0,0,'C');
$pdf->Cell(60,6,'Firma del Tecnico',0,1,'C');

// Incluir firma si existe
if (!empty($reporte['firma']) && file_exists(__DIR__ . '/../uploads/' . $reporte['firma'])) {
    $pdf->Image(__DIR__ . '/../uploads/' . $reporte['firma'], 30, $pdf->GetY()-30, 40);
}

$filename = "Reporte_" . ($reporte['id'] ?? '000') . ".pdf";
$pdf->Output('I', $filename);
