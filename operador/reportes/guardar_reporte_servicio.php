<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header('Location: /index.php');
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/fpdf.php';

function generarPDF($pdo, $mantenimiento_id) {
    class PDF extends FPDF {
        function Header() {
            if(file_exists(__DIR__.'/../../lib/logo.jpeg')){
                $this->Image(__DIR__.'/../../lib/logo.jpeg',10,6,25);
            }
            $this->SetFont('Arial','B',14);
            $this->Cell(0,10,'Reporte de Servicio Técnico',0,1,'C');
            $this->Ln(3);
        }
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,'Página '.$this->PageNo().'/{nb}',0,0,'C');
        }
    }

    $stmt = $pdo->prepare("SELECT m.*, c.cliente, c.direccion, c.responsable, c.telefono 
                           FROM mantenimientos m
                           LEFT JOIN clientes c ON c.id = m.cliente_id
                           WHERE m.id = ?");
    $stmt->execute([$mantenimiento_id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$m){
        header("HTTP/1.1 404 Not Found");
        exit("No existe el mantenimiento con ID: $mantenimiento_id");
    }

    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial','',10);

    // 📌 Info Cliente
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,7,"Datos del Cliente",1,1,'C');
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(95,7,"Cliente: ".$m['cliente'],1,0);
    $pdf->Cell(95,7,"Responsable: ".$m['responsable'],1,1);
    $pdf->Cell(95,7,"Direccion: ".$m['direccion'],1,0);
    $pdf->Cell(95,7,"Telefono: ".$m['telefono'],1,1);
    $pdf->Cell(95,7,"Fecha: ".$m['fecha'],1,0);
    $pdf->Cell(95,7,"ID Mantenimiento: ".$mantenimiento_id,1,1);
    $pdf->Ln(5);

    // 📌 Trabajos
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,7,"Trabajos Realizados",1,1,'C');
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,7,$m['trabajos'],1);
    $pdf->Ln(3);

    // 📌 Observaciones
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,7,"Observaciones",1,1,'C');
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,7,$m['observaciones'],1);
    $pdf->Ln(5);

    // 📌 Parámetros
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,7,"Parámetros de Funcionamiento",1,1,'C');
    $pdf->SetFont('Arial','',9);

    $params = json_decode($m['parametros'],true) ?? [];
    if($params){
        foreach($params as $param => $equipos){
            $pdf->Cell(0,7,"➤ ".$param,1,1,'L');
            foreach($equipos as $eq=>$vals){
                $line = "Equipo $eq | Antes: ".($vals['antes'] ?? '')." | Después: ".($vals['despues'] ?? '');
                $pdf->Cell(0,7,$line,1,1);
            }
        }
    } else {
        $pdf->Cell(0,7,"Sin parámetros registrados",1,1,'C');
    }
    $pdf->Ln(5);

    // 📌 Firmas
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,7,"Firmas",1,1,'C');
    $pdf->Ln(3);

    $baseFirmas = __DIR__ . "/../../uploads/firmas/";
    $yStart = $pdf->GetY();

    if($m['firma_cliente'] && file_exists($baseFirmas.$m['firma_cliente']))
        $pdf->Image($baseFirmas.$m['firma_cliente'],20,$yStart,40,20);
    $pdf->SetXY(20,$yStart+22);
    $pdf->Cell(40,7,"Cliente",1,0,'C');

    if($m['firma_supervisor'] && file_exists($baseFirmas.$m['firma_supervisor']))
        $pdf->Image($baseFirmas.$m['firma_supervisor'],85,$yStart,40,20);
    $pdf->SetXY(85,$yStart+22);
    $pdf->Cell(40,7,"Supervisor",1,0,'C');

    if($m['firma_tecnico'] && file_exists($baseFirmas.$m['firma_tecnico']))
        $pdf->Image($baseFirmas.$m['firma_tecnico'],150,$yStart,40,20);
    $pdf->SetXY(150,$yStart+22);
    $pdf->Cell(40,7,"Técnico",1,0,'C');

    $pdf->Ln(35);

    // 📌 Fotos
    $fotos = json_decode($m['fotos'],true) ?? [];
    $baseFotos = __DIR__ . "/../../uploads/fotos/";
    if($fotos){
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(0,7,"Fotos del Servicio",1,1,'C');
        foreach($fotos as $foto){
            if(file_exists($baseFotos.$foto)){
                $pdf->Image($baseFotos.$foto, $pdf->GetX()+10, $pdf->GetY()+5, 80, 60);
                $pdf->Ln(65);
            }
        }
    }

    ob_end_clean(); // 🔥 Limpia cualquier salida previa
    $pdf->Output("D","Reporte_Mantenimiento_{$mantenimiento_id}.pdf");
    exit;
}

// 🚩 POST (guardar y generar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // tu lógica de guardado...
    generarPDF($pdo, $mantenimiento_id);
}

// 🚩 GET (descargar PDF directo)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $mantenimiento_id = intval($_GET['id']);
    generarPDF($pdo, $mantenimiento_id);
}
