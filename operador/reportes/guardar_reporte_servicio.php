<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/fpdf.php';

function generarPDF($id, $pdo) {
    // Obtener mantenimiento
    $stmt = $pdo->prepare("SELECT * FROM mantenimientos WHERE id = ?");
    $stmt->execute([$id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$m) throw new Exception("No se encontró el mantenimiento");

    // Obtener equipos relacionados
    $equiposIds = [];
    for ($i=1; $i<=7; $i++) {
        if (!empty($m["equipo$i"])) $equiposIds[] = $m["equipo$i"];
    }

    $equiposData = [];
    if ($equiposIds) {
        $in = str_repeat('?,', count($equiposIds)-1) . '?';
        $stmt = $pdo->prepare("
            SELECT e.Identificador, e.marca, e.modelo, e.ubicacion, inv.nombre AS tipo, inv.gas
            FROM equipos e
            LEFT JOIN inventario inv ON e.Identificador = inv.codigo
            WHERE e.Identificador IN ($in)
        ");
        $stmt->execute($equiposIds);
        $equiposData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Decodificar parámetros
    $parametros = json_decode($m['parametros'], true) ?: [];

    // Decodificar fotos
    $fotos = json_decode($m['fotos'], true) ?: [];

    // Crear PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);

    // Logo
    if(file_exists(__DIR__ . '/../../lib/logo.jpeg')){
        $pdf->Image(__DIR__ . '/../../lib/logo.jpeg',10,10,30);
    }
    $pdf->Cell(0,10,utf8_decode("REPORTE DE MANTENIMIENTO"),0,1,'C');
    $pdf->Ln(10);

    // --- Datos de Identificación de los Equipos ---
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8,"Datos de Identificación de los Equipos",1,1,'C');
    $pdf->SetFont('Arial','B',9);
    $pdf->Cell(30,8,"ID",1);
    $pdf->Cell(40,8,"Tipo",1);
    $pdf->Cell(30,8,"Marca",1);
    $pdf->Cell(30,8,"Modelo",1);
    $pdf->Cell(30,8,"Ubicación",1);
    $pdf->Cell(30,8,"Gas",1);
    $pdf->Ln();

    $pdf->SetFont('Arial','',9);
    foreach($equiposData as $eq){
        $pdf->Cell(30,8,$eq['Identificador'],1);
        $pdf->Cell(40,8,utf8_decode($eq['tipo']),1);
        $pdf->Cell(30,8,$eq['marca'],1);
        $pdf->Cell(30,8,$eq['modelo'],1);
        $pdf->Cell(30,8,$eq['ubicacion'],1);
        $pdf->Cell(30,8,$eq['gas'],1);
        $pdf->Ln();
    }
    $pdf->Ln(5);

    // --- Parámetros de Funcionamiento ---
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8,"Parámetros de Funcionamiento (Antes / Después)",1,1,'C');

    $pdf->SetFont('Arial','B',9);
    $pdf->Cell(50,8,"Parámetro",1);
    $pdf->Cell(40,8,"Equipo",1);
    $pdf->Cell(40,8,"Antes",1);
    $pdf->Cell(40,8,"Después",1);
    $pdf->Ln();

    $pdf->SetFont('Arial','',9);
    foreach($parametros as $nombre=>$data){
        foreach($data as $equipo=>$valores){
            $pdf->Cell(50,8,utf8_decode($nombre),1);
            $pdf->Cell(40,8,$equipo,1);
            $pdf->Cell(40,8,$valores['antes'] ?? '',1);
            $pdf->Cell(40,8,$valores['despues'] ?? '',1);
            $pdf->Ln();
        }
    }
    $pdf->Ln(5);

    // --- Trabajos Realizados ---
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8,"Trabajos Realizados",1,1,'C');
    $pdf->SetFont('Arial','',9);
    $pdf->MultiCell(0,6,utf8_decode($m['trabajos']),1);
    $pdf->Ln(5);

    // --- Observaciones ---
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8,"Observaciones y Recomendaciones",1,1,'C');
    $pdf->SetFont('Arial','',9);
    $pdf->MultiCell(0,6,utf8_decode($m['observaciones']),1);
    $pdf->Ln(5);

    // --- Fotos ---
    if($fotos){
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,8,"Registro Fotográfico",1,1,'C');
        $pdf->Ln(3);

        $x = 10; $y = $pdf->GetY();
        $count = 0;
        foreach($fotos as $foto){
            $path = __DIR__ . "/../../uploads/fotos/".$foto;
            if(file_exists($path)){
                $pdf->Image($path,$x,$y,60,45);
                $x += 65;
                $count++;
                if($count % 3 == 0){ $x=10; $y += 50; }
            }
        }
        $pdf->Ln(60);
    }

    // --- Firmas ---
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8,"Firmas",1,1,'C');
    $pdf->Ln(10);

    $y = $pdf->GetY();
    $pdf->SetFont('Arial','',9);

    // Cliente
    if(!empty($m['firma_cliente'])){
        $path = __DIR__ . "/../../uploads/firmas/".$m['firma_cliente'];
        if(file_exists($path)){
            $pdf->Image($path,20,$y,40,25);
            $pdf->SetXY(20,$y+30);
            $pdf->Cell(40,6,"Cliente",0,0,'C');
        }
    }

    // Supervisor
    if(!empty($m['firma_supervisor'])){
        $path = __DIR__ . "/../../uploads/firmas/".$m['firma_supervisor'];
        if(file_exists($path)){
            $pdf->Image($path,85,$y,40,25);
            $pdf->SetXY(85,$y+30);
            $pdf->Cell(40,6,"Supervisor",0,0,'C');
        }
    }

    // Técnico
    if(!empty($m['firma_tecnico'])){
        $path = __DIR__ . "/../../uploads/firmas/".$m['firma_tecnico'];
        if(file_exists($path)){
            $pdf->Image($path,150,$y,40,25);
            $pdf->SetXY(150,$y+30);
            $pdf->Cell(40,6,"Técnico",0,0,'C');
        }
    }

    // Guardar archivo
    $fileName = "reporte_servicio_{$m['id']}.pdf";
    $savePath = __DIR__ . "/../../uploads/reportes/".$fileName;
    $pdf->Output('F',$savePath);

    return $fileName;
}

// Ejecutar
$id = $_GET['id'] ?? null;
if(!$id){
    die("Acceso inválido.");
}

try {
    $file = generarPDF($id,$pdo);
    echo "Reporte generado correctamente: ".$file;
} catch(Exception $e){
    echo "Error: ".$e->getMessage();
}
