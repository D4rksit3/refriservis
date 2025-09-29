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

// 🚩 Función para generar PDF (la reuso tanto en POST como en GET)
function generarPDF($pdo, $mantenimiento_id) {
    class PDF extends FPDF {
        function Header() {
            if(file_exists(__DIR__.'/../../lib/logo.jpeg')){
                $this->Image(__DIR__.'/../../lib/logo.jpeg',10,6,30);
            }
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

    $stmt = $pdo->prepare("SELECT m.*, c.cliente, c.direccion, c.responsable, c.telefono 
                           FROM mantenimientos m
                           LEFT JOIN clientes c ON c.id = m.cliente_id
                           WHERE m.id = ?");
    $stmt->execute([$mantenimiento_id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$m){
        echo "No existe el mantenimiento con ID: $mantenimiento_id";
        exit;
    }

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
            $pdf->Cell(0,6,"Equipo $eq → Antes: ".($vals['antes'] ?? '')." | Después: ".($vals['despues'] ?? ''),0,1);
        }
        $pdf->Ln(1);
    }

    // Firmas
    $pdf->Ln(10);
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,6,"Firmas:",0,1);
    $pdf->Ln(5);

    $baseFirmas = __DIR__ . "/../../uploads/firmas/";
    if($m['firma_cliente'] && file_exists($baseFirmas.$m['firma_cliente']))
        $pdf->Image($baseFirmas.$m['firma_cliente'],20,$pdf->GetY(),40,20);
    if($m['firma_supervisor'] && file_exists($baseFirmas.$m['firma_supervisor']))
        $pdf->Image($baseFirmas.$m['firma_supervisor'],80,$pdf->GetY(),40,20);
    if($m['firma_tecnico'] && file_exists($baseFirmas.$m['firma_tecnico']))
        $pdf->Image($baseFirmas.$m['firma_tecnico'],150,$pdf->GetY(),40,20);
    $pdf->Ln(30);

    // Fotos
    $fotos = json_decode($m['fotos'],true) ?? [];
    $baseFotos = __DIR__ . "/../../uploads/fotos/";
    if($fotos){
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(0,6,"Fotos:",0,1);
        foreach($fotos as $foto){
            if(file_exists($baseFotos.$foto)){
                $pdf->Image($baseFotos.$foto, null, null, 60, 40);
                $pdf->Ln(45);
            }
        }
    }

    $pdf->Output("D","Reporte_Mantenimiento_{$mantenimiento_id}.pdf");
    exit;
}

// 🚩 Si viene por POST → guardar datos y generar PDF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... tu bloque actual para guardar (lo dejamos igual) ...
    // Al final:
    generarPDF($pdo, $mantenimiento_id);
}

// 🚩 Si viene por GET → generar PDF directamente
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $mantenimiento_id = intval($_GET['id']);
    generarPDF($pdo, $mantenimiento_id);
}

echo "Accede mediante formulario o con ?id= en GET.";
