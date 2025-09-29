<?php
require_once __DIR__ . '/../../lib/fpdf.php';
require_once __DIR__ . '/../../config/db.php';

$id = $_GET['id'] ?? null;
if(!$id) die("ID no proporcionado");

// =======================
// CONSULTA PRINCIPAL
// =======================
$stmt = $pdo->prepare("
  SELECT m.*, c.cliente, c.direccion, c.responsable, c.telefono
  FROM mantenimientos m
  LEFT JOIN clientes c ON c.id = m.cliente_id
  WHERE m.id = ?
");
$stmt->execute([$id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$m) die("Mantenimiento no encontrado");

// =======================
// CONSULTA EQUIPOS
// =======================
$equipos = [];
for($i=1;$i<=7;$i++){
    if(!empty($m["equipo$i"])){
        $stmtEq = $pdo->prepare("SELECT * FROM equipos WHERE id_equipo=?");
        $stmtEq->execute([$m["equipo$i"]]);
        $equipos[$i] = $stmtEq->fetch(PDO::FETCH_ASSOC);
    } else {
        $equipos[$i] = null;
    }
}

// =======================
// PARÁMETROS
// =======================
$parametrosGuardados = json_decode($m['parametros'] ?? "{}", true);

// =======================
// FUNCIONES
// =======================
function txt($s){ return mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8'); }

class PDF extends FPDF {
    function Header(){
        global $id;

        // Logo
        $this->Cell(40,25,$this->Image(__DIR__.'/../../lib/logo.jpeg',12,12,20),1,0,'C');

        // Título
        $this->SetFont('Arial','B',10);
        $this->Cell(110,7,txt("FORMATO DE CALIDAD"),1,0,'C',true);
        $this->Ln(7);
        $this->SetX(50);
        $this->SetFont('Arial','B',11);
        $this->Cell(110,10,txt("REPORTE DE SERVICIO TÉCNICO"),1,0,'C');
        $this->Ln(10);
        $this->SetX(50);
        $this->SetFont('Arial','',8);
        $this->Cell(110,8,txt("Oficina: (01) 6557907  |  Emergencias: +51 943 048 606  |  ventas@refriservissac.com"),1,0,'C');

        // Número de reporte
        $this->SetXY(160,12);
        $this->SetFont('Arial','',9);
        $this->Cell(40,25,"001-N°".str_pad($id,6,"0",STR_PAD_LEFT),1,1,'C');
        $this->Ln(5);
    }
}

// =======================
// GENERAR PDF
// =======================
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',9);

// ===== CLIENTE =====
$pdf->Cell(0,7,txt("Datos del Cliente"),1,1,'C');
$pdf->Cell(40,7,txt("Cliente:"),1,0);
$pdf->Cell(150,7,txt($m['cliente']),1,1);
$pdf->Cell(40,7,txt("Dirección:"),1,0);
$pdf->Cell(150,7,txt($m['direccion']),1,1);
$pdf->Cell(40,7,txt("Responsable:"),1,0);
$pdf->Cell(70,7,txt($m['responsable']),1,0);
$pdf->Cell(40,7,txt("Teléfono:"),1,0);
$pdf->Cell(40,7,txt($m['telefono']),1,1);
$pdf->Ln(4);

// ===== EQUIPOS =====
$pdf->Cell(0,7,txt("Datos de Identificación de los Equipos"),1,1,'C');
$pdf->SetFont('Arial','B',8);
$pdf->Cell(10,7,"#",1,0,'C');
$pdf->Cell(35,7,txt("Identificador"),1,0,'C');
$pdf->Cell(35,7,txt("Marca"),1,0,'C');
$pdf->Cell(35,7,txt("Modelo"),1,0,'C');
$pdf->Cell(35,7,txt("Ubicación"),1,0,'C');
$pdf->Cell(40,7,txt("Voltaje"),1,1,'C');
$pdf->SetFont('Arial','',8);

for($i=1;$i<=7;$i++){
    $eq = $equipos[$i];
    $pdf->Cell(10,7,$i,1,0,'C');
    $pdf->Cell(35,7,txt($eq['Identificador'] ?? ""),1,0);
    $pdf->Cell(35,7,txt($eq['Marca'] ?? ""),1,0);
    $pdf->Cell(35,7,txt($eq['Modelo'] ?? ""),1,0);
    $pdf->Cell(35,7,txt($eq['Ubicacion'] ?? ""),1,0);
    $pdf->Cell(40,7,txt($eq['Voltaje'] ?? ""),1,1);
}
$pdf->Ln(4);

// ===== PARÁMETROS =====
$pdf->Cell(0,7,txt("Parámetros de Funcionamiento (Antes / Después)"),1,1,'C');
$parametros = [
  'Corriente eléctrica L1','Corriente L2','Corriente L3',
  'Tensión eléctrica V1','Tensión V2','Tensión V3',
  'Presión descarga (PSI)','Presión succión (PSI)'
];
$pdf->SetFont('Arial','B',7);
$pdf->Cell(40,7,"Medida",1,0,'C');
for($i=1;$i<=7;$i++){
    $pdf->Cell(14,7,"Eq$i A",1,0,'C');
    $pdf->Cell(14,7,"Eq$i D",1,0,'C');
}
$pdf->Ln();
$pdf->SetFont('Arial','',7);
foreach($parametros as $p){
    $pdf->Cell(40,7,txt($p),1,0);
    for($i=1;$i<=7;$i++){
        $antes = $parametrosGuardados[md5($p)][$i]['antes'] ?? '';
        $despues = $parametrosGuardados[md5($p)][$i]['despues'] ?? '';
        $pdf->Cell(14,7,txt($antes),1,0,'C');
        $pdf->Cell(14,7,txt($despues),1,0,'C');
    }
    $pdf->Ln();
}
$pdf->Ln(4);

// ===== TRABAJOS =====
$pdf->MultiCell(0,7,txt("Trabajos Realizados:\n".$m['trabajos']),1);
$pdf->Ln(2);

// ===== OBSERVACIONES =====
$pdf->MultiCell(0,7,txt("Observaciones y Recomendaciones:\n".$m['observaciones']),1);
$pdf->Ln(2);

// ===== FOTOS =====
$fotos = json_decode($m['fotos'] ?? "[]", true);
if($fotos){
    $pdf->Cell(0,7,txt("Fotos de Equipos"),1,1,'C');
    foreach($fotos as $f){
        $ruta = __DIR__."/../../uploads/fotos/".$f;
        if(file_exists($ruta)){
            $pdf->Image($ruta, null, null, 60, 40);
            $pdf->Ln(42);
        }
    }
}

// ===== FIRMAS =====
$pdf->Cell(63,20,"Firma Cliente",1,0,'C');
$pdf->Cell(63,20,"Firma Supervisor",1,0,'C');
$pdf->Cell(64,20,"Firma Técnico",1,1,'C');

if($m['firma_cliente']){
    $pdf->Image(__DIR__.'/../../uploads/firmas/'.$m['firma_cliente'], 15, $pdf->GetY()-20, 40, 20);
}
if($m['firma_supervisor']){
    $pdf->Image(__DIR__.'/../../uploads/firmas/'.$m['firma_supervisor'], 80, $pdf->GetY()-20, 40, 20);
}
if($m['firma_tecnico']){
    $pdf->Image(__DIR__.'/../../uploads/firmas/'.$m['firma_tecnico'], 145, $pdf->GetY()-20, 40, 20);
}

$pdf->Output("I","reporte_$id.pdf");
