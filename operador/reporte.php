<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header("Location: /index.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/fpdf.php';

$id = $_GET['id'] ?? null;
$stmt = $pdo->prepare("SELECT r.*, m.titulo, m.fecha, c.nombre AS cliente, i.nombre AS inventario
                       FROM reportes r
                       LEFT JOIN mantenimientos m ON r.mantenimiento_id = m.id
                       LEFT JOIN clientes c ON m.cliente_id = c.id
                       LEFT JOIN inventario i ON m.inventario_id = i.id
                       WHERE r.id = ?");
$stmt->execute([$id]);
$reporte = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reporte) die("Reporte no encontrado");

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,'REFRISERVIS S.A.C.',0,1,'C');
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,'REPORTE DE SERVICIO TECNICO',0,1,'C');
$pdf->Ln(10);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'Cliente: ' . utf8_decode($reporte['cliente']),0,1);
$pdf->Cell(0,6,'Equipo: ' . utf8_decode($reporte['inventario']),0,1);
$pdf->Cell(0,6,'Fecha: ' . $reporte['fecha'],0,1);
$pdf->Ln(5);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'Trabajos Realizados:',0,1);
$pdf->SetFont('Arial','',10);
$pdf->MultiCell(0,6,utf8_decode($reporte['trabajos']));
$pdf->Ln(5);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'Observaciones:',0,1);
$pdf->SetFont('Arial','',10);
$pdf->MultiCell(0,6,utf8_decode($reporte['observaciones']));
$pdf->Ln(15);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,6,'Firma:',0,1);
if (!empty($reporte['firma']) && file_exists(__DIR__ . '/../uploads/' . $reporte['firma'])) {
    $pdf->Image(__DIR__ . '/../uploads/' . $reporte['firma'], 30, $pdf->GetY(), 60);
}

$filename = "Reporte_" . $reporte['id'] . ".pdf";
$pdf->Output('I', $filename);
