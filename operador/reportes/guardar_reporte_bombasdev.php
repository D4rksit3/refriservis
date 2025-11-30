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
            tec.nombre AS tecnico,
            dig.nombre AS nombre_digitador
        FROM mantenimientos m
        LEFT JOIN clientes c ON c.id = m.cliente_id
        LEFT JOIN usuarios sup ON sup.id = m.digitador_id
        LEFT JOIN usuarios tec ON tec.id = m.operador_id
        LEFT JOIN usuarios dig ON dig.id = m.digitador_id
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

            // Altura total del header
            $cellH = 40;

            // ========================================
            // COLUMNA 1: LOGO
            // ========================================
            $this->SetDrawColor(13, 110, 253); // Azul
            $this->SetLineWidth(0.5);
            $this->Rect($left, $top, $logoW, $cellH);
            
            if (file_exists(__DIR__ . '/../../lib/logo.jpeg')) {
                $imgW = 32; 
                $imgH = 20;
                $imgX = $left + ($logoW - $imgW) / 2;
                $imgY = $top + ($cellH - $imgH) / 2;
                $this->Image(__DIR__ . '/../../lib/logo.jpeg', $imgX, $imgY, $imgW, $imgH);
            }

            // ========================================
            // COLUMNA 2: TÍTULO Y CONTACTO
            // ========================================
            $centerX = $left + $logoW;
            $this->SetXY($centerX, $top);

            // Título "FORMATO DE CALIDAD"
            $this->SetFont('Arial', 'B', 9);
            $this->SetFillColor(227, 242, 253); // Azul claro
            $this->SetTextColor(13, 110, 253); // Azul
            $this->Cell($centerW, 6, txt("FORMATO DE CALIDAD"), 1, 2, 'C', true);

            // Subtítulo principal
            $this->SetFont('Arial', 'B', 10);
            $this->SetTextColor(0, 0, 0);
            $subtitle = txt("CHECK LIST DE MANTENIMIENTO");
            $this->Cell($centerW, 5, $subtitle, 1, 2, 'C');
            
            $this->SetFont('Arial', 'B', 9);
            $subtitle2 = txt("PREVENTIVO DE EQUIPOS");
            $this->Cell($centerW, 5, $subtitle2, 1, 2, 'C');
            
            $subtitle3 = txt("BOMBA DE AGUA");
            $this->Cell($centerW, 5, $subtitle3, 1, 2, 'C');

            // Información de contacto
            $this->SetFont('Arial', '', 7);
            $this->SetTextColor(100, 100, 100);
            $contacto = txt("Oficina: (01) 6557907  |  Emergencias: +51 943 048 606");
            $this->Cell($centerW, 4, $contacto, 1, 2, 'C');
            
            $email = "ventas@refriservissac.com";
            $this->Cell($centerW, 4, $email, 1, 2, 'C');

            // Marco completo de la columna central
            $this->Rect($centerX, $top, $centerW, $cellH);

            // ========================================
            // COLUMNA 3: NÚMERO DE FORMATO
            // ========================================
            $numX = $centerX + $centerW;
            $this->SetXY($numX, $top);

            // Título
            $this->SetFont('Arial', 'B', 8);
            $this->SetFillColor(227, 242, 253);
            $this->SetTextColor(13, 110, 253);
            $this->Cell($numW, 6, txt("FORMATO"), 1, 2, 'C', true);

            // Número
            $this->SetFont('Arial', 'B', 11);
            $this->SetTextColor(0, 0, 0);
            $this->SetY($top + ($cellH / 2) - 3);
            $this->SetX($numX);
            $numeroFormato = "N" . chr(176) . str_pad($this->mantenimientoId ?? '', 6, "0", STR_PAD_LEFT);
            $this->Cell($numW, 8, $numeroFormato, 0, 2, 'C');

            // Marco de la columna de número
            $this->Rect($numX, $top, $numW, $cellH);

            // Espaciado después del header
            $this->SetY($top + $cellH + 6);
            $this->SetTextColor(0, 0, 0);
        }

        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(100, 100, 100);

            date_default_timezone_set('America/Lima');
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

    // Calcular ancho útil
    $pageWidth = $pdf->GetPageWidth();
    $leftMargin = $pdf->getLeftMargin();
    $rightMargin = $pdf->getRightMargin();
    $usableW = $pageWidth - $leftMargin - $rightMargin;

    // ==========================================
    // DATOS DEL CLIENTE
    // ==========================================
    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(13, 110, 253);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, txt("DATOS DEL CLIENTE"), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);

    // Configuración de columnas
    $col1W = 30;  // Etiqueta
    $col2W = 65;  // Valor
    $col3W = 30;  // Etiqueta
    $col4W = 65;  // Valor

    // Fila 1: Cliente | Responsable
    $pdf->SetFont('Arial','B',8);
    $pdf->SetFillColor(227, 242, 253);
    $pdf->Cell($col1W, 6, txt("Cliente:"), 1, 0, 'L', true);
    $pdf->SetFont('Arial','',8);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Cell($col2W, 6, txt($m['cliente'] ?? ''), 1, 0, 'L');
    
    $pdf->SetFont('Arial','B',8);
    $pdf->SetFillColor(227, 242, 253);
    $pdf->Cell($col3W, 6, txt("Responsable:"), 1, 0, 'L', true);
    $pdf->SetFont('Arial','',8);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Cell($col4W, 6, txt($m['responsable'] ?? ''), 1, 1, 'L');

    // Fila 2: Dirección (span completo)
    $pdf->SetFont('Arial','B',8);
    $pdf->SetFillColor(227, 242, 253);
    $pdf->Cell($col1W, 6, txt("Dirección:"), 1, 0, 'L', true);
    $pdf->SetFont('Arial','',8);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Cell($col2W + $col3W + $col4W, 6, txt($m['direccion'] ?? ''), 1, 1, 'L');

    // Fila 3: Teléfono | Fecha
    $pdf->SetFont('Arial','B',8);
    $pdf->SetFillColor(227, 242, 253);
    $pdf->Cell($col1W, 6, txt("Teléfono:"), 1, 0, 'L', true);
    $pdf->SetFont('Arial','',8);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Cell($col2W, 6, txt($m['telefono'] ?? ''), 1, 0, 'L');
    
    $pdf->SetFont('Arial','B',8);
    $pdf->SetFillColor(227, 242, 253);
    $pdf->Cell($col3W, 6, txt("Fecha:"), 1, 0, 'L', true);
    $pdf->SetFont('Arial','',8);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Cell($col4W, 6, txt($m['fecha'] ?? date('Y-m-d')), 1, 1, 'L');

    // Fila 4: MT - Digitador (span completo)
    $pdf->SetFont('Arial','B',8);
    $pdf->SetFillColor(227, 242, 253);
    $pdf->Cell($col1W, 6, txt("MT - Digitador:"), 1, 0, 'L', true);
    $pdf->SetFont('Arial','',8);
    $pdf->SetFillColor(255, 255, 255);
    $nombreDigitador = !empty($m['nombre_digitador']) ? $m['nombre_digitador'] : '';
    $pdf->Cell($col2W + $col3W + $col4W, 6, txt($nombreDigitador), 1, 1, 'L');

    $pdf->Ln(4);

    // ==========================================
    // EQUIPOS
    // ==========================================
    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(13, 110, 253);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, txt("DATOS DE IDENTIFICACIÓN DE LOS EQUIPOS"), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    
    // Encabezado de tabla
    $pdf->SetFont('Arial','B',8);
    $pdf->SetFillColor(227, 242, 253);
    $pdf->Cell(10, 7, "#", 1, 0, 'C', true);
    $pdf->Cell(40, 7, txt("Identificador / Nombre"), 1, 0, 'C', true);
    $pdf->Cell(40, 7, txt("Marca"), 1, 0, 'C', true);
    $pdf->Cell(40, 7, txt("Modelo"), 1, 0, 'C', true);
    $pdf->Cell(35, 7, txt("Ubicación"), 1, 0, 'C', true);
    $pdf->Cell(25, 7, txt("Voltaje"), 1, 1, 'C', true);
    
    $pdf->SetFont('Arial','',8);

    // Filas de equipos
    for ($i = 1; $i <= 7; $i++) {
        if ($i % 2 == 0) {
            $pdf->SetFillColor(248, 249, 250);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }

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

            $pdf->SetXY($x, $y);
            $pdf->Cell($colAnchos[0], $cellHeight, $i, 1, 0, 'C', true);

            $pdf->SetXY($x + $colAnchos[0], $y);
            $pdf->MultiCell($colAnchos[1], $lineHeight, utf8_decode($texto), 1, 'L', true);

            $newY = $pdf->GetY();

            $pdf->SetXY($x + $colAnchos[0] + $colAnchos[1], $y);
            $pdf->Cell($colAnchos[2], $cellHeight, txt($eq['Marca'] ?? ''), 1, 0, 'L', true);
            $pdf->Cell($colAnchos[3], $cellHeight, txt($eq['Modelo'] ?? ''), 1, 0, 'L', true);
            $pdf->Cell($colAnchos[4], $cellHeight, txt($eq['Ubicacion'] ?? ''), 1, 0, 'L', true);
            $pdf->Cell($colAnchos[5], $cellHeight, txt($eq['Voltaje'] ?? ''), 1, 1, 'L', true);

            $pdf->SetY(max($newY, $y + $cellHeight));
        } else {
            $pdf->Cell(10, 7, "", 1, 0, 'C', true);
            $pdf->Cell(40, 7, "", 1, 0, 'L', true);
            $pdf->Cell(40, 7, "", 1, 0, 'L', true);
            $pdf->Cell(40, 7, "", 1, 0, 'L', true);
            $pdf->Cell(35, 7, "", 1, 0, 'L', true);
            $pdf->Cell(25, 7, "", 1, 1, 'L', true);
        }
    }

    $pdf->Ln(4);

    // ==========================================
    // PARÁMETROS
    // ==========================================
    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(13, 110, 253);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, txt("PARÁMETROS DE FUNCIONAMIENTO (Antes / Después)"), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);

    $labels = [
        'Corriente eléctrica nominal (Amperios) L1',
        'Corriente L2','Corriente L3',
        'Tensión eléctrica nominal V1','Tensión V2','Tensión V3',
        'Presión de descarga (PSI)','Presión de succión (PSI)'
    ];

    $labelW = 55;
    $totalCols = 7 * 2;
    $colW = ($usableW - $labelW) / $totalCols;

    // Encabezado
    $pdf->SetFont('Arial','B',7);
    $pdf->SetFillColor(227, 242, 253);
    $pdf->Cell($labelW, 7, txt("Medida"), 1, 0, 'C', true);
    for ($i = 1; $i <= 7; $i++) {
        $pdf->Cell($colW, 7, txt("Eq{$i} A"), 1, 0, 'C', true);
        $pdf->Cell($colW, 7, txt("Eq{$i} D"), 1, 0, 'C', true);
    }
    $pdf->Ln();

    $pdf->SetFont('Arial','',7);
    $rowCounter = 0;
    
    foreach ($labels as $label) {
        if ($rowCounter % 2 == 0) {
            $pdf->SetFillColor(248, 249, 250);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }

        $pdf->Cell($labelW, 7, txt($label), 1, 0, 'L', true);
        $hash = md5($label);
        for ($i = 1; $i <= 7; $i++) {
            $antes = $parametrosStored[$hash][$i]['antes'] ?? ($parametrosStored[$label][$i]['antes'] ?? "");
            $desp = $parametrosStored[$hash][$i]['despues'] ?? ($parametrosStored[$label][$i]['despues'] ?? "");
            $pdf->Cell($colW, 7, txt((string)$antes), 1, 0, 'C', true);
            $pdf->Cell($colW, 7, txt((string)$desp), 1, 0, 'C', true);
        }
        $pdf->Ln();
        $rowCounter++;
    }
    $pdf->Ln(4);

    // ==========================================
    // ACTIVIDADES - NUEVA PÁGINA
    // ==========================================
    $pdf->AddPage();

    $nameW = 80;
    $dayW = 10;
    $freqW = 8;
    $lineH = 5;
    $bottomMargin = 15;
    $totalWidth = $nameW + ($dayW * 7) + ($freqW * 4);

    // Título
    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(13, 110, 253);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell($totalWidth, 8, txt("ACTIVIDADES A REALIZAR"), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);

    // Cabecera
    $pdf->SetFont('Arial','B',7);
    $pdf->SetFillColor(227, 242, 253);
    $pdf->Cell($nameW, 6, txt("Actividad"), 1, 0, 'C', true);
    for ($i = 1; $i <= 7; $i++) {
        $pdf->Cell($dayW, 6, str_pad($i, 2, '0', STR_PAD_LEFT), 1, 0, 'C', true);
    }
    $pdf->Cell($freqW, 6, "B", 1, 0, 'C', true);
    $pdf->Cell($freqW, 6, "T", 1, 0, 'C', true);
    $pdf->Cell($freqW, 6, "S", 1, 0, 'C', true);
    $pdf->Cell($freqW, 6, "A", 1, 1, 'C', true);

    // Lista de actividades
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
        $pdf->SetFont('Arial','B',10);
        $pdf->SetFillColor(13, 110, 253);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($totalWidth, 8, txt("ACTIVIDADES A REALIZAR"), 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->SetFont('Arial','B',7);
        $pdf->SetFillColor(227, 242, 253);
        $pdf->Cell($nameW, 6, txt("Actividad"), 1, 0, 'C', true);
        for ($i = 1; $i <= 7; $i++) {
            $pdf->Cell($dayW, 6, str_pad($i, 2, '0', STR_PAD_LEFT), 1, 0, 'C', true);
        }
        $pdf->Cell($freqW, 6, "B", 1, 0, 'C', true);
        $pdf->Cell($freqW, 6, "T", 1, 0, 'C', true);
        $pdf->Cell($freqW, 6, "S", 1, 0, 'C', true);
        $pdf->Cell($freqW, 6, "A", 1, 1, 'C', true);
        
        $pdf->SetFont('Arial','',7);
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

        $pageHeight = 297;
        if ($pdf->GetY() + $h + $bottomMargin > $pageHeight) {
            $pdf->AddPage();
            $printHeader();
            $rowCounter = 0;
        }

        if ($rowCounter % 2 == 0) {
            $pdf->SetFillColor(248, 249, 250);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }

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

    // ==========================================
    // TRABAJOS REALIZADOS
    // ==========================================
    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(13, 110, 253);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, txt("TRABAJOS REALIZADOS"), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetFont('Arial','',9);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->MultiCell(0, 6, txt($m['trabajos'] ?? ''), 1, 'L', true);
    $pdf->Ln(3);

    // ==========================================
    // OBSERVACIONES
    // ==========================================
    if (!empty($m['observaciones'])) {
        $observaciones = json_decode($m['observaciones'], true);

        if (is_array($observaciones)) {
            foreach ($observaciones as $obs) {
                $pdf->AddPage();
                
                // Título de la sección
                $pdf->SetFont('Arial','B',11);
                $pdf->SetFillColor(13, 110, 253);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell(0, 8, txt("OBSERVACIONES Y RECOMENDACIONES"), 1, 1, 'C', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Ln(2);

                $xStart = 10;
                $yStart = $pdf->GetY();
                $pdf->SetDrawColor(200, 200, 200);
                $pdf->SetLineWidth(0.3);

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

                $pdf->SetFont('Arial','B',10);
                $pdf->SetFillColor(227, 242, 253);
                $pdf->Cell(0, 7, utf8_decode("Equipo: " . $codigoEquipo . " - " . $nombreEquipo), 0, 1, 'L', true);
                $pdf->Ln(1);

                // Observación
                $pdf->SetFont('Arial','',9);
                $texto = isset($obs['texto']) ? $obs['texto'] : '';
                $pdf->MultiCell(0, 5, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $texto), 0, 'L');
                $pdf->Ln(3);

                // Imágenes (SIN MARCO y sin superposición)
                if (!empty($obs['imagenes']) && is_array($obs['imagenes'])) {
                    $maxWidth = 85;
                    $maxHeight = 65;
                    $margin = 10;
                    $count = 0;
                    $maxHeightInRow = 0;

                    foreach ($obs['imagenes'] as $imgPath) {
                        $realPath = __DIR__ . '/' . $imgPath;

                        if (file_exists($realPath)) {
                            $correctedPath = corregirOrientacion($realPath);
                            [$width, $height] = getimagesize($correctedPath);

                            $ratio = min($maxWidth / $width, $maxHeight / $height);
                            $w_mm = $width * $ratio;
                            $h_mm = $height * $ratio;

                            if ($count % 2 == 0) {
                                $rowStartY = $pdf->GetY();
                                $maxHeightInRow = 0;
                            }

                            $x = 15 + ($count % 2) * ($maxWidth + $margin);
                            $y = $rowStartY;

                            if ($y + $h_mm > 270) {
                                $pdf->AddPage();
                                $rowStartY = $pdf->GetY();
                                $y = $rowStartY;
                                $count = 0;
                                $maxHeightInRow = 0;
                            }

                            // Imagen SIN marco
                            $pdf->Image($correctedPath, $x, $y, $w_mm, $h_mm);

                            $maxHeightInRow = max($maxHeightInRow, $h_mm);

                            if ($count % 2 == 1) {
                                $pdf->SetY($rowStartY + $maxHeightInRow + 8);
                            }

                            $count++;
                        }
                    }

                    if ($count % 2 == 1) {
                        $pdf->SetY($rowStartY + $maxHeightInRow + 8);
                    }
                }

                $endY = $pdf->GetY();
                $pdf->SetDrawColor(200, 200, 200);
                $pdf->Rect($xStart, $yStart, 190 - $xStart, $endY - $yStart);
                $pdf->Ln(4);
            }
        }
    }

    // ==========================================
    // FIRMAS
    // ==========================================
    $pdf->AddPage();
    
    $pdf->SetFont('Arial','B',11);
    $pdf->SetFillColor(13, 110, 253);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, txt("FIRMAS Y CONFORMIDAD"), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(3);

    $firmaBase = __DIR__ . "/../../uploads/firmas/";
    $sigW = ($usableW - 4) / 3;
    $sigH = 30;
    
    $sigFiles = [
        $m['firma_cliente'] ?? null,
        $m['firma_supervisor'] ?? null,
        $m['firma_tecnico'] ?? null
    ];
    
    foreach ($sigFiles as $sfile) {
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->SetDrawColor(200, 200, 200);
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

    $pdf->SetFont('Arial','B',9);
    $pdf->SetFillColor(227, 242, 253);
    $pdf->Cell($sigW, 7, txt("Firma Cliente"), 1, 0, 'C', true);
    $pdf->Cell($sigW, 7, txt("Firma Supervisor"), 1, 0, 'C', true);
    $pdf->Cell($sigW, 7, txt("Firma Técnico"), 1, 1, 'C', true);

    $pdf->SetFont('Arial','',8);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Cell($sigW, 6, txt($m['nombre_cliente'] ?? ''), 1, 0, 'C');
    $pdf->Cell($sigW, 6, txt($m['nombre_supervisor'] ?? ''), 1, 0, 'C');
    $pdf->Cell($sigW, 6, txt($m['tecnico'] ?? ''), 1, 1, 'C');

    // Forzar descarga
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
    $actividades = $_POST['actividades'] ?? [];
    $nombre_digitador = $_POST['nombre_digitador'] ?? null;

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

    $update = $pdo->prepare("UPDATE mantenimientos SET 
        trabajos=?, 
        actividades=?, 
        observaciones=?, 
        estado='finalizado', 
        parametros=?, 
        nombre_digitador=?,
        firma_cliente=COALESCE(?,firma_cliente), 
        firma_supervisor=COALESCE(?,firma_supervisor), 
        firma_tecnico=COALESCE(?,firma_tecnico), 
        fotos=? 
        WHERE id=?");
    $update->execute([
        $trabajos,
        json_encode($actividades),
        $observaciones,
        json_encode($parametros),
        $nombre_digitador,
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