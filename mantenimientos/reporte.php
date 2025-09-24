<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/fpdf.php'; // tu FPDF nativo

$id = $_GET['id'] ?? null;
if (!$id) exit("⚠️ ID no proporcionado");

$stmt = $pdo->prepare("SELECT * FROM mantenimientos WHERE id=?");
$stmt->execute([$id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$m || $m['estado'] !== 'finalizado') {
    exit("⚠️ Reporte solo disponible para finalizados");
}

$cliente = $pdo->query("SELECT cliente FROM clientes WHERE id=" . $m['cliente_id'])->fetchColumn();

// =========================
// PDF con FPDF
// =========================
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);

// Encabezado
$pdf->Cell(0,10,utf8_decode("FORMATO DE CALIDAD"),0,1,'C');
$pdf->Cell(0,10,utf8_decode("REFRISERVIS S.A.C."),0,1,'C');
$pdf->Ln(5);

$pdf->SetFont('Arial','',11);
$pdf->MultiCell(0,7,utf8_decode("REPORTE DE SERVICIO TECNICO"),0,'C');
$pdf->Ln(3);

// Datos principales
$pdf->Cell(0,8,utf8_decode("Oficina: (01) 6557907  |  Emergencias: +51 943 048 606"),0,1);
$pdf->Cell(0,8,utf8_decode("ventas@refriservissac.com"),0,1);
$pdf->Ln(5);

$pdf->Cell(0,8,utf8_decode("Reporte N°: 001-" . str_pad($m['id'],6,'0',STR_PAD_LEFT)),0,1);
$pdf->Cell(0,8,utf8_decode("Cliente: " . $cliente),0,1);
$pdf->Cell(0,8,utf8_decode("Fecha: " . $m['fecha']),0,1);
$pdf->Ln(5);

// Sección equipos (ejemplo tabla vacía)
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,8,utf8_decode("DATOS DE IDENTIFICACIÓN DE LOS EQUIPOS A INTERVENIR"),0,1);
$pdf->SetFont('Arial','',10);

// Dibujar tabla de ejemplo (7 filas, 6 columnas)
$headers = ["#", "Tipo", "Marca", "Modelo", "Serie", "Gas"];
$w = [10, 30, 30, 30, 40, 40];
foreach ($headers as $i => $h) {
    $pdf->Cell($w[$i],8,utf8_decode($h),1,0,'C');
}
$pdf->Ln();
for ($i=1;$i<=7;$i++){
    $pdf->Cell($w[0],8,$i,1);
    $pdf->Cell($w[1],8,"",1);
    $pdf->Cell($w[2],8,"",1);
    $pdf->Cell($w[3],8,"",1);
    $pdf->Cell($w[4],8,"",1);
    $pdf->Cell($w[5],8,"",1);
    $pdf->Ln();
}

// Firma
$pdf->Ln(15);
$pdf->Cell(60,8,"Firma del Cliente",0,0,'C');
$pdf->Cell(60,8,utf8_decode("V°B° Supervisor R.F.S S.A.C"),0,0,'C');
$pdf->Cell(60,8,"Firma del técnico ejecutor",0,1,'C');

// Salida
$pdf->Output("D","reporte_{$m['id']}.pdf");
