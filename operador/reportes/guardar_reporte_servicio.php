<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header('Location: /../index.php');
    exit;
}

require_once __DIR__ . '../../../config/db.php';
require_once __DIR__ . '../../../lib/fpdf.php';

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// --- Función helper para evitar utf8_decode ---
function txt($s) {
    return mb_convert_encoding($s ?? '', 'ISO-8859-1', 'UTF-8');
}

// --- Recibir datos del formulario ---
$id_mantenimiento = $_POST['mantenimiento_id'] ?? null;
$equipos = $_POST['equipos'] ?? [];
$parametros = $_POST['parametros'] ?? [];
$trabajos = $_POST['trabajos'] ?? '';
$observaciones = $_POST['observaciones'] ?? '';
$firma_cliente = $_POST['firma_cliente'] ?? null;
$firma_supervisor = $_POST['firma_supervisor'] ?? null;
$firma_tecnico = $_POST['firma_tecnico'] ?? null;

// Traer datos del mantenimiento + cliente
$stmt = $pdo->prepare("
  SELECT m.*, c.cliente, c.direccion, c.responsable
  FROM mantenimientos m
  LEFT JOIN clientes c ON c.id = m.cliente_id
  WHERE m.id = ?
");
$stmt->execute([$id_mantenimiento]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$m) {
    die("Mantenimiento no encontrado");
}

// Instanciar PDF
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial','B',10);

// --- CABECERA ---
$pdf->Image(__DIR__.'/../../assets/logo.png',10,6,40); // Ajusta ruta/logo
$pdf->Cell(120);
$pdf->Cell(70,10,txt("REFRISERVIS S.A.C."),0,1,'R');
$pdf->SetFont('Arial','',8);
$pdf->Cell(120);
$pdf->Cell(70,5,txt("Oficina: (01) 6557907"),0,1,'R');
$pdf->Cell(120);
$pdf->Cell(70,5,txt("Emergencias: +51 943 048 606"),0,1,'R');
$pdf->Cell(120);
$pdf->Cell(70,5,txt("ventas@refriservissac.com"),0,1,'R');

$pdf->Ln(5);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,7,txt("REPORTE DE SERVICIO TÉCNICO"),0,1,'C');

// --- DATOS DEL CLIENTE ---
$pdf->Ln(3);
$pdf->SetFont('Arial','B',9);
$pdf->Cell(40,7,txt("CLIENTE:"),1);
$pdf->SetFont('Arial','',9);
$pdf->Cell(150,7,txt($m['cliente']),1,1);

$pdf->SetFont('Arial','B',9);
$pdf->Cell(40,7,txt("DIRECCIÓN:"),1);
$pdf->SetFont('Arial','',9);
$pdf->Cell(150,7,txt($m['direccion']),1,1);

$pdf->SetFont('Arial','B',9);
$pdf->Cell(40,7,txt("RESPONSABLE:"),1);
$pdf->SetFont('Arial','',9);
$pdf->Cell(150,7,txt($m['responsable']),1,1);

$pdf->SetFont('Arial','B',9);
$pdf->Cell(40,7,txt("FECHA:"),1);
$pdf->SetFont('Arial','',9);
$pdf->Cell(150,7,txt($m['fecha']),1,1);

$pdf->Ln(4);

// --- TABLA EQUIPOS ---
$pdf->SetFont('Arial','B',9);
$pdf->Cell(0,7,txt("DATOS DE IDENTIFICACIÓN DE LOS EQUIPOS A INTERVENIR"),1,1,'C');

$headers = ["#", "Identificador", "Marca", "Modelo", "Ubicación", "Voltaje"];
$w = [10, 40, 30, 30, 40, 40];

$pdf->SetFont('Arial','B',8);
for($i=0;$i<count($headers);$i++) {
    $pdf->Cell($w[$i],6,txt($headers[$i]),1,0,'C');
}
$pdf->Ln();

$pdf->SetFont('Arial','',8);
for($i=1;$i<=7;$i++) {
    $eq = $equipos[$i] ?? [];
    $pdf->Cell($w[0],6,$i,1);
    $pdf->Cell($w[1],6,txt($eq['id_equipo'] ?? ''),1);
    $pdf->Cell($w[2],6,txt($eq['marca'] ?? ''),1);
    $pdf->Cell($w[3],6,txt($eq['modelo'] ?? ''),1);
    $pdf->Cell($w[4],6,txt($eq['ubicacion'] ?? ''),1);
    $pdf->Cell($w[5],6,txt($eq['voltaje'] ?? ''),1);
    $pdf->Ln();
}
$pdf->Ln(4);

