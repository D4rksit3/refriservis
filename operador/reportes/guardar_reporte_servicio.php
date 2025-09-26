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

// ==========================
// DATOS DEL FORMULARIO
// ==========================
$mantenimiento_id = $_POST['mantenimiento_id'] ?? null;
$trabajos = $_POST['trabajos'] ?? '';
$observaciones = $_POST['observaciones'] ?? '';
$firma_cliente = $_POST['firma_cliente'] ?? null;
$firma_supervisor = $_POST['firma_supervisor'] ?? null;
$firma_tecnico = $_POST['firma_tecnico'] ?? null;

// ==========================
// SUBIDA DE FOTOS
// ==========================
$fotosGuardadas = [];
$uploadDir = __DIR__ . '/../../uploads/fotos_mantenimientos/' . $mantenimiento_id . '/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!empty($_FILES['fotos']['name'][0])) {
    foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['fotos']['error'][$key] === UPLOAD_ERR_OK) {
            $nombre = time() . "_" . basename($_FILES['fotos']['name'][$key]);
            $destino = $uploadDir . $nombre;
            if (move_uploaded_file($tmp_name, $destino)) {
                $fotosGuardadas[] = $destino;
            }
        }
    }
}

// ==========================
// GUARDAR EN BASE DE DATOS
// ==========================
$stmt = $pdo->prepare("
    INSERT INTO reportes_servicio
    (mantenimiento_id, trabajos, observaciones, firma_cliente, firma_supervisor, firma_tecnico, fecha)
    VALUES (?,?,?,?,?,?,NOW())
");
$stmt->execute([
    $mantenimiento_id,
    $trabajos,
    $observaciones,
    $firma_cliente,
    $firma_supervisor,
    $firma_tecnico
]);
$reporte_id = $pdo->lastInsertId();

// ==========================
// CREAR PDF
// ==========================
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,'REPORTE DE SERVICIO TECNICO',0,1,'C');

$pdf->SetFont('Arial','',10);
$pdf->Cell(0,8,"Mantenimiento Nro: $mantenimiento_id",0,1);

$pdf->MultiCell(0,6,"Trabajos Realizados:\n".$trabajos);
$pdf->Ln(2);
$pdf->MultiCell(0,6,"Observaciones:\n".$observaciones);

// ==========================
// FOTOS EN EL PDF
// ==========================
if (!empty($fotosGuardadas)) {
    $pdf->Ln(5);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8,'Fotos de los equipos:',0,1);

    foreach ($fotosGuardadas as $foto) {
        $pdf->Image($foto, null, null, 80, 60); // ancho=80mm alto=60mm
        $pdf->Ln(65);
    }
}

// ==========================
// FIRMAS EN EL PDF
// ==========================
$pdf->Ln(10);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,'Firmas:',0,1);

if ($firma_cliente) {
    $imgData = base64_decode(str_replace('data:image/png;base64,','',$firma_cliente));
    $firmaPath = $uploadDir."firma_cliente.png";
    file_put_contents($firmaPath,$imgData);
    $pdf->Image($firmaPath,20,$pdf->GetY(),40,20);
}
if ($firma_supervisor) {
    $imgData = base64_decode(str_replace('data:image/png;base64,','',$firma_supervisor));
    $firmaPath = $uploadDir."firma_supervisor.png";
    file_put_contents($firmaPath,$imgData);
    $pdf->Image($firmaPath,80,$pdf->GetY(),40,20);
}
if ($firma_tecnico) {
    $imgData = base64_decode(str_replace('data:image/png;base64,','',$firma_tecnico));
    $firmaPath = $uploadDir."firma_tecnico.png";
    file_put_contents($firmaPath,$imgData);
    $pdf->Image($firmaPath,150,$pdf->GetY(),40,20);
}

$pdfFile = $uploadDir."reporte_$reporte_id.pdf";
$pdf->Output('F',$pdfFile);

// ==========================
// REDIRIGIR A DESCARGA
// ==========================
header("Location: descargar_reporte.php?id=$reporte_id");
exit;
