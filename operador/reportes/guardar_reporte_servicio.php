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

// 🚩 Función para generar PDF con diseño
function generarPDF($pdo, $mantenimiento_id) {
    class PDF extends FPDF {
        function Header() {
            if(file_exists(__DIR__.'/../../lib/logo.jpeg')){
                $this->Image(__DIR__.'/../../lib/logo.jpeg',10,6,25);
            }
            $this->SetFont('Arial','B',12);
            $this->Cell(0,5,utf8_decode('FORMATO DE CALIDAD'),0,1,'C');
            $this->SetFont('Arial','B',14);
            $this->Cell(0,7,utf8_decode('REPORTE DE SERVICIO TÉCNICO'),0,1,'C');
            $this->Ln(3);
        }
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,utf8_decode('Página ').$this->PageNo().'/{nb}',0,0,'C');
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

    // 📌 Cabecera con datos principales en tabla
    $pdf->SetFillColor(220,230,241);
    $pdf->Cell(100,8,"Cliente: ".utf8_decode($m['cliente']),1,0,'L',true);
    $pdf->Cell(90,8,"Fecha: ".$m['fecha'],1,1,'L',true);
    $pdf->Cell(100,8,"Direccion: ".utf8_decode($m['direccion']),1,0,'L');
    $pdf->Cell(90,8,"Tel: ".$m['telefono'],1,1,'L');
    $pdf->Cell(190,8,"Responsable: ".utf8_decode($m['responsable']),1,1,'L');
    $pdf->Ln(5);

    // 📌 Número de reporte
    $pdf->SetFont('Arial','B',11);
    $pdf->SetFillColor(255,255,204);
    $pdf->Cell(190,10,"N° DE REPORTE: 001-".$mantenimiento_id,1,1,'C',true);
    $pdf->Ln(5);

    // 📌 Trabajos
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,8,"Trabajos Realizados:",0,1);
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,6,utf8_decode($m['trabajos']));
    $pdf->Ln(3);

    // 📌 Observaciones
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,8,"Observaciones:",0,1);
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,6,utf8_decode($m['observaciones']));
    $pdf->Ln(5);

    // 📌 Parámetros
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,8,"Parametros de Funcionamiento:",0,1);
    $pdf->SetFont('Arial','',9);
    $params = json_decode($m['parametros'],true) ?? [];
    foreach($params as $param => $equipos){
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(0,6,utf8_decode($param),0,1);
        foreach($equipos as $eq=>$vals){
            $pdf->SetFont('Arial','',9);
            $pdf->Cell(0,6,"Equipo $eq → Antes: ".($vals['antes'] ?? '')." | Despues: ".($vals['despues'] ?? ''),0,1);
        }
        $pdf->Ln(1);
    }

    // 📌 Firmas
    $pdf->Ln(10);
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,8,"Firmas:",0,1);

    $yFirmas = $pdf->GetY();
    $baseFirmas = __DIR__ . "/../../uploads/firmas/";

    $pdf->Cell(63,30,"Cliente",1,0,'C');
    $pdf->Cell(63,30,"Supervisor",1,0,'C');
    $pdf->Cell(64,30,"Tecnico",1,1,'C');

    if($m['firma_cliente'] && file_exists($baseFirmas.$m['firma_cliente'])){
        $pdf->Image($baseFirmas.$m['firma_cliente'],20,$yFirmas+5,40,20);
    }
    if($m['firma_supervisor'] && file_exists($baseFirmas.$m['firma_supervisor'])){
        $pdf->Image($baseFirmas.$m['firma_supervisor'],83,$yFirmas+5,40,20);
    }
    if($m['firma_tecnico'] && file_exists($baseFirmas.$m['firma_tecnico'])){
        $pdf->Image($baseFirmas.$m['firma_tecnico'],146,$yFirmas+5,40,20);
    }

    $pdf->Ln(40);

    // 📌 Fotos
    $fotos = json_decode($m['fotos'],true) ?? [];
    $baseFotos = __DIR__ . "/../../uploads/fotos/";
    if($fotos){
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(0,8,"Fotos:",0,1);
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

// 🚩 POST → guardar datos + generar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... tu bloque de guardado ...
    generarPDF($pdo, $mantenimiento_id);
}

// 🚩 GET → generar directo
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $mantenimiento_id = intval($_GET['id']);
    generarPDF($pdo, $mantenimiento_id);
}

echo "Accede mediante formulario o con ?id= en GET.";
