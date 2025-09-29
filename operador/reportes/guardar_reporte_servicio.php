<?php
require '../../config/database.php';
require '../../lib/fpdf.php';

$id = $_GET['id'] ?? null;
if (!$id) die("ID inválido");

$stmt = $pdo->prepare("SELECT * FROM mantenimientos WHERE id = ?");
$stmt->execute([$id]);
$reporte = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$reporte) die("No encontrado");

// Marcar como generado
$pdo->prepare("UPDATE mantenimientos SET reporte_generado=1 WHERE id=?")->execute([$id]);

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);

// Logo + cabecera
$pdf->Image('../../lib/logo.jpeg',10,10,30);
$pdf->Cell(190,10,utf8_decode("Reporte de Servicio Técnico"),0,1,'C');
$pdf->Ln(10);

// Datos básicos
$pdf->SetFont('Arial','',10);
$pdf->Cell(95,6,"Titulo: " . utf8_decode($reporte['titulo']),0,0);
$pdf->Cell(95,6,"Fecha: " . $reporte['fecha'],0,1);
$pdf->Cell(95,6,"Categoria: " . utf8_decode($reporte['categoria']),0,0);
$pdf->Cell(95,6,"Cliente ID: " . $reporte['cliente_id'],0,1);
$pdf->Ln(5);

// Trabajos y observaciones
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,6,"Trabajos Realizados:",0,1);
$pdf->SetFont('Arial','',10);
$pdf->MultiCell(0,6,utf8_decode($reporte['trabajos']));
$pdf->Ln(3);

$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,6,"Observaciones:",0,1);
$pdf->SetFont('Arial','',10);
$pdf->MultiCell(0,6,utf8_decode($reporte['observaciones']));
$pdf->Ln(10);

// Firmas
if ($reporte['firma_cliente']) {
    $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i','',$reporte['firma_cliente']));
    file_put_contents("firma_cliente.png",$img);
    $pdf->Image("firma_cliente.png",20,200,40,20);
    $pdf->Text(25,225,"Cliente");
}
if ($reporte['firma_supervisor']) {
    $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i','',$reporte['firma_supervisor']));
    file_put_contents("firma_supervisor.png",$img);
    $pdf->Image("firma_supervisor.png",90,200,40,20);
    $pdf->Text(95,225,"Supervisor");
}
if ($reporte['firma_tecnico']) {
    $img = base64_decode(preg_replace('#^data:image/\w+;base64,#i','',$reporte['firma_tecnico']));
    file_put_contents("firma_tecnico.png",$img);
    $pdf->Image("firma_tecnico.png",160,200,40,20);
    $pdf->Text(165,225,"Técnico");
}

$pdf->Ln(30);

// Fotos
$fotos = json_decode($reporte['fotos'],true);
if ($fotos && is_array($fotos)) {
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,"Registro Fotográfico",0,1,'C');
    foreach ($fotos as $foto) {
        if (file_exists("../../uploads/$foto")) {
            $pdf->Image("../../uploads/$foto",null,null,90,60);
            $pdf->Ln(65);
        }
    }
}

$pdf->Output("I","reporte_$id.pdf");
