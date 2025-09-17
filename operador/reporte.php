<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../lib/fpdf.php';

// Recibir ID del reporte
$id = $_GET['id'] ?? null;

// Datos del reporte + mantenimiento + inventario + cliente
$stmt = $pdo->prepare("
    SELECT r.*, 
           m.fecha, m.titulo, 
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

if(!$reporte) die("Reporte no encontrado");

// Traer parámetros de funcionamiento
$stmt = $pdo->prepare("SELECT * FROM parametros_reporte WHERE reporte_id=? ORDER BY id ASC");
$stmt->execute([$id]);
$parametros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traer fotos
$stmt = $pdo->prepare("SELECT * FROM reportes_fotos WHERE reporte_id=?");
$stmt->execute([$id]);
$fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

function safeText($txt){ return mb_convert_encoding((string)$txt, 'ISO-8859-1', 'UTF-8'); }

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
$pdf->Cell(35,6,'N° Reporte:',1); $pdf->SetFont('Arial','',10); $pdf->Cell(50,6,$reporte['id'],1);
$pdf->SetFont('Arial','B',10); $pdf->Cell(35,6,'Cliente:',1); $pdf->SetFont('Arial','',10); $pdf->Cell(70,6,safeText($reporte['cliente']),1,1);

$pdf->SetFont('Arial','B',10); $pdf->Cell(35,6,'Equipo:',1); $pdf->SetFont('Arial','',10); $pdf->Cell(50,6,safeText($reporte['equipo']),1);
$pdf->SetFont('Arial','B',10); $pdf->Cell(35,6,'Fecha:',1); $pdf->SetFont('Arial','',10); $pdf->Cell(70,6,$reporte['fecha'],1,1);
$pdf->SetFont('Arial','B',10); $pdf->Cell(35,6,'Dirección:',1); $pdf->SetFont('Arial','',10); $pdf->Cell(155,6,safeText($reporte['direccion']),1,1);
$pdf->SetFont('Arial','B',10); $pdf->Cell(35,6,'Ubicación:',1); $pdf->SetFont('Arial','',10); $pdf->Cell(155,6,safeText($reporte['ubicacion']),1,1);
$pdf->Ln(6);

// Datos de identificación del equipo
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'DATOS DE IDENTIFICACION DE LOS EQUIPOS A INTERVENIR',0,1);
$pdf->SetFont('Arial','B',8);
$headers = ['Marca','Modelo','Serie','Gas','Código'];
$widths  = [40,40,40,35,35];
foreach($headers as $i=>$h){ $pdf->Cell($widths[$i],6,$h,1,0,'C'); }
$pdf->Ln();
$pdf->SetFont('Arial','',8);
$pdf->Cell(40,6,safeText($reporte['marca']),1);
$pdf->Cell(40,6,safeText($reporte['modelo']),1);
$pdf->Cell(40,6,safeText($reporte['serie']),1);
$pdf->Cell(35,6,safeText($reporte['gas']),1);
$pdf->Cell(35,6,safeText($reporte['codigo']),1);
$pdf->Ln(10);

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
foreach($parametros as $p){
    $pdf->Cell(50,6,safeText($p['medida']),1);
    $pdf->Cell(35,6,safeText($p['antes1']),1);
    $pdf->Cell(35,6,safeText($p['despues1']),1);
    $pdf->Cell(35,6,safeText($p['antes2']),1);
    $pdf->Cell(35,6,safeText($p['despues2']),1,1);
}
$pdf->Ln(6);

// Trabajos y observaciones
$pdf->SetFont('Arial','B',10); $pdf->Cell(0,6,'TRABAJOS REALIZADOS',0,1); $pdf->SetFont('Arial','',9);
$pdf->MultiCell(0,6,safeText($reporte['trabajos'] ?? '')); $pdf->Ln(6);
$pdf->SetFont('Arial','B',10); $pdf->Cell(0,6,'OBSERVACIONES Y RECOMENDACIONES',0,1); $pdf->SetFont('Arial','',9);
$pdf->MultiCell(0,6,safeText($reporte['observaciones'] ?? '')); $pdf->Ln(10);

// Fotos
$pdf->SetFont('Arial','B',10); $pdf->Cell(0,6,'FOTOS DEL/OS EQUIPOS',0,1); $pdf->Ln(5);
$x = 20; $y = $pdf->GetY();
foreach($fotos as $f){
    $file = __DIR__.'/../uploads/'.$f['archivo'];
    if(file_exists($file)){
        $pdf->Image($file,$x,$y,80,50);
        $x+=90;
        if($x>120){$x=20;$y+=60;}
    }
}
$pdf->Ln(60);

// Firmas
$pdf->Ln(10);
$pdf->Cell(60,6,'_________________________',0,0,'C');
$pdf->Cell(60,6,'_________________________',0,0,'C');
$pdf->Cell(60,6,'_________________________',0,1,'C');
$pdf->Cell(60,6,'Firma del Cliente',0,0,'C');
$pdf->Cell(60,6,'Firma del Supervisor',0,0,'C');
$pdf->Cell(60,6,'Firma del Tecnico',0,1,'C');

$y = $pdf->GetY()-25;
$firmas = ['firma_cliente'=>25,'firma_supervisor'=>85,'firma_tecnico'=>145];
foreach($firmas as $col=>$xpos){
    if(!empty($reporte[$col])){
        $data = str_replace('data:image/png;base64,','',$reporte[$col]);
        $imgFile = __DIR__."/../uploads/{$col}_{$reporte['id']}.png";
        file_put_contents($imgFile,base64_decode($data));
        $pdf->Image($imgFile,$xpos,$y,40);
    }
}

$pdf->Output('I',"Reporte_{$reporte['id']}.pdf");
