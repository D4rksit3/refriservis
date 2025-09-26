<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/fpdf.php';

// ---- Recibir datos del form ----
$idMantenimiento = $_POST['mantenimiento_id'] ?? null;
$equipos = $_POST['equipos'] ?? [];
$parametros = $_POST['parametros'] ?? [];
$trabajos = $_POST['trabajos'] ?? '';
$observaciones = $_POST['observaciones'] ?? '';
$firmaCliente = $_POST['firma_cliente'] ?? '';
$firmaSupervisor = $_POST['firma_supervisor'] ?? '';
$firmaTecnico = $_POST['firma_tecnico'] ?? '';

// Traer datos del mantenimiento + cliente
$stmt = $pdo->prepare("SELECT m.*, c.cliente, c.direccion, c.responsable, c.telefono 
                       FROM mantenimientos m 
                       LEFT JOIN clientes c ON c.id = m.cliente_id 
                       WHERE m.id = ?");
$stmt->execute([$idMantenimiento]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);

// ================== PDF ==================
$pdf = new FPDF();
$pdf->AddPage();

// ✅ Fondo (tu imagen de diseño)
$templatePath = __DIR__ . '/../../lib/logo.jpeg'; // cambia a tu ruta real
$pdf->Image($templatePath, 0, 0, 210, 297); // A4

$pdf->SetFont('Arial','',9);

// ----------------- CABECERA -----------------
$pdf->SetXY(25, 38);
$pdf->Cell(100,5,utf8_decode("CLIENTE: " . ($m['cliente'] ?? '-')),0,1);

$pdf->SetXY(25, 44);
$pdf->Cell(100,5,utf8_decode("DIRECCIÓN: " . ($m['direccion'] ?? '-')),0,1);

$pdf->SetXY(25, 50);
$pdf->Cell(100,5,utf8_decode("RESPONSABLE: " . ($m['responsable'] ?? '-')),0,1);

$pdf->SetXY(160, 50);
$pdf->Cell(40,5,utf8_decode("FECHA: " . ($m['fecha'] ?? date('Y-m-d'))),0,1);

// ----------------- TABLA EQUIPOS -----------------
$yEquipos = 90;
foreach($equipos as $i => $eq){
    if(empty($eq['id_equipo'])) continue;
    $pdf->SetXY(20, $yEquipos);
    $pdf->Cell(10,5,$i,1,0,'C');
    $pdf->Cell(30,5,utf8_decode($eq['marca'] ?? ''),1,0);
    $pdf->Cell(30,5,utf8_decode($eq['modelo'] ?? ''),1,0);
    $pdf->Cell(60,5,utf8_decode($eq['ubicacion'] ?? ''),1,0);
    $pdf->Cell(20,5,utf8_decode($eq['voltaje'] ?? ''),1,1);
    $yEquipos += 6;
}

// ----------------- TRABAJOS REALIZADOS -----------------
$pdf->SetXY(20, 190);
$pdf->MultiCell(170,5,utf8_decode("TRABAJOS REALIZADOS:\n".$trabajos));

// ----------------- OBSERVACIONES -----------------
$pdf->SetXY(20, 220);
$pdf->MultiCell(170,5,utf8_decode("OBSERVACIONES:\n".$observaciones));

// ----------------- FIRMAS -----------------
function base64ToImage($base64_string, $output_file) {
    $data = explode(',', $base64_string);
    if(count($data) > 1){
        $ifp = fopen($output_file, "wb");
        fwrite($ifp, base64_decode($data[1]));
        fclose($ifp);
        return $output_file;
    }
    return null;
}

$yFirma = 250;
if($firmaCliente){
    $img = base64ToImage($firmaCliente, __DIR__.'/tmp_firma_cliente.png');
    if($img) $pdf->Image($img, 30, $yFirma, 40, 20);
}
if($firmaSupervisor){
    $img = base64ToImage($firmaSupervisor, __DIR__.'/tmp_firma_super.png');
    if($img) $pdf->Image($img, 90, $yFirma, 40, 20);
}
if($firmaTecnico){
    $img = base64ToImage($firmaTecnico, __DIR__.'/tmp_firma_tecnico.png');
    if($img) $pdf->Image($img, 150, $yFirma, 40, 20);
}

// ----------------- SALIDA -----------------
$pdf->Output('D', 'Reporte_Mantenimiento_'.$idMantenimiento.'.pdf');
exit;
