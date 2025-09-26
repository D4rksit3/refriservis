<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header('Location: /../index.php');
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/fpdf.php';

// =======================
// Capturar datos del form
// =======================
$mantenimiento_id = $_POST['mantenimiento_id'] ?? null;
$equipos = $_POST['equipos'] ?? [];
$parametros = $_POST['parametros'] ?? [];
$trabajos = $_POST['trabajos'] ?? '';
$observaciones = $_POST['observaciones'] ?? '';
$firma_cliente = $_POST['firma_cliente'] ?? '';
$firma_supervisor = $_POST['firma_supervisor'] ?? '';
$firma_tecnico = $_POST['firma_tecnico'] ?? '';

// Traer datos del mantenimiento + cliente
$stmt = $pdo->prepare("
  SELECT m.*, c.cliente, c.direccion, c.responsable, c.telefono, m.fecha
  FROM mantenimientos m
  LEFT JOIN clientes c ON c.id = m.cliente_id
  WHERE m.id = ?
");
$stmt->execute([$mantenimiento_id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$m) {
    die("No se encontró mantenimiento");
}

// =======================
// Procesar fotos (BLOB en memoria)
// =======================
$fotos = [];
if (!empty($_FILES['fotos']['tmp_name'][0])) {
    foreach ($_FILES['fotos']['tmp_name'] as $tmp) {
        if (is_uploaded_file($tmp)) {
            $fotos[] = $tmp; // guardamos la ruta temporal para pasársela directo a FPDF
        }
    }
}

// =======================
// Clase personalizada PDF
// =======================
class PDF extends FPDF {
    function Header() {
        // Logo
        $this->Image(__DIR__.'/../../public/logo.png', 10, 6, 30);
        // Título
        $this->SetFont('Arial','B',12);
        $this->Cell(0,10,'REPORTE DE SERVICIO TECNICO',0,1,'C');
        $this->Ln(5);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',10);

// =======================
// Datos del cliente
// =======================
$pdf->SetFont('Arial','B',10);
$pdf->Cell(30,7,'Cliente:',1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(80,7,utf8_decode($m['cliente']),1);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(30,7,'Fecha:',1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(50,7,$m['fecha'],1);
$pdf->Ln();

$pdf->SetFont('Arial','B',10);
$pdf->Cell(30,7,'Direccion:',1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(160,7,utf8_decode($m['direccion']),1);
$pdf->Ln();

$pdf->SetFont('Arial','B',10);
$pdf->Cell(30,7,'Responsable:',1);
$pdf->SetFont('Arial','',10);
$pdf->Cell(160,7,utf8_decode($m['responsable']),1);
$pdf->Ln(12);

// =======================
// Equipos
// =======================
$pdf->SetFont('Arial','B',11);
$pdf->Cell(0,7,'Datos de identificacion de equipos',1,1,'C');

$pdf->SetFont('Arial','B',9);
$pdf->Cell(10,7,'#',1);
$pdf->Cell(30,7,'Identificador',1);
$pdf->Cell(30,7,'Marca',1);
$pdf->Cell(30,7,'Modelo',1);
$pdf->Cell(50,7,'Ubicacion',1);
$pdf->Cell(30,7,'Voltaje',1);
$pdf->Ln();

$pdf->SetFont('Arial','',9);
for($i=1;$i<=7;$i++){
    $eq = $equipos[$i] ?? [];
    $pdf->Cell(10,7,$i,1);
    $pdf->Cell(30,7,utf8_decode($eq['id_equipo'] ?? ''),1);
    $pdf->Cell(30,7,utf8_decode($eq['marca'] ?? ''),1);
    $pdf->Cell(30,7,utf8_decode($eq['modelo'] ?? ''),1);
    $pdf->Cell(50,7,utf8_decode($eq['ubicacion'] ?? ''),1);
    $pdf->Cell(30,7,utf8_decode($eq['voltaje'] ?? ''),1);
    $pdf->Ln();
}
$pdf->Ln(10);

// =======================
// Trabajos y Observaciones
// =======================
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,7,'Trabajos realizados',1,1);
$pdf->SetFont('Arial','',9);
$pdf->MultiCell(0,6,utf8_decode($trabajos),1);
$pdf->Ln(5);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,7,'Observaciones y recomendaciones',1,1);
$pdf->SetFont('Arial','',9);
$pdf->MultiCell(0,6,utf8_decode($observaciones),1);
$pdf->Ln(5);

// =======================
// Fotos
// =======================
if($fotos){
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,7,'Fotos',1,1,'C');
    $pdf->Ln(3);
    $x = 10; $y = $pdf->GetY();
    foreach($fotos as $foto){
        $pdf->Image($foto, $x, $y, 60, 45); // cada foto 60x45
        $x += 65;
        if($x > 180){
            $x = 10;
            $y += 50;
        }
    }
    $pdf->Ln(55);
}

// =======================
// Firmas
// =======================
$pdf->SetFont('Arial','B',10);
$pdf->Cell(63,7,'Cliente',1,0,'C');
$pdf->Cell(63,7,'Supervisor',1,0,'C');
$pdf->Cell(63,7,'Tecnico',1,1,'C');

$pdf->Cell(63,30,'',1,0,'C');
$pdf->Cell(63,30,'',1,0,'C');
$pdf->Cell(63,30,'',1,1,'C');

// Insertar firmas si existen (en base64 vienen con "data:image/png;base64,...")
function insertarFirma($pdf, $firma, $x, $y){
    if($firma && strpos($firma, "base64,") !== false){
        $data = explode(',', $firma);
        $img = base64_decode($data[1]);
        $tmp = tempnam(sys_get_temp_dir(), 'sig');
        file_put_contents($tmp, $img);
        $pdf->Image($tmp, $x, $y, 40, 25);
        unlink($tmp);
    }
}
$yFirmas = $pdf->GetY() - 30;
insertarFirma($pdf, $firma_cliente, 20, $yFirmas+2);
insertarFirma($pdf, $firma_supervisor, 85, $yFirmas+2);
insertarFirma($pdf, $firma_tecnico, 150, $yFirmas+2);

// =======================
// Output
// =======================
$pdf->Output("I","Reporte_Mantenimiento_$mantenimiento_id.pdf");
