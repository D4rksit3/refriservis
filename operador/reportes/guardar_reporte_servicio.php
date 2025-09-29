<?php
// guardar_reporte_servicio.php
// --- Descarga inmediata del PDF con diseño de "FORMATO DE CALIDAD" ---
// Debe colocarse sin ningún output previo (sin espacios antes de <?php)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// Evitamos que las funciones deprecated salgan en el PDF (previene "Some data has already been output")
error_reporting(E_ALL & ~E_DEPRECATED);

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header('Location: /../index.php');
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/fpdf.php';

/**
 * Guarda una firma base64 en uploads/firmas/ y devuelve el nombre de archivo (basename),
 * o NULL si no hubo firma.
 */
function saveSignatureFile(string $dataUrl = null, string $namePrefix = 'firma') {
    if (!$dataUrl) return null;
    if (strpos($dataUrl, ',') === false) return null;
    $parts = explode(',', $dataUrl, 2);
    $decoded = base64_decode($parts[1]);
    if ($decoded === false) return null;
    $dir = __DIR__ . "/../../uploads/firmas/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $fileName = $namePrefix . "_" . time() . "_" . bin2hex(random_bytes(4)) . ".png";
    file_put_contents($dir . $fileName, $decoded);
    return $fileName;
}

/**
 * Generar y forzar la descarga del PDF para el mantenimiento $id
 */
