<?php
// guardar_reporte_bombas.php
// --- Descarga inmediata del PDF con diseño de "FORMATO DE CALIDAD" ---
// Debe colocarse sin ningún output previo (sin espacios antes de <?php)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// Evitamos que las funciones deprecated salgan en el PDF (previene "Some data has already been output")
error_reporting(E_ALL & ~E_DEPRECATED);

session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['operador', 'digitador', 'admin'])) {
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
        SELECT 
            m.*, 
            c.cliente, 
            c.direccion, 
            c.responsable, 
            c.telefono, 
            sup.nombre AS supervisor,
            tec.nombre AS tecnico
        FROM mantenimientos m
        LEFT JOIN clientes c ON c.id = m.cliente_id
        LEFT JOIN usuarios sup ON sup.id = m.digitador_id
        LEFT JOIN usuarios tec ON tec.id = m.operador_id
        WHERE m.id = ?
    ");
    $stmt->execute([$id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$m) {
        http_response_code(404);
        exit("Mantenimiento no encontrado.");
    }

    $userName = 'Desconocido';
    $fechaModificacion = !empty($m['modificado_en'])
        ? date('d/m/Y H:i:s', strtotime($m['modificado_en']))
        : 'Sin fecha';
    if (!empty($m['modificado_por'])) {
        $stmtUser = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
        $stmtUser->execute([$m['modificado_por']]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if ($user && !empty($user['nombre'])) {
            $userName = $user['nombre'];
        }
    } elseif (!empty($m['digitador_id'])) {
        $stmtUser = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
        $stmtUser->execute([$m['digitador_id']]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if ($user && !empty($user['nombre'])) {
            $userName = $user['nombre'];
        }
    }



    // Traer equipos según los campos equipo1..equipo7
    $equipos = [];
    for ($i = 1; $i <= 7; $i++) {
        $eqId = $m["equipo{$i}"] ?? null;
        if ($eqId) {
            $stmtEq = $pdo->prepare("SELECT * FROM equipos WHERE id_equipo = ? LIMIT 1");
            $stmtEq->execute([$eqId]);
            $row = $stmtEq->fetch(PDO::FETCH_ASSOC);
            $equipos[$i] = $row ?: null;
        } else {
            $equipos[$i] = null;
        }
    }

    // parámetros (JSON)
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
        
        // Getter para márgenes
        public function getLeftMargin() { return $this->lMargin; }
        public function getRightMargin() { return $this->rMargin; }
            
            
        public function Header() {
            $left = 10; 
            $top  = 10;

            // Configuración de anchos
            $logoW   = 40;
            $centerW = 110;
            $numW    = 40;

            // Texto central
            $this->SetFont('Arial','B',11);
            $text = txt("CHECK LIST DE MANTENIMIENTO PREVENTIVO DE EQUIPOS - BOMBA DE AGUA");

            // Altura de línea
            $lineH = 5.5;
            $nbLines = substr_count($text, "\n") + 1;
            $centerH = 7 + ($nbLines * $lineH) + 8;
            $cellH = max(35, $centerH);

            // -------------------------------
            // Logo con sombra sutil
            // -------------------------------
            $this->SetDrawColor(13, 110, 253); // Color primario #0d6efd
            $this->SetLineWidth(0.5);
            $this->Rect($left, $top, $logoW, $cellH);
            
            if (file_exists(__DIR__ . '/../../lib/logo.jpeg')) {
                $imgW = 32; $imgH = 20;
                $imgX = $left + ($logoW - $imgW) / 2;
                $imgY = $top + ($cellH - $imgH) / 2;
                $this->Image(__DIR__ . '/../../lib/logo.jpeg', $imgX, $imgY, $imgW, $imgH);
            }

            // -------------------------------
            // Bloque central - Gradiente azul
            // -------------------------------
            $this->SetXY($left + $logoW, $top);

            // Título principal con fondo azul principal
            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(13, 110, 253); // #0d6efd
            $this->SetTextColor(255, 255, 255);
            $this->Cell($centerW, 7, txt("FORMATO DE CALIDAD"), 1, 2, 'C', true);

            // Subtítulo con texto oscuro
            $this->SetTextColor(33, 37, 41); // Gris oscuro
            $this->SetFont('Arial','B',10);
            $this->MultiCell($centerW, $lineH, $text, 0, 'C');

            $yAfterTitle = $this->GetY();

            // Contacto con fondo azul claro
            $contacto = txt("Oficina: (01) 6557907  |  Emergencias: +51 943 048 606  |  ventas@refriservissac.com");
            $this->SetXY($left + $logoW, $top + $cellH - 8);
            $this->SetFont('Arial','',7.5);
            $this->SetFillColor(227, 242, 253); // Azul muy claro
            $this->SetTextColor(13, 110, 253);
            $this->Cell($centerW, 8, $contacto, 1, 2, 'C', true);

            // Marco del bloque central
            $this->SetDrawColor(13, 110, 253);
            $this->Rect($left + $logoW, $top, $centerW, $cellH);

            // -------------------------------
            // Número con estilo destacado
            // -------------------------------
            $this->SetXY($left + $logoW + $centerW, $top);
            $this->SetDrawColor(13, 110, 253);
            $this->Rect($this->GetX(), $this->GetY(), $numW, $cellH);

            $this->SetFont('Arial','B',11);
            $this->SetTextColor(13, 110, 253);
            $this->SetXY($this->GetX(), $this->GetY() + ($cellH/2) - 4);
            $this->Cell($numW, 8, "N" . chr(176) . str_pad($this->mantenimientoId ?? '', 6, "0", STR_PAD_LEFT), 0, 1, 'C');

            // Resetear colores
            $this->SetTextColor(0, 0, 0);
            $this->SetDrawColor(0, 0, 0);

            $this->Ln(4);
            $this->SetY($top + $cellH + 10);
        }
        
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 7.5);
            $this->SetTextColor(108, 117, 125); // Gris medio

            date_default_timezone_set('America/Lima');
            $fechaHora = date('d/m/Y H:i:s');
            $usuario = isset($this->user) ? $this->user : 'Desconocido';

            $texto = utf8_decode("Pág. " . $this->PageNo() . " de {nb}  |  Generado por: $usuario  |  {$this->fechaModificacion}");
            $this->Cell(0, 10, $texto, 0, 0, 'C');
            
            $this->SetTextColor(0, 0, 0);
        }
    }

    /**
     * Corrige la orientación de una imagen según sus metadatos EXIF.
     */
    function corregirOrientacion($ruta) {
        $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg'])) {
            return $ruta;
        }

        $exif = @exif_read_data($ruta);
        if (!isset($exif['Orientation'])) {
            return $ruta;
        }

        $image = imagecreatefromjpeg($ruta);
        switch ($exif['Orientation']) {
            case 3:
                $image = imagerotate($image, 180, 0);
                break;
            case 6:
                $image = imagerotate($image, -90, 0);
                break;
            case 8:
                $image = imagerotate($image, 90, 0);
                break;
            default:
                return $ruta;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'img_') . '.jpg';
        imagejpeg($image, $tmp, 90);
        imagedestroy($image);

        return $tmp;
    }

    // Construir PDF
    $pdf = new PDF('P','mm','A4');
    $pdf->mantenimientoId = $m['id'];
    $pdf->user = $userName;
    $pdf->fechaModificacion = $fechaModificacion;
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial','',9);

    $pageHeight = 297;
    $bottomMargin = 15;

    // ---------- DATOS DEL CLIENTE ----------
    $pdf->SetFont('Arial','B',9.5);
    $pdf->SetFillColor(13, 110, 253);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0,8, txt("DATOS DEL CLIENTE"), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);

    // Fila: Cliente + Supervisor
    $pdf->SetFont('Arial','B',8.5);
    $pdf->SetFillColor(240, 248, 255); // Azul muy claro
    $pdf->Cell(28,7, txt("Cliente:"), 1, 0, 'L', true);
    $pdf->SetFont('Arial','',8.5);
    $pdf->Cell(87,7, txt($m['cliente'] ?? ''), 1, 0);
    $pdf->SetFont('Arial','B',8.5);
    $pdf->SetFillColor(240, 248, 255);
    $pdf->Cell(28,7, txt("Supervisor:"), 1, 0, 'L', true);
    $pdf->SetFont('Arial','',8.5);
    $pdf->Cell(47,7, txt($m['supervisor'] ?? ''), 1, 1);

    // Fila: Dirección
    $pdf->SetFont('Arial','B',8.5);
    $pdf->SetFillColor(240, 248, 255);
    $pdf->Cell(28,7, txt("Dirección:"), 1, 0, 'L', true);
    $pdf->SetFont('Arial','',8.5);
    $pdf->Cell(162,7, txt($m['direccion'] ?? ''), 1, 1);

    // Fila: Responsable + Teléfono
    $pdf->SetFont('Arial','B',8.5);
    $pdf->SetFillColor(240, 248, 255);
    $pdf->Cell(28,7, txt("Responsable:"), 1, 0, 'L', true);
    $pdf->SetFont('Arial','',8.5);
    $pdf->Cell(87,7, txt($m['responsable'] ?? ''), 1, 0);
    $pdf->SetFont('Arial','B',8.5);
    $pdf->SetFillColor(240, 248, 255);
    $pdf->Cell(28,7, txt("Teléfono:"), 1, 0, 'L', true);
    $pdf->SetFont('Arial','',8.5);
    $pdf->Cell(47,7, txt($m['telefono'] ?? ''), 1, 1);

    $pdf->Ln(5);

    // ---------- EQUIPOS ----------
    $pdf->SetFont('Arial','B',9.5);
    $pdf->SetFillColor(13, 110, 253);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0,8, txt("DATOS DE IDENTIFICACIÓN DE LOS EQUIPOS"), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    
    // Encabezado de tabla
    $pdf->SetFont('Arial','B',8);
    $pdf->SetFillColor(227, 242, 253); // Azul claro
    $pdf->SetTextColor(13, 110, 253);
    $pdf->Cell(10,7,"#",1,0,'C',true);
    $pdf->Cell(40,7, txt("Identificador / Nombre"), 1, 0, 'C',true);
    $pdf->Cell(40,7, txt("Marca"), 1, 0, 'C',true);
    $pdf->Cell(40,7, txt("Modelo"), 1, 0, 'C',true);
    $pdf->Cell(35,7, txt("Ubicación"), 1, 0, 'C',true);
    $pdf->Cell(25,7, txt("Voltaje"), 1, 1, 'C',true);
    
    $pdf->SetFont('Arial','',8);
    $pdf->SetTextColor(0, 0, 0);

    // Filas de equipos
    for ($i = 1; $i <= 7; $i++) {
        if (!empty($equipos[$i])) {
            $eq = $equipos[$i];
            $identificador = $eq['Identificador'] ?? '';
            $nombre = $eq['Nombre'] ?? '';
            $texto = trim($identificador . ' - ' . $nombre);

            $lineHeight = 6;
            $colAnchos = [10, 40, 40, 40, 35, 25];

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x + $colAnchos[0], $y);
            $pdf->MultiCell($colAnchos[1], $lineHeight, utf8_decode($texto), 0, 'L');
            $alturaTexto = $pdf->GetY() - $y;

            $cellHeight = max($lineHeight, $alturaTexto);

            // Alternar color de fondo
            $fillColor = ($i % 2 == 0) ? [248, 249, 250] : [255, 255, 255];
            $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);

            $pdf->SetXY($x, $y);
            $pdf->Cell($colAnchos[0], $cellHeight, $i, 1, 0, 'C', true);

            $pdf->SetXY($x + $colAnchos[0], $y);
            $pdf->MultiCell($colAnchos[1], $lineHeight, utf8_decode($texto), 1, 'L');

            $newY = $pdf->GetY();

            $pdf->SetXY($x + $colAnchos[0] + $colAnchos[1], $y);
            $pdf->Cell($colAnchos[2], $cellHeight, txt($eq['marca'] ?? ''), 1, 0, 'L', true);
            $pdf->Cell($colAnchos[3], $cellHeight, txt($eq['modelo'] ?? ''), 1, 0, 'L', true);
            $pdf->Cell($colAnchos[4], $cellHeight, txt($eq['ubicacion'] ?? ''), 1, 0, 'L', true);
            $pdf->Cell($colAnchos[5], $cellHeight, txt($eq['voltaje'] ?? ''), 1, 1, 'L', true);

            $pdf->SetY(max($newY, $y + $cellHeight));

        } else {
            $fillColor = ($i % 2 == 0) ? [248, 249, 250] : [255, 255, 255];
            $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
            
            $pdf->Cell(10,7,$i,1,0,'C',true);
            $pdf->Cell(40,7,"",1,0,'L',true);
            $pdf->Cell(40,7,"",1,0,'L',true);
            $pdf->Cell(40,7,"",1,0,'L',true);
            $pdf->Cell(35,7,"",1,0,'L',true);
            $pdf->Cell(25,7,"",1,1,'L',true);
        }
    }

    $pdf->Ln(5);

    // ---------- PARÁMETROS ----------
    $pdf->SetFont('Arial','B',9.5);
    $pdf->SetFillColor(13, 110, 253);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0,8, txt("PARÁMETROS DE FUNCIONAMIENTO (Antes / Después)"), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);

    $labels = [
        'Corriente eléctrica nominal (Amperios) L1',
        'Corriente L2','Corriente L3',
        'Tensión eléctrica nominal V1','Tensión V2','Tensión V3',
        'Presión de descarga (PSI)','Presión de succión (PSI)'
    ];

    $pageWidth   = $pdf->GetPageWidth();
    $leftMargin  = $pdf->getLeftMargin();
    $rightMargin = $pdf->getRightMargin();
    $usableW     = $pageWidth - $leftMargin - $rightMargin;
    
    $labelW  = 55;
    $totalCols = 7 * 2;
    $colW = ($usableW - $labelW) / $totalCols;

    $pdf->SetFont('Arial','B',7);
    $pdf->SetFillColor(227, 242, 253);
    $pdf->SetTextColor(13, 110, 253);
    $pdf->Cell($labelW,7, txt("Medida"), 1, 0, 'C', true);
    for ($i = 1; $i <= 7; $i++) {
        $pdf->Cell($colW,7, txt("Eq{$i} A"), 1, 0, 'C', true);
        $pdf->Cell($colW,7, txt("Eq{$i} D"), 1, 0, 'C', true);
    }
    $pdf->Ln();

    $pdf->SetFont('Arial','',7);
    $pdf->SetTextColor(0, 0, 0);
    
    $rowCounter = 0;
    foreach ($labels as $label) {
        $fillColor = ($rowCounter % 2 == 0) ? [248, 249, 250] : [255, 255, 255];
        $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
        
        $pdf->Cell($labelW,7, txt($label), 1, 0, 'L', true);
        $hash = md5($label);
        for ($i = 1; $i <= 7; $i++) {
            $antes = $parametrosStored[$hash][$i]['antes'] ?? ($parametrosStored[$label][$i]['antes'] ?? "");
            $desp  = $parametrosStored[$hash][$i]['despues'] ?? ($parametrosStored[$label][$i]['despues'] ?? "");
            $pdf->Cell($colW,7, txt((string)$antes), 1, 0, 'C', true);
            $pdf->Cell($colW,7, txt((string)$desp), 1, 0, 'C', true);
        }
        $pdf->Ln();
        $rowCounter++;
    }
    $pdf->Ln(5);

    // ---------- NUEVA PÁGINA - ACTIVIDADES ----------
    $pdf->AddPage();

    $nameW = 80;
    $dayW  = 10;
    $freqW = 8;
    $lineH = 5;

    $totalWidth = $nameW + ($dayW * 7) + ($freqW * 4);

    $pdf->SetFont('Arial','B',9.5);
    $pdf->SetFillColor(13, 110, 253);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell($totalWidth,8, txt("ACTIVIDADES A REALIZAR"), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);

    // Cabecera
    $pdf->SetFont('Arial','B',7);
    $pdf->SetFillColor(227, 242, 253);
    $pdf->SetTextColor(13, 110, 253);
    $pdf->Cell($nameW,6, txt("Actividad"), 1, 0, 'C', true);
    for ($i = 1; $i <= 7; $i++) {
        $pdf->Cell($dayW,6, str_pad($i,2,'0',STR_PAD_LEFT), 1, 0, 'C', true);
    }
    $pdf->Cell($freqW,6,"B",1,0,'C', true);
    $pdf->Cell($freqW,6,"T",1,0,'C', true);
    $pdf->Cell($freqW,6,"S",1,0,'C', true);
    $pdf->Cell($freqW,6,"A",1,1,'C', true);
    $pdf->SetTextColor(0, 0, 0);

    $actividadesList = [
        "Inspección ocular del equipo en funcionamiento",
        "Verificación del estado de superficies y aseo general del equipo",
        "Medición y registro de parámetros de operación (amperaje, voltaje, potencia)",
        "Inspección de estado del sello mecánico",
        "Inspección de manómetros y termómetros",
        "Inspección de rodamientos de motor y bomba centrifuga",
        "Inspección del acoplamiento y ajuste de prisioneros",
        "Medición y registro de consumos eléctricos",
        "Ajuste de conexiones eléctricas del motor",
        "Revisión de variador de velocidad",
        "Lubricación de rodamientos de acuerdo a recomendaciones del fabricante",
        "Revisión de los pernos de la base y motor (requiere uso de torquímetro)",
        "Pintado externo del motor y bomba manteniendo color original (dieléctrica)",
        "Prueba de funcionamiento y verificación de condiciones operativas",
        "Lubricación y engrase de la bomba.",
        "Revisión y Ajuste de la prensa estopa y/o sello mecánico",
        "Revisión y/o cambio de empaquetaduras de O-rings",
        "Revisión y cambio de borneras eléctricas",
        "Cambio de empaquetaduras, sellos y rodamientos en caso se requiera",
        "Pintado de las válvulas y de las tuberías de distribución si lo requiere",
        "Megar y registrar el estado del aislamiento del motor eléctrico",
    ];

    $actividadesBD = json_decode($m['actividades'] ?? '[]', true);
    if (!is_array($actividadesBD)) $actividadesBD = [];

    $getNbLines = function($pdf, $w, $txt) use ($lineH) {
        $txt = trim(str_replace("\r", '', $txt));
        if ($txt === '') return 1;
        $words = explode(' ', $txt);
        $nb = 1;
        $lineWidth = 0;
        foreach ($words as $word) {
            $wordWidth = $pdf->GetStringWidth($word . ' ');
            if ($lineWidth + $wordWidth <= $w) {
                $lineWidth += $wordWidth;
            } else {
                $nb++;
                $lineWidth = $wordWidth;
            }
        }
        return $nb;
    };

    $printHeader = function() use ($pdf, $nameW, $dayW, $freqW, $totalWidth) {
        $pdf->SetFont('Arial','B',9.5);
        $pdf->SetFillColor(13, 110, 253);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($totalWidth,8, txt("ACTIVIDADES A REALIZAR"), 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->SetFont('Arial','B',7);
        $pdf->SetFillColor(227, 242, 253);
        $pdf->SetTextColor(13, 110, 253);
        $pdf->Cell($nameW,6, txt("Actividad"), 1, 0, 'C', true);
        for ($i = 1; $i <= 7; $i++) {
            $pdf->Cell($dayW,6, str_pad($i,2,'0',STR_PAD_LEFT), 1, 0, 'C', true);
        }
        $pdf->Cell($freqW,6,"B",1,0,'C', true);
        $pdf->Cell($freqW,6,"T",1,0,'C', true);
        $pdf->Cell($freqW,6,"S",1,0,'C', true);
        $pdf->Cell($freqW,6,"A",1,1,'C', true);
        $pdf->SetFont('Arial','',7);
        $pdf->SetTextColor(0, 0, 0);
    };

    $pdf->SetFont('Arial','',7);
    $rowCounter = 0;
    
    foreach ($actividadesList as $idx => $nombreRaw) {
        $actividadBD = $actividadesBD[$idx] ?? ["dias" => [], "frecuencia" => null];
        $diasMarcados = $actividadBD['dias'] ?? [];
        if (!is_array($diasMarcados)) {
            $diasMarcados = json_decode($diasMarcados, true) ?: [];
        }

        $nombre = txt($nombreRaw);
        $nb = $getNbLines($pdf, $nameW - 2, $nombre);
        $h = $nb * $lineH;

        if ($pdf->GetY() + $h + $bottomMargin > $pageHeight) {
            $pdf->AddPage();
            $printHeader();
            $rowCounter = 0;
        }

        $fillColor = ($rowCounter % 2 == 0) ? [248, 249, 250] : [255, 255, 255];
        $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->MultiCell($nameW, $lineH, $nombre, 1, 'L', true);

        $usedHeight = $pdf->GetY() - $y;
        $pdf->SetXY($x + $nameW, $y);

        for ($d = 1; $d <= 7; $d++) {
            $marca = in_array($d, $diasMarcados, true) ? "X" : "";
            $pdf->Cell($dayW, $usedHeight, $marca, 1, 0, 'C', true);
        }

        foreach (["B","T","S","A"] as $f) {
            $marca = ($actividadBD['frecuencia'] === $f) ? "X" : "";
            $pdf->Cell($freqW, $usedHeight, $marca, 1, 0, 'C', true);
        }

        $pdf->Ln($usedHeight);
        $rowCounter++;
    }

    $pdf->Ln(3);

    // ---------- TRABAJOS REALIZADOS ----------
    $pdf->SetFont('Arial','B',9.5);
    $pdf->SetFillColor(13, 110, 253);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0,8, txt("TRABAJOS REALIZADOS"), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetFont('Arial','',9);
    $pdf->SetFillColor(248, 249, 250);
    $pdf->MultiCell(0,6, txt($m['trabajos'] ?? 'Sin trabajos registrados'), 1, 'L', true);
    $pdf->Ln(3);

    // ---------- OBSERVACIONES ----------
    if (!empty($m['observaciones'])) {
        $observaciones = json_decode($m['observaciones'], true);

        if (is_array($observaciones)) {
            foreach ($observaciones as $obs) {
                $pdf->AddPage();
                
                $xStart = 10;
                $yStart = $pdf->GetY();
                $pdf->SetDrawColor(13, 110, 253);
                $pdf->SetLineWidth(0.3);

                $startY = $pdf->GetY();

                // Encabezado de observación
                $pdf->SetFont('Arial','B',9.5);
                $pdf->SetFillColor(13, 110, 253);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell(0,8, txt("OBSERVACIÓN"), 1, 1, 'C', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Ln(2);

                // Equipo
                $codigoEquipo = $obs['equipo'] ?? '';
                $nombreEquipo = '';

                if (!empty($codigoEquipo)) {
                    $stmtEq = $pdo->prepare("SELECT Nombre FROM equipos WHERE Identificador = ? LIMIT 1");
                    $stmtEq->execute([$codigoEquipo]);
                    $rowEq = $stmtEq->fetch(PDO::FETCH_ASSOC);
                    if ($rowEq) {
                        $nombreEquipo = $rowEq['Nombre'] ?? '';
                    }
                }

                $pdf->SetFont('Arial','B',9);
                $pdf->SetFillColor(227, 242, 253);
                $pdf->SetTextColor(13, 110, 253);
                $pdf->Cell(25,7, txt("Equipo:"), 1, 0, 'L', true);
                $pdf->SetFont('Arial','',9);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFillColor(255, 255, 255);
                $pdf->Cell(0,7, utf8_decode($codigoEquipo . " - " . $nombreEquipo), 1, 1, 'L', true);
                $pdf->Ln(2);

                // Texto de observación
                $pdf->SetFont('Arial','B',9);
                $pdf->SetFillColor(227, 242, 253);
                $pdf->SetTextColor(13, 110, 253);
                $pdf->Cell(0,7, txt("Detalle:"), 1, 1, 'L', true);
                $pdf->SetTextColor(0, 0, 0);
                
                $pdf->SetFont('Arial','',9);
                $texto = isset($obs['texto']) ? $obs['texto'] : '';
                $pdf->SetFillColor(248, 249, 250);
                $pdf->MultiCell(0,6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $texto), 1, 'L', true);
                $pdf->Ln(3);

                // Imágenes
                if (!empty($obs['imagenes']) && is_array($obs['imagenes'])) {
                    $pdf->SetFont('Arial','B',9);
                    $pdf->SetFillColor(227, 242, 253);
                    $pdf->SetTextColor(13, 110, 253);
                    $pdf->Cell(0,7, txt("Evidencias Fotográficas:"), 1, 1, 'L', true);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->Ln(2);

                    $maxWidth = 85;
                    $maxHeight = 65;
                    $margin = 10;
                    $count = 0;

                    foreach ($obs['imagenes'] as $imgPath) {
                        $realPath = __DIR__ . '/' . $imgPath;

                        if (file_exists($realPath)) {
                            $correctedPath = corregirOrientacion($realPath);
                            [$width, $height] = getimagesize($correctedPath);

                            $ratio = min($maxWidth / $width, $maxHeight / $height);
                            $w_mm = $width * $ratio;
                            $h_mm = $height * $ratio;

                            $x = 15 + ($count % 2) * ($maxWidth + $margin);
                            $y = $pdf->GetY();

                            if ($y + $h_mm > 270) {
                                $pdf->AddPage();
                                $y = $pdf->GetY();
                            }

                            // Borde azul alrededor de la imagen
                            $pdf->SetDrawColor(13, 110, 253);
                            $pdf->SetLineWidth(0.5);
                            $pdf->Rect($x - 2, $y - 2, $w_mm + 4, $h_mm + 4);

                            $pdf->Image($correctedPath, $x, $y, $w_mm, $h_mm);

                            if ($count % 2 == 1) {
                                $pdf->Ln($h_mm + 8);
                            }

                            $count++;
                        } else {
                            $pdf->SetFont('Arial', 'I', 8);
                            $pdf->SetTextColor(220, 53, 69); // Rojo
                            $pdf->Cell(0, 5, "Imagen no encontrada: $imgPath", 0, 1, 'L');
                            $pdf->SetTextColor(0, 0, 0);
                        }
                    }

                    if ($count % 2 == 1) {
                        $pdf->Ln($maxHeight + 5);
                    }
                }

                $endY = $pdf->GetY();
                $pdf->SetDrawColor(13, 110, 253);
                $pdf->Rect($xStart, $yStart, 190 - $xStart, $endY - $yStart);
                $pdf->Ln(4);
            }
        } else {
            $pdf->SetFont('Arial','I',9);
            $pdf->SetTextColor(108, 117, 125);
            $pdf->Cell(0,6, utf8_decode("No hay observaciones registradas."), 0, 1);
            $pdf->SetTextColor(0, 0, 0);
        }
    } else {
        $pdf->SetFont('Arial','I',9);
        $pdf->SetTextColor(108, 117, 125);
        $pdf->Cell(0,6, utf8_decode("No hay observaciones registradas."), 0, 1);
        $pdf->SetTextColor(0, 0, 0);
    }

    $pdf->Ln(3);

    // ---------- FOTOS DEL SERVICIO ----------
    if (!empty($fotos)) {
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',9.5);
        $pdf->SetFillColor(13, 110, 253);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0,8, txt("FOTOS DEL SERVICIO"), 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(3);

        $colsPerRow = 3;
        $imgCellW = ($usableW - 4) / $colsPerRow;
        $imgCellH = 50;
        $colCounter = 0;
        
        foreach ($fotos as $foto) {
            // Verificar si hay espacio suficiente para la imagen
            if ($pdf->GetY() + $imgCellH > $pageHeight - $bottomMargin) {
                $pdf->AddPage();
                $pdf->SetFont('Arial','B',9.5);
                $pdf->SetFillColor(13, 110, 253);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell(0,8, txt("FOTOS DEL SERVICIO (continuación)"), 1, 1, 'C', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Ln(3);
                $colCounter = 0;
            }
            
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            
            // Borde azul
            $pdf->SetDrawColor(13, 110, 253);
            $pdf->SetLineWidth(0.5);
            $pdf->Rect($x, $y, $imgCellW, $imgCellH);
            
            $fpath = __DIR__ . "/../../uploads/fotos/" . $foto;
            if (file_exists($fpath)) {
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
                $pdf->SetFont('Arial','I',8);
                $pdf->SetTextColor(220, 53, 69);
                $pdf->Cell($imgCellW, 6, txt("[No encontrada]"), 0, 0, 'C');
                $pdf->SetTextColor(0, 0, 0);
            }
            
            $pdf->SetXY($x + $imgCellW + 2, $y);
            $colCounter++;
            
            if ($colCounter >= $colsPerRow) {
                $pdf->Ln($imgCellH + 4);
                $colCounter = 0;
            }
        }
        
        if ($colCounter > 0) $pdf->Ln($imgCellH + 4);
    }

    // ---------- FIRMAS ----------
    $sigH = 35;
    $sigLabelH = 7 + 6; // altura de las etiquetas y nombres

    // Verificar si hay espacio suficiente para toda la sección de firmas
    if ($pdf->GetY() + $sigH + $sigLabelH + 20 > $pageHeight - $bottomMargin) {
        $pdf->AddPage();
    }

    $pdf->Ln(6);
    $pdf->SetFont('Arial','B',9.5);
    $pdf->SetFillColor(13, 110, 253);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0,8, txt("FIRMAS Y CONFORMIDAD"), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(3);

    $firmaBase = __DIR__ . "/../../uploads/firmas/";
    $sigW = ($usableW - 4) / 3;
    
    $sigFiles = [
        $m['firma_cliente'] ?? null,
        $m['firma_supervisor'] ?? null,
        $m['firma_tecnico'] ?? null
    ];
    
    foreach ($sigFiles as $sfile) {
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        
        // Borde azul
        $pdf->SetDrawColor(13, 110, 253);
        $pdf->SetLineWidth(0.5);
        $pdf->Rect($x, $y, $sigW, $sigH);
        
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

    // Etiquetas de firmas
    $pdf->SetFont('Arial','B',9);
    $pdf->SetFillColor(227, 242, 253);
    $pdf->SetTextColor(13, 110, 253);
    $pdf->Cell($sigW, 7, txt("Firma Cliente"), 1, 0, 'C', true);
    $pdf->Cell($sigW, 7, txt("Firma Supervisor"), 1, 0, 'C', true);
    $pdf->Cell($sigW, 7, txt("Firma Técnico"), 1, 1, 'C', true);

    // Nombres debajo de las firmas
    $pdf->SetFont('Arial','',8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(248, 249, 250);
    $pdf->Cell($sigW, 6, txt($m['nombre_cliente'] ?? ''), 1, 0, 'C', true);
    $pdf->Cell($sigW, 6, txt($m['nombre_supervisor'] ?? ''), 1, 0, 'C', true);
    $pdf->Cell($sigW, 6, txt($m['tecnico'] ?? ''), 1, 1, 'C', true);

    // ----------------- Forzar descarga -----------------
    if (ob_get_length()) {
        @ob_end_clean();
    }
    $fileName = "reporte_bombas_{$m['id']}.pdf";
    $pdf->Output('D', $fileName);
    exit;
}

// ----- FLUJO: POST guarda y genera, GET solo genera -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mantenimiento_id = intval($_POST['mantenimiento_id'] ?? 0);
    if (!$mantenimiento_id) {
        http_response_code(400);
        exit("ID de mantenimiento no proporcionado en POST.");
    }

    $trabajos = $_POST['trabajos'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    $parametros = $_POST['parametros'] ?? [];
    $actividades    = $_POST['actividades'] ?? [];

    $firma_cliente = saveSignatureFile($_POST['firma_cliente'] ?? '', "cliente");
    $firma_supervisor = saveSignatureFile($_POST['firma_supervisor'] ?? '', "supervisor");
    $firma_tecnico = saveSignatureFile($_POST['firma_tecnico'] ?? '', "tecnico");

    $stmt = $pdo->prepare("SELECT fotos FROM mantenimientos WHERE id=?");
    $stmt->execute([$mantenimiento_id]);
    $fotosExist = $stmt->fetchColumn();
    $fotosExistArr = $fotosExist ? json_decode($fotosExist, true) : [];
    if (!is_array($fotosExistArr)) $fotosExistArr = [];

    if (!empty($_FILES['fotos']['name'][0])) {
        $dir = __DIR__ . "/../../uploads/fotos/";
        if (!is_dir($dir)) mkdir($dir,0777,true);
        foreach ($_FILES['fotos']['tmp_name'] as $k => $tmp) {
            if (is_uploaded_file($tmp)) {
                $base = time()."_".bin2hex(random_bytes(4))."_".basename($_FILES['fotos']['name'][$k]);
                move_uploaded_file($tmp, $dir.$base);
                $fotosExistArr[] = $base;
            }
        }
    }

    $update = $pdo->prepare("UPDATE mantenimientos SET trabajos=?, actividades=?, observaciones=?, estado='finalizado' , parametros=?, firma_cliente=COALESCE(?,firma_cliente), firma_supervisor=COALESCE(?,firma_supervisor), firma_tecnico=COALESCE(?,firma_tecnico), fotos=? WHERE id=?");
    $update->execute([
        $trabajos,
        json_encode($actividades),
        $observaciones,
        json_encode($parametros),
        $firma_cliente,
        $firma_supervisor,
        $firma_tecnico,
        json_encode($fotosExistArr),
        $mantenimiento_id
    ]);

    generarPDF($pdo, $mantenimiento_id);

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    generarPDF($pdo, intval($_GET['id']));
} else {
    http_response_code(400);
    echo "Método inválido.";
}