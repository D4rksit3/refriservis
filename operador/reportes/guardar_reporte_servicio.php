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
require_once __DIR__ . '/../../lib/fpdf/fpdf.php'; // 🚩 usa tu librería fpdf

// 🚩 Función para guardar firma en disco
function saveSignature($dataUrl, $name) {
    if (!$dataUrl) return null;
    $data = explode(',', $dataUrl);
    if (count($data) !== 2) return null;
    $decoded = base64_decode($data[1]);
    $dir = __DIR__ . "/../../uploads/firmas/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $file = $dir . "{$name}_" . time() . ".png";
    file_put_contents($file, $decoded);
    return $file; // ruta completa
}

// 🚩 Si viene un POST → Guardar datos y generar PDF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mantenimiento_id = $_POST['mantenimiento_id'];
    $trabajos = $_POST['trabajos'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';

    // Guardar firmas
    $firma_cliente = saveSignature($_POST['firma_cliente'] ?? '', 'cliente');
    $firma_supervisor = saveSignature($_POST['firma_supervisor'] ?? '', 'supervisor');
    $firma_tecnico = saveSignature($_POST['firma_tecnico'] ?? '', 'tecnico');

    // Guardar fotos
    $fotos_guardadas = [];
    if (!empty($_FILES['fotos']['name'][0])) {
        $dir = __DIR__ . "/../../uploads/fotos/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        foreach ($_FILES['fotos']['tmp_name'] as $k => $tmp) {
            if (is_uploaded_file($tmp)) {
                $nombre = time() . "_" . $_FILES['fotos']['name'][$k];
                move_uploaded_file($tmp, $dir . $nombre);
                $fotos_guardadas[] = $dir . $nombre;
            }
        }
    }

    // Guardar parámetros en JSON
    $parametros = $_POST['parametros'] ?? [];
    $parametros_json = json_encode($parametros);

    // Actualizar en la tabla mantenimientos
    $stmt = $pdo->prepare("UPDATE mantenimientos SET 
        trabajos = ?, 
        observaciones = ?, 
        parametros = ?, 
        firma_cliente = ?, 
        firma_supervisor = ?, 
        firma_tecnico = ?, 
        fotos = ?, 
        reporte_generado = 1,
        modificado_en = NOW(),
        modificado_por = ?
        WHERE id = ?");
    $stmt->execute([
        $trabajos,
        $observaciones,
        $parametros_json,
        $firma_cliente,
        $firma_supervisor,
        $firma_tecnico,
        json_encode($fotos_guardadas),
        $_SESSION['usuario_id'], // 🚩 guarda el usuario que generó
        $mantenimiento_id
    ]);

    // ============================
    // GENERAR PDF
    // ============================
    class PDF extends FPDF {
        function Header() {
            $this->Image(__DIR__.'/../../lib/logo.jpeg',10,6,30);
            $this->SetFont('Arial','B',14);
            $this->Cell(80);
            $this->Cell(30,10,'Reporte de Servicio Técnico',0,0,'C');
            $this->Ln(20);
        }
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,'Página '.$this->PageNo().'/{nb}',0,0,'C');
        }
    }

    // Datos del mantenimiento
    $stmt = $pdo->prepare("SELECT m.*, c.cliente, c.direccion, c.responsable, c.telefono 
                           FROM mantenimientos m
                           LEFT JOIN clientes c ON c.id = m.cliente_id
                           WHERE m.id = ?");
    $stmt->execute([$mantenimiento_id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial','',10);

    // Cabecera cliente
    $pdf->Cell(0,6,"Cliente: ".$m['cliente'],0,1);
    $pdf->Cell(0,6,"Direccion: ".$m['direccion'],0,1);
    $pdf->Cell(0,6,"Responsable: ".$m['responsable'],0,1);
    $pdf->Cell(0,6,"Fecha: ".$m['fecha'],0,1);
    $pdf->Ln(5);

    // Trabajos
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,6,"Trabajos Realizados:",0,1);
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,6,$m['trabajos']);
    $pdf->Ln(3);

    // Observaciones
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,6,"Observaciones:",0,1);
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,6,$m['observaciones']);
    $pdf->Ln(5);

    // Parámetros
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,6,"Parametros de Funcionamiento:",0,1);
    $pdf->SetFont('Arial','',9);
    $params = json_decode($m['parametros'],true) ?? [];
    foreach($params as $param => $equipos){
        $pdf->Cell(0,6, $param,0,1);
        foreach($equipos as $eq=>$vals){
            $pdf->Cell(0,6,"Equipo $eq → Antes: ".$vals['antes']." | Después: ".$vals['despues'],0,1);
        }
        $pdf->Ln(1);
    }

    // Firmas
    $pdf->Ln(10);
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,6,"Firmas:",0,1);
    $pdf->Ln(5);

    if($m['firma_cliente']) $pdf->Image($m['firma_cliente'],20,$pdf->GetY(),40,20);
    if($m['firma_supervisor']) $pdf->Image($m['firma_supervisor'],80,$pdf->GetY(),40,20);
    if($m['firma_tecnico']) $pdf->Image($m['firma_tecnico'],150,$pdf->GetY(),40,20);
    $pdf->Ln(30);

    // Fotos
    $fotos = json_decode($m['fotos'],true) ?? [];
    if($fotos){
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(0,6,"Fotos:",0,1);
        foreach($fotos as $foto){
            if(file_exists($foto)){
                $pdf->Image($foto, null, null, 60, 40);
                $pdf->Ln(45);
            }
        }
    }

    // Descargar
    $pdf->Output("D","Reporte_Mantenimiento_{$mantenimiento_id}.pdf");
    exit;
}

// 🚩 Si llega aquí por error
die("Acceso inválido.");