function generarPDF(PDO $pdo, int $id) {
    // Traer datos del mantenimiento y cliente
    $stmt = $pdo->prepare("
      SELECT m.*, c.cliente, c.direccion, c.responsable, c.telefono
      FROM mantenimientos m
      LEFT JOIN clientes c ON c.id = m.cliente_id
      WHERE m.id = ?
    ");
    $stmt->execute([$id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$m) {
        http_response_code(404);
        exit("Mantenimiento no encontrado.");
    }

    // Traer equipos según los campos equipo1..equipo7
    $equipos = [];
    for ($i = 1; $i <= 7; $i++) {
        $eqId = $m["equipo{$i}"] ?? null;
        if ($eqId) {
            $stmtEq = $pdo->prepare("SELECT * FROM equipos WHERE id_equipo = ? LIMIT 1");
            $stmtEq->execute([$eqId]);
            $row = $stmtEq->fetch(PDO::FETCH_ASSOC);
            if ($row) $equipos[$i] = $row;
            else $equipos[$i] = null;
        } else {
            $equipos[$i] = null;
        }
    }

    // parámetros (esperamos JSON con la estructura generada por el formulario)
    $parametrosStored = [];
    if (!empty($m['parametros'])) {
        $decoded = json_decode($m['parametros'], true);
        if (is_array($decoded)) $parametrosStored = $decoded;
    }

    // fotos (array de basenames)
    $fotos = [];
    if (!empty($m['fotos'])) {
        $decoded = json_decode($m['fotos'], true);
        if (is_array($decoded)) $fotos = $decoded;
    }

    // helper para tildes (FPDF usa ISO-8859-1)
    function txt($s) { return mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8'); }

    // Clase PDF (header y footer)
    class PDF extends FPDF {
        public $mantenimientoId;
        public function Header() {
            global $m;
            // Mantener bordes y estilo similar al HTML-proporcionado
            $left = $this->GetX();
            $top = $this->GetY();

            // Logo con celda bordeada
            $cellW = 40; $cellH = 25;
            $this->Rect($left, $top, $cellW, $cellH); // borde del recuadro
            if (file_exists(__DIR__ . '/../../lib/logo.jpeg')) {
                // Imagen centrada dentro del recuadro
                $imgW = 30; $imgH = 18;
                $imgX = $left + ($cellW - $imgW) / 2;
                $imgY = $top + ($cellH - $imgH) / 2;
                $this->Image(__DIR__ . '/../../lib/logo.jpeg', $imgX, $imgY, $imgW, $imgH);
            }
            $this->SetXY($left + $cellW + 2, $top);

            // Título en dos filas con fondo (imitando el diseño)
            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(207, 226, 243);
            $this->Cell(110, 7, txt("FORMATO DE CALIDAD"), 1, 1, 'C', true);
            // Título principal
            $this->SetX($left + $cellW + 2);
            $this->SetFont('Arial','B',12);
            $this->Cell(110, 10, txt("REPORTE DE SERVICIO TÉCNICO"), 1, 1, 'C');
            // Contacto fila
            $this->SetX($left + $cellW + 2);
            $this->SetFont('Arial','',8);
            $this->Cell(110, 8, txt("Oficina: (01) 6557907  |  Emergencias: +51 943 048 606  |  ventas@refriservissac.com"), 1, 0, 'C');

            // Número del reporte a la derecha
            $this->SetXY($left + $cellW + 2 + 110 + 4, $top); // algo a la derecha
            $this->SetFont('Arial','',9);
            $numCellW = 40; $numCellH = 25;
            $this->Rect($this->GetX(), $this->GetY(), $numCellW, $numCellH);
            $this->SetXY($this->GetX(), $this->GetY() + 6);
            $this->Cell($numCellW, 6, "001-N°" . str_pad($this->mantenimientoId ?? '', 6, "0", STR_PAD_LEFT), 0, 1, 'C');

            $this->Ln(6);
        }

        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,'Página '.$this->PageNo().'/{nb}',0,0,'C');
        }
    }

    // Construir PDF
    $pdf = new PDF('P','mm','A4');
    $pdf->mantenimientoId = $m['id'];
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial','',9);

    // ---------- DATOS DEL CLIENTE ----------
    $pdf->SetFont('Arial','B',9);
    $pdf->Cell(0,7, txt("Datos del Cliente"), 1, 1, 'C');
    $pdf->SetFont('Arial','',9);
    $pdf->Cell(40,7, txt("Cliente:"), 1, 0);
    $pdf->Cell(150,7, txt($m['cliente'] ?? ''), 1, 1);
    $pdf->Cell(40,7, txt("Dirección:"), 1, 0);
    $pdf->Cell(150,7, txt($m['direccion'] ?? ''), 1, 1);
    $pdf->Cell(40,7, txt("Responsable:"), 1, 0);
    $pdf->Cell(70,7, txt($m['responsable'] ?? ''), 1, 0);
    $pdf->Cell(40,7, txt("Teléfono:"), 1, 0);
    $pdf->Cell(40,7, txt($m['telefono'] ?? ''), 1, 1);
    $pdf->Ln(4);

    // ---------- EQUIPOS ----------
    $pdf->SetFont('Arial','B',9);
    $pdf->Cell(0,7, txt("Datos de Identificación de los Equipos"), 1, 1, 'C');
    $pdf->SetFont('Arial','B',8);
    $pdf->Cell(10,7,"#",1,0,'C');
    $pdf->Cell(40,7, txt("Identificador"), 1, 0, 'C');
    $pdf->Cell(40,7, txt("Marca"), 1, 0, 'C');
    $pdf->Cell(40,7, txt("Modelo"), 1, 0, 'C');
    $pdf->Cell(35,7, txt("Ubicación"), 1, 0, 'C');
    $pdf->Cell(25,7, txt("Voltaje"), 1, 1, 'C');
    $pdf->SetFont('Arial','',8);

    for ($i = 1; $i <= 7; $i++) {
        $pdf->Cell(10,7, $i, 1, 0, 'C');
        if (!empty($equipos[$i])) {
            $eq = $equipos[$i];
            $pdf->Cell(40,7, txt($eq['Identificador'] ?? ''), 1, 0);
            $pdf->Cell(40,7, txt($eq['marca'] ?? ''), 1, 0);
            $pdf->Cell(40,7, txt($eq['modelo'] ?? ''), 1, 0);
            $pdf->Cell(35,7, txt($eq['ubicacion'] ?? ''), 1, 0);
            $pdf->Cell(25,7, txt($eq['voltaje'] ?? ''), 1, 1);
        } else {
            $pdf->Cell(40,7, "", 1, 0);
            $pdf->Cell(40,7, "", 1, 0);
            $pdf->Cell(40,7, "", 1, 0);
            $pdf->Cell(35,7, "", 1, 0);
            $pdf->Cell(25,7, "", 1, 1);
        }
    }
    $pdf->Ln(4);

    // ---------- PARÁMETROS ----------
    $pdf->SetFont('Arial','B',9);
    $pdf->Cell(0,7, txt("Parámetros de Funcionamiento (Antes / Después)"), 1, 1, 'C');

    // Etiquetas (mismas que el formulario)
    $labels = [
        'Corriente eléctrica nominal (Amperios) L1',
        'Corriente L2','Corriente L3',
        'Tensión eléctrica nominal V1','Tensión V2','Tensión V3',
        'Presión de descarga (PSI)','Presión de succión (PSI)'
    ];

    // Layout compacto para que entre en A4: labelWidth + 7*(2*colW) <= ancho_util
    $leftMargin = $pdf->lMargin;
    $rightMargin = $pdf->rMargin;
    $pageWidth = $pdf->GetPageWidth();
    $usableW = $pageWidth - $leftMargin - $rightMargin;
    $labelW = 50;
    $colW = floor(($usableW - $labelW) / (7 * 2)); // ancho para Antes/Desp por equipo

    $pdf->SetFont('Arial','B',7);
    $pdf->Cell($labelW,7, txt("Medida"), 1, 0, 'C');
    for ($i = 1; $i <= 7; $i++) {
        $pdf->Cell($colW,7, txt("Eq{$i} A"), 1, 0, 'C');
        $pdf->Cell($colW,7, txt("Eq{$i} D"), 1, 0, 'C');
    }
    $pdf->Ln();

    $pdf->SetFont('Arial','',7);
    foreach ($labels as $label) {
        $pdf->Cell($labelW,7, txt($label), 1, 0);
        $hash = md5($label);
        for ($i = 1; $i <= 7; $i++) {
            $antes = $parametrosStored[$hash][$i]['antes'] ?? ($parametrosStored[$label][$i]['antes'] ?? "");
            $desp  = $parametrosStored[$hash][$i]['despues'] ?? ($parametrosStored[$label][$i]['despues'] ?? "");
            // Normalizar a string
            $pdf->Cell($colW,7, txt((string)$antes), 1, 0);
            $pdf->Cell($colW,7, txt((string)$desp), 1, 0);
        }
        $pdf->Ln();
    }
    $pdf->Ln(4);

    // ---------- TRABAJOS ----------
    $pdf->SetFont('Arial','B',9);
    $pdf->MultiCell(0,7, txt("Trabajos Realizados:\n" . ($m['trabajos'] ?? '')), 1);
    $pdf->Ln(2);

    // ---------- OBSERVACIONES ----------
    $pdf->SetFont('Arial','B',9);
    $pdf->MultiCell(0,7, txt("Observaciones y Recomendaciones:\n" . ($m['observaciones'] ?? '')), 1);
    $pdf->Ln(4);

    // ---------- FOTOS ----------
    if (!empty($fotos)) {
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(0,7, txt("Fotos del Servicio"), 1, 1, 'C');
        $pdf->Ln(2);

        $colsPerRow = 3;
        $imgCellW = ($usableW - 4) / $colsPerRow; // espacio para 3 por fila
        $imgCellH = 50;
        $colCounter = 0;
        foreach ($fotos as $foto) {
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            // dibujar borde del "recuadro" imagen
            $pdf->Rect($x, $y, $imgCellW, $imgCellH);
            $fpath = __DIR__ . "/../../uploads/fotos/" . $foto;
            if (file_exists($fpath)) {
                // insertar imagen centrada dentro del recuadro (reduciendo margen)
                $maxW = $imgCellW - 6;
                $maxH = $imgCellH - 6;
                list($iw, $ih) = getimagesize($fpath);
                $ratio = min($maxW / $iw, $maxH / $ih, 1);
                $drawW = $iw * $ratio;
                $drawH = $ih * $ratio;
                $imgX = $x + ($imgCellW - $drawW) / 2;
                $imgY = $y + ($imgCellH - $drawH) / 2;
                $pdf->Image($fpath, $imgX, $imgY, $drawW, $drawH);
            } else {
                $pdf->SetXY($x, $y + ($imgCellH/2) - 3);
                $pdf->Cell($imgCellW, 6, txt("[No encontrada]"), 0, 0, 'C');
            }
            // avanzar X
            $pdf->SetXY($x + $imgCellW + 2, $y);
            $colCounter++;
            if ($colCounter >= $colsPerRow) {
                $pdf->Ln($imgCellH + 4);
                $colCounter = 0;
            }
        }
        if ($colCounter > 0) $pdf->Ln($imgCellH + 4);
    }

    // ---------- FIRMAS (imágenes arriba y texto debajo) ----------
    $pdf->Ln(4);
    $pdf->SetFont('Arial','',9);

    $firmaBase = __DIR__ . "/../../uploads/firmas/";
    // Primero fila de imágenes (si existen) con altura 30
    $sigW = ($usableW - 4) / 3;
    $sigH = 30;
    $sigFiles = [
        $m['firma_cliente'] ?? null,
        $m['firma_supervisor'] ?? null,
        $m['firma_tecnico'] ?? null
    ];
    // Imagenes (centradas en celdas)
    foreach ($sigFiles as $idx => $sfile) {
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->Rect($x, $y, $sigW, $sigH); // borde
        if ($sfile && file_exists($firmaBase . $sfile)) {
            list($iw, $ih) = getimagesize($firmaBase . $sfile);
            $ratio = min(($sigW - 6) / $iw, ($sigH - 6) / $ih, 1);
            $drawW = $iw * $ratio;
            $drawH = $ih * $ratio;
            $imgX = $x + ($sigW - $drawW) / 2;
            $imgY = $y + ($sigH - $drawH) / 2;
            $pdf->Image($firmaBase . $sfile, $imgX, $imgY, $drawW, $drawH);
        }
        $pdf->SetXY($x + $sigW + 2, $y);
    }
    $pdf->Ln($sigH + 2);

    // Etiquetas debajo
    $pdf->Cell($sigW, 8, txt("Firma Cliente"), 1, 0, 'C');
    $pdf->Cell($sigW, 8, txt("Firma Supervisor"), 1, 0, 'C');
    $pdf->Cell($sigW, 8, txt("Firma Técnico"), 1, 1, 'C');

    // ----------------- Forzar descarga -----------------
    // Limpiar buffer por si acaso (previene el error "Some data has already been output")
    if (ob_get_length()) {
        @ob_end_clean();
    }
    $fileName = "reporte_servicio_{$m['id']}.pdf";
    $pdf->Output('D', $fileName);
    exit;
}

// ----- FLUJO: POST guarda y genera, GET solo genera -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Guardar los datos enviados y luego generar PDF
    $mantenimiento_id = intval($_POST['mantenimiento_id'] ?? 0);
    if (!$mantenimiento_id) {
        http_response_code(400);
        exit("ID de mantenimiento no proporcionado en POST.");
    }

    // Trabajos / observaciones / parámetros
    $trabajos = $_POST['trabajos'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    $parametros = $_POST['parametros'] ?? [];

    // Guardar firmas (devuelve basename o null)
    $firma_cliente = saveSignatureFile($_POST['firma_cliente'] ?? '', 'cliente');
    $firma_supervisor = saveSignatureFile($_POST['firma_supervisor'] ?? '', 'supervisor');
    $firma_tecnico = saveSignatureFile($_POST['firma_tecnico'] ?? '', 'tecnico');

    // Guardar fotos
    $fotos_guardadas = [];
    if (!empty($_FILES['fotos']['name'][0])) {
        $dir = __DIR__ . "/../../uploads/fotos/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        foreach ($_FILES['fotos']['tmp_name'] as $k => $tmp) {
            if (is_uploaded_file($tmp)) {
                $original = basename($_FILES['fotos']['name'][$k]);
                $fileName = time() . "_" . bin2hex(random_bytes(4)) . "_" . $original;
                move_uploaded_file($tmp, $dir . $fileName);
                $fotos_guardadas[] = $fileName;
            }
        }
    }

    // Guardar JSON de parámetros (tal como viene del form)
    $paramJson = json_encode($parametros);

    // Actualizar la fila en mantenimientos
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
        $paramJson,
        $firma_cliente,
        $firma_supervisor,
        $firma_tecnico,
        json_encode($fotos_guardadas),
        $_SESSION['usuario_id'] ?? null,
        $mantenimiento_id
    ]);

    // Generar PDF y ofrecer descarga
    generarPDF($pdo, $mantenimiento_id);

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $mantenimiento_id = intval($_GET['id']);
    if (!$mantenimiento_id) {
        http_response_code(400);
        exit("ID inválido.");
    }
    generarPDF($pdo, $mantenimiento_id);
} else {
    // Si se accede sin parámetros mostramos un mensaje simple (no se usa para descarga)
    echo "Accede a este archivo mediante el formulario de reporte (POST) o con ?id= (GET).";
}
