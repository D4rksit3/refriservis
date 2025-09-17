<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header("Location: /index.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/fpdf.php';

// Obtener datos
$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID inválido");
}

$stmt = $pdo->prepare("SELECT m.*, c.nombre AS cliente, i.nombre AS inventario
                       FROM mantenimientos m
                       LEFT JOIN clientes c ON m.cliente_id = c.id
                       LEFT JOIN inventario i ON m.inventario_id = i.id
                       WHERE m.id = ?");
$stmt->execute([$id]);
$mantenimiento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mantenimiento) {
    die("Mantenimiento no encontrado");
}

// Crear PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);

// Encabezado
$pdf->Cell(0, 10, 'REFRISERVIS S.A.C.', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'REPORTE DE SERVICIO TECNICO', 0, 1, 'C');
$pdf->Cell(0, 6, 'Oficina: (01) 6557907 | Emergencias: +51 943 048 606 | ventas@refriservissac.com', 0, 1, 'C');
$pdf->Ln(8);

// Datos del cliente
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 6, 'N° Reporte:', 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 6, $mantenimiento['id'], 1);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 6, 'Cliente:', 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(50, 6, utf8_decode($mantenimiento['cliente']), 1, 1);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 6, 'Equipo:', 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 6, utf8_decode($mantenimiento['inventario']), 1);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 6, 'Fecha:', 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(50, 6, $mantenimiento['fecha'], 1, 1);

$pdf->Ln(6);

// Trabajos realizados
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, 'TRABAJOS REALIZADOS:', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 6, utf8_decode($mantenimiento['descripcion']));

// Observaciones
$pdf->Ln(6);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, 'OBSERVACIONES Y RECOMENDACIONES:', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 6, utf8_decode($mantenimiento['estado']));

// Firmas
$pdf->Ln(20);
$pdf->Cell(60, 6, '_________________________', 0, 0, 'C');
$pdf->Cell(60, 6, '_________________________', 0, 0, 'C');
$pdf->Cell(60, 6, '_________________________', 0, 1, 'C');
$pdf->Cell(60, 6, 'Firma del Cliente', 0, 0, 'C');
$pdf->Cell(60, 6, 'V°B° Supervisor R.F.S', 0, 0, 'C');
$pdf->Cell(60, 6, 'Firma del Tecnico', 0, 1, 'C');

// Salida
$filename = "Reporte_Mantenimiento_" . $mantenimiento['id'] . ".pdf";
$pdf->Output('I', $filename);
