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
            $this->Cell(0,10,utf8_decode('Reporte de Servicio Técnico'),0,1,'C');
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

    // 📌 Info Cliente en cuadro
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,7,utf8_decode("Datos del Cliente"),1,1,'C');
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(95,7,utf8_decode("Cliente: ".$m['cliente']),1,0);
    $pdf->Cell(95,7,utf8_decode("Responsable: ".$m['responsable']),1,1);
    $pdf->Cell(95,7,utf8_decode("Dirección: ".$m['direccion']),1,0);
    $pdf->Cell(95,7,utf8_decode("Teléfono: ".$m['telefono']),1,1);
    $pdf->Cell(95,7,utf8_decode("Fecha: ".$m['fecha']),1,0);
    $pdf->Cell(95,7,utf8_decode("ID Mantenimiento: ".$mantenimiento_id),1,1);
    $pdf->Ln(5);

    // 📌 Trabajos
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,7,utf8_decode("Trabajos Realizados"),1,1,'C');
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,7,utf8_decode($m['trabajos']),1);
    $pdf->Ln(3);

    // 📌 Observaciones
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,7,utf8_decode("Observaciones"),1,1,'C');
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,7,utf8_decode($m['observaciones']),1);
    $pdf->Ln(5);

    // 📌 Parámetros
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,7,utf8_decode("Parámetros de Funcionamiento"),1,1,'C');
    $pdf->SetFont('Arial','',9);

    $params = json_decode($m['parametros'],true) ?? [];
    if($params){
        foreach($params as $param => $equipos){
            $pdf->Cell(0,7,utf8_decode("➤ ".$param),1,1,'L');
            foreach($equipos as $eq=>$vals){
                $line = "Equipo $eq | Antes: ".($vals['antes'] ?? '')." | Después: ".($vals['despues'] ?? '');
                $pdf->Cell(0,7,utf8_decode($line),1,1);
            }
        }
    } else {
        $pdf->Cell(0,7,utf8_decode("Sin parámetros registrados"),1,1,'C');
    }
    $pdf->Ln(5);

    // 📌 Firmas
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(0,7,utf8_decode("Firmas"),1,1,'C');
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
        $pdf->Cell(0,7,utf8_decode("Fotos del Servicio"),1,1,'C');
        foreach($fotos as $foto){
            if(file_exists($baseFotos.$foto)){
                $pdf->Image($baseFotos.$foto, $pdf->GetX()+10, $pdf->GetY()+5, 80, 60);
                $pdf->Ln(65);
            }
        }
    }

    $pdf->Output("D","Reporte_Mantenimiento_{$mantenimiento_id}.pdf");
    exit;
}

// 🚩 Si viene por POST → guardar datos y generar PDF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tu lógica de guardado aquí...
    generarPDF($pdo, $mantenimiento_id);
}

// 🚩 Si viene por GET → generar PDF directamente
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $mantenimiento_id = intval($_GET['id']);
    generarPDF($pdo, $mantenimiento_id);
}

echo "Accede mediante formulario o con ?id= en GET.";
