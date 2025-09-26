<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/fpdf.php';

// =======================
// GUARDAR DATOS
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo        = $_POST['titulo'] ?? '';
    $categoria     = $_POST['categoria'] ?? '';
    $descripcion   = $_POST['descripcion'] ?? '';
    $fecha         = $_POST['fecha'] ?? date('Y-m-d');
    $cliente_id    = $_POST['cliente_id'] ?? null;
    $inventario_id = $_POST['inventario_id'] ?? null;
    $estado        = $_POST['estado'] ?? 'pendiente';
    $digitador_id  = $_POST['digitador_id'] ?? null;
    $operador_id   = $_POST['operador_id'] ?? null;

    $stmt = $pdo->prepare("INSERT INTO mantenimientos 
        (titulo, categoria, descripcion, fecha, cliente_id, inventario_id, estado, digitador_id, operador_id) 
        VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$titulo, $categoria, $descripcion, $fecha, $cliente_id, $inventario_id, $estado, $digitador_id, $operador_id]);

    $mantenimiento_id = $pdo->lastInsertId();
}

// =======================
// OBTENER DATOS COMPLETOS
// =======================
$query = $pdo->prepare("SELECT m.*, c.nombre AS cliente, i.nombre AS equipo
    FROM mantenimientos m
    LEFT JOIN clientes c ON m.cliente_id = c.id
    LEFT JOIN inventario i ON m.inventario_id = i.id
    WHERE m.id = ?");
$query->execute([$mantenimiento_id]);
$reporte = $query->fetch(PDO::FETCH_ASSOC);

// =======================
// PDF CON DISEÑO
// =======================
class PDF extends FPDF {
    function Header() {
        // Logo
        $logoPath = __DIR__ . '/../../lib/logo.png';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 10, 8, 30);
        }
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, utf8_decode('Reporte de Mantenimiento'), 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Tabla de datos
$pdf->Cell(50, 10, 'ID:', 1);
$pdf->Cell(0, 10, $reporte['id'], 1, 1);

$pdf->Cell(50, 10, 'Titulo:', 1);
$pdf->Cell(0, 10, utf8_decode($reporte['titulo']), 1, 1);

$pdf->Cell(50, 10, 'Categoria:', 1);
$pdf->Cell(0, 10, utf8_decode($reporte['categoria']), 1, 1);

$pdf->Cell(50, 10, 'Descripcion:', 1);
$pdf->MultiCell(0, 10, utf8_decode($reporte['descripcion']), 1);

$pdf->Cell(50, 10, 'Fecha:', 1);
$pdf->Cell(0, 10, $reporte['fecha'], 1, 1);

$pdf->Cell(50, 10, 'Cliente:', 1);
$pdf->Cell(0, 10, utf8_decode($reporte['cliente']), 1, 1);

$pdf->Cell(50, 10, 'Equipo:', 1);
$pdf->Cell(0, 10, utf8_decode($reporte['equipo']), 1, 1);

$pdf->Cell(50, 10, 'Estado:', 1);
$pdf->Cell(0, 10, utf8_decode($reporte['estado']), 1, 1);

// =======================
// FOTOS (BLOB → PDF)
// =======================
if (!empty($_FILES['fotos']['tmp_name'][0])) {
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, utf8_decode('Fotos del equipo:'), 0, 1);

    foreach ($_FILES['fotos']['tmp_name'] as $index => $tmpPath) {
        if ($_FILES['fotos']['error'][$index] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['fotos']['name'][$index], PATHINFO_EXTENSION);
            $tmpFile = sys_get_temp_dir() . "/foto_" . uniqid() . "." . $ext;
            move_uploaded_file($tmpPath, $tmpFile);

            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])) {
                $pdf->Image($tmpFile, null, null, 60, 60);
                $pdf->Ln(65);
            }
            unlink($tmpFile);
        }
    }
}

// =======================
// DESCARGA DIRECTA
// =======================
$pdf->Output('D', "reporte_mantenimiento_{$mantenimiento_id}.pdf");
exit;
