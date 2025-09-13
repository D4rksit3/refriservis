<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'digitador') header('Location: /index.php');
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../vendor/autoload.php'; // si usas composer con fpdf/tcpdf

if (empty($_POST['seleccionados'])) {
    die("No seleccionaste registros");
}

$ids = $_POST['seleccionados'];
$in  = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT m.*, c.nombre as cliente, c.direccion, i.nombre as inventario 
    FROM mantenimientos m 
    LEFT JOIN clientes c ON c.id=m.cliente_id 
    LEFT JOIN inventario i ON i.id=m.inventario_id 
    WHERE m.id IN ($in)");
$stmt->execute($ids);
$rows = $stmt->fetchAll();

if (!$rows) die("No se encontraron registros");

// --- Generar PDF estilo checklist ---
require_once(__DIR__."/../libs/fpdf.php"); // ruta a FPDF

$pdf = new FPDF();
$pdf->AddPage();

// Logo
$pdf->Image(__DIR__.'/../assets/img/logo.png',10,8,40);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(190,10,utf8_decode("CHECK LIST DE MANTENIMIENTO PREVENTIVO DE EQUIPOS"),0,1,'C');
$pdf->Cell(190,10,utf8_decode("CORTINAS DE AIRE"),0,1,'C');
$pdf->Ln(5);

// Datos generales (Cliente único)
$cliente = $rows[0]['cliente'];
$direccion = $rows[0]['direccion'] ?? '';
$pdf->SetFont('Arial','',10);
$pdf->Cell(30,8,"Cliente:",1); $pdf->Cell(160,8,utf8_decode($cliente),1,1);
$pdf->Cell(30,8,"Direccion:",1); $pdf->Cell(160,8,utf8_decode($direccion),1,1);
$pdf->Ln(5);

// Equipos
$pdf->SetFont('Arial','B',10);
$pdf->Cell(10,8,"#",1);
$pdf->Cell(60,8,"Equipo",1);
$pdf->Cell(120,8,"Descripcion",1,1);

$pdf->SetFont('Arial','',9);
$n=1;
foreach($rows as $r){
    $pdf->Cell(10,8,$n++,1);
    $pdf->Cell(60,8,utf8_decode($r['inventario']),1);
    $pdf->Cell(120,8,utf8_decode($r['descripcion']),1,1);
}

// Aquí puedes añadir las secciones de parámetros de funcionamiento y actividades
// usando tablas con $pdf->Cell()

$pdf->Output("I","checklist.pdf");