// --- TABLA PARÁMETROS ---
$pdf->SetFont('Arial','B',9);
$pdf->Cell(0,7,txt("PARÁMETROS DE FUNCIONAMIENTO"),1,1,'C');

$parametrosLabels = [
    'Corriente eléctrica nominal L1',
    'Corriente L2',
    'Corriente L3',
    'Tensión eléctrica V1',
    'Tensión V2',
    'Tensión V3',
    'Presión de descarga (PSI)',
    'Presión de succión (PSI)'
];

$pdf->SetFont('Arial','B',7);
$pdf->Cell(40,6,txt("Medida"),1);
for($i=1;$i<=7;$i++) {
    $pdf->Cell(20,6,txt("Eq".$i." A"),1,0,'C');
    $pdf->Cell(20,6,txt("Eq".$i." D"),1,0,'C');
}
$pdf->Ln();

$pdf->SetFont('Arial','',7);
foreach($parametrosLabels as $p) {
    $pdf->Cell(40,6,txt($p),1);
    for($i=1;$i<=7;$i++) {
        $antes = $parametros[md5($p)][$i]['antes'] ?? '';
        $despues = $parametros[md5($p)][$i]['despues'] ?? '';
        $pdf->Cell(20,6,txt($antes),1,0,'C');
        $pdf->Cell(20,6,txt($despues),1,0,'C');
    }
    $pdf->Ln();
}
$pdf->Ln(4);

// --- TRABAJOS ---
$pdf->SetFont('Arial','B',9);
$pdf->Cell(0,7,txt("TRABAJOS REALIZADOS"),1,1,'L');
$pdf->SetFont('Arial','',9);
$pdf->MultiCell(0,6,txt($trabajos),1);
$pdf->Ln(3);

// --- OBSERVACIONES ---
$pdf->SetFont('Arial','B',9);
$pdf->Cell(0,7,txt("OBSERVACIONES Y RECOMENDACIONES"),1,1,'L');
$pdf->SetFont('Arial','',9);
$pdf->MultiCell(0,6,txt($observaciones),1);
$pdf->Ln(3);

// --- FOTOS ---
if (!empty($_FILES['fotos']['tmp_name'][0])) {
    $pdf->SetFont('Arial','B',9);
    $pdf->Cell(0,7,txt("FOTOS DE LOS EQUIPOS"),1,1,'L');
    $pdf->Ln(2);
    foreach($_FILES['fotos']['tmp_name'] as $k=>$tmp) {
        if (is_uploaded_file($tmp)) {
            $dest = sys_get_temp_dir()."/foto_$k.jpg";
            move_uploaded_file($tmp,$dest);
            $pdf->Image($dest, null, null, 60, 45);
            if(($k+1)%3==0) $pdf->Ln(50);
            else $pdf->Cell(65,50,'',0,0);
        }
    }
    $pdf->Ln(10);
}

// --- FIRMAS ---
$pdf->SetFont('Arial','B',9);
$pdf->Cell(0,7,txt("FIRMAS"),1,1,'L');
$pdf->Ln(5);

$y = $pdf->GetY();
$x = $pdf->GetX();

if ($firma_cliente) {
    $data = str_replace('data:image/png;base64,','',$firma_cliente);
    $file = sys_get_temp_dir()."/firma_cliente.png";
    file_put_contents($file,base64_decode($data));
    $pdf->Image($file,$x+10,$y,40,20);
}
$pdf->Cell(60,25,txt("Cliente"),1,0,'C');

if ($firma_supervisor) {
    $data = str_replace('data:image/png;base64,','',$firma_supervisor);
    $file = sys_get_temp_dir()."/firma_supervisor.png";
    file_put_contents($file,base64_decode($data));
    $pdf->Image($file,$x+70,$y,40,20);
}
$pdf->Cell(60,25,txt("Supervisor"),1,0,'C');

if ($firma_tecnico) {
    $data = str_replace('data:image/png;base64,','',$firma_tecnico);
    $file = sys_get_temp_dir()."/firma_tecnico.png";
    file_put_contents($file,base64_decode($data));
    $pdf->Image($file,$x+130,$y,40,20);
}
$pdf->Cell(60,25,txt("Técnico"),1,1,'C');

// --- SALIDA ---
$pdf->Output('I', "Reporte_Mantenimiento_$id_mantenimiento.pdf");
