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
    $this->SetFont('Arial','B',12);
    $text = txt("CHECK LIST DE MANTENIMIENTO PREVENTIVO DE EQUIPOS - CORTINAS DE AIRE");

    // Altura de línea
    $lineH = 6;
    $nbLines = substr_count($text, "\n") + 1;
    $centerH = 7 + ($nbLines * $lineH) + 8;   // titulo + subtitulo + contacto
    $cellH = max(35, $centerH);

    // -------------------------------
    // Logo
    // -------------------------------
    $this->Rect($left, $top, $logoW, $cellH);
    if (file_exists(__DIR__ . '/../../lib/logo.jpeg')) {
        $imgW = 30; $imgH = 18;
        $imgX = $left + ($logoW - $imgW) / 2;
        $imgY = $top + ($cellH - $imgH) / 2;
        $this->Image(__DIR__ . '/../../lib/logo.jpeg', $imgX, $imgY, $imgW, $imgH);
    }

    // -------------------------------
    // Bloque central
    // -------------------------------
    $this->SetXY($left + $logoW, $top);

    // Título principal
    $this->SetFont('Arial', 'B', 10);
    $this->SetFillColor(207, 226, 243);
    $this->Cell($centerW, 7, txt("FORMATO DE CALIDAD"), 1, 2, 'C', true);

    // Subtítulo
    $this->SetFont('Arial','B',12);
    $this->MultiCell($centerW, $lineH, $text, 0, 'C');

    // Guardar altura usada hasta aquí
    $yAfterTitle = $this->GetY();

    // Contacto en la parte inferior del cuadro central
    $contacto = txt("Oficina: (01) 6557907  |  Emergencias: +51 943 048 606  |  ventas@refriservissac.com");
    $this->SetXY($left + $logoW, $top + $cellH - 8);
    $this->SetFont('Arial','',8);
    $this->Cell($centerW, 8, $contacto, 1, 2, 'C');

    // Dibujar marco completo central
    $this->Rect($left + $logoW, $top, $centerW, $cellH);

    // -------------------------------
    // Número
    // -------------------------------
    $this->SetXY($left + $logoW + $centerW, $top);
    $this->Rect($this->GetX(), $this->GetY(), $numW, $cellH);

    $this->SetFont('Arial','',9);
    $this->SetXY($this->GetX(), $this->GetY() + ($cellH/2) - 3);
    $this->Cell($numW, 6, "001-N" . chr(176) . str_pad($this->mantenimientoId ?? '', 6, "0", STR_PAD_LEFT), 0, 1, 'C');

    // -------------------------------
    // Espaciado
    // -------------------------------
    $this->Ln(6);
    $this->SetY($top + $cellH + 10);
}

    public function Footer()
        {
            // Posición del footer (15 mm del final)
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);

            // Zona horaria y fecha/hora actual
            date_default_timezone_set('America/Lima');
            $fechaHora = date('d/m/Y H:i:s');

            // Usuario generador del PDF
            $usuario = isset($this->user) ? $this->user : 'Desconocido';

            // Texto del pie
            $texto = utf8_decode("Página " . $this->PageNo() . " de {nb}    |    Generado por: $usuario    |    {$this->fechaModificacion}");

            // Mostrar centrado
            $this->Cell(0, 10, $texto, 0, 0, 'C');
        }


    }

    // Construir PDF
    $pdf = new PDF('P','mm','A4');
    $pdf->mantenimientoId = $m['id'];
    $pdf->user = $userName;
    $pdf->fechaModificacion = $fechaModificacion;
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial','',9);

    // ---------- DATOS DEL CLIENTE ----------
    $pdf->SetFont('Arial','B',9);
    $pdf->Cell(0,7, txt("Datos del Cliente"), 1, 1, 'C');

    // Fila: Cliente + Supervisor
    $pdf->SetFont('Arial','',9);
    $pdf->Cell(25,7, txt("Cliente:"), 1, 0);
    $pdf->Cell(90,7, txt($m['cliente'] ?? ''), 1, 0);
    $pdf->Cell(25,7, txt("Supervisor:"), 1, 0);
    $pdf->Cell(50,7, txt($m['supervisor'] ?? ''), 1, 1);

    // Fila: Dirección
    $pdf->Cell(25,7, txt("Dirección:"), 1, 0);
    $pdf->Cell(165,7, txt($m['direccion'] ?? ''), 1, 1);

    // Fila: Responsable + Teléfono
    $pdf->Cell(25,7, txt("Responsable:"), 1, 0);
    $pdf->Cell(90,7, txt($m['responsable'] ?? ''), 1, 0);
    $pdf->Cell(25,7, txt("Teléfono:"), 1, 0);
    $pdf->Cell(50,7, txt($m['telefono'] ?? ''), 1, 1);

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

    $labels = [
        'Corriente eléctrica nominal (Amperios) L1',
        'Corriente L2',
        'Tensión eléctrica nominal V1','Tensión V2'
    ];

    $pageWidth   = $pdf->GetPageWidth();
    $leftMargin  = $pdf->getLeftMargin();
    $rightMargin = $pdf->getRightMargin();
    $usableW     = $pageWidth - $leftMargin - $rightMargin;
    // Ajuste de anchos
    $labelW  = 55; // ancho para la columna de texto
    $totalCols = 7 * 2; // Eq1A, Eq1D, Eq2A, Eq2D, etc.
    $colW = ($usableW - $labelW) / $totalCols; // ajusta todo el ancho exacto

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
            $pdf->Cell($colW,7, txt((string)$antes), 1, 0);
            $pdf->Cell($colW,7, txt((string)$desp), 1, 0);
        }
        $pdf->Ln();
    }
    $pdf->Ln(4);

 

      // ---------- PARÁMETROS ----------
   /*  $pdf->SetFont('Arial','B',9);
    $pdf->Cell(0,7,txt("Parámetros"),1,1,'C');
    $pdf->SetFont('Arial','',9);
    foreach($parametros as $k=>$v) {
        $pdf->Cell(0,6,txt("$k: $v"),1,1);
    }
    $pdf->Ln(3); */
 
    // ---------- NUEVA PÁGINA ----------




        
    // ---------- NUEVA PÁGINA ----------
 
   // ---------- ACTIVIDADES A REALIZAR ----------
$pdf->AddPage();

$nameW = 80;
$dayW  = 10;
$freqW = 8;
$lineH = 5;
$bottomMargin = 15;

// Calcular ancho total exacto
$totalWidth = $nameW + ($dayW * 7) + ($freqW * 4);

// Título alineado con el cuadro
$pdf->SetFont('Arial','B',9);
$pdf->Cell($totalWidth,7, txt("ACTIVIDADES A REALIZAR"), 1, 1, 'C');

// Cabecera
$pdf->SetFont('Arial','B',7);
$pdf->Cell($nameW,6, txt("Actividad"), 1, 0, 'C');
for ($i = 1; $i <= 7; $i++) {
    $pdf->Cell($dayW,6, str_pad($i,2,'0',STR_PAD_LEFT), 1, 0, 'C');
}
$pdf->Cell($freqW,6,"B",1,0,'C');
$pdf->Cell($freqW,6,"T",1,0,'C');
$pdf->Cell($freqW,6,"S",1,0,'C');
$pdf->Cell($freqW,6,"A",1,1,'C');
// Lista fija de actividades
$actividadesList = [
        "Desmontaje de equipo",
        "Limpieza de carcasa de cortina con paño húmedo",
        "Limpieza de motor con aspiradora",
        "Lavado de turbina",
        "Lubricación de componentes mecánicos",
        "Limpieza de parrillas laterales",
        "Verificación y ajuste de componentes electromecánicos",
        "Limpieza de componentes electrónicos",
        "Pruebas de funcionamiento y toma de parámetros"
];

// Decodificar JSON de la BD
$actividadesBD = json_decode($m['actividades'], true);
if (!is_array($actividadesBD)) {
    $actividadesBD = [];
}

// Recorremos la lista fija
foreach ($actividadesList as $idx => $nombre) {
    $actividadBD = $actividadesBD[$idx] ?? ["dias"=>[], "frecuencia"=>null];

    // Normalizar dias
    $diasMarcados = $actividadBD['dias'] ?? [];
    if (!is_array($diasMarcados)) {
        $diasMarcados = json_decode($diasMarcados, true) ?: [];
    }

    // Guardar posición inicial
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // MultiCell SOLO para el nombre (80 de ancho)
    $pdf->MultiCell(80,5, txt($nombre),1,'L');

    // Altura real que ocupó el texto
    $altura = $pdf->GetY() - $y;

    // Regresar posición a la derecha del texto
    $pdf->SetXY($x+80,$y);

    // Columnas de días (01-07)
    for ($i=1;$i<=7;$i++) {
        $marca = in_array($i, $diasMarcados) ? "X" : "";
        $pdf->Cell(10,$altura, $marca, 1, 0, 'C');
    }

    // Columnas de frecuencia (B, T, S, A)
    foreach (["B","T","S","A"] as $f) {
        $marca = ($actividadBD['frecuencia'] === $f) ? "X" : "";
        $pdf->Cell(8,$altura, $marca, 1, 0, 'C');
    }

    // Bajar cursor a la siguiente fila
    $pdf->Ln();
}

$pdf->Ln(3);



// ---------- TRABAJOS ----------
    $pdf->SetFont('Arial','B',9);
    $pdf->MultiCell(0,7, txt("Trabajos Realizados:\n" . ($m['trabajos'] ?? '')), 1);
    $pdf->Ln(2);

    // ---------- OBSERVACIONES ----------
   /* $pdf->AddPage();
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,8, utf8_decode("Observaciones y Recomendaciones:"), 0, 1, 'L');
    $pdf->Ln(2); */

    if (!empty($m['observaciones'])) {
        $observaciones = json_decode($m['observaciones'], true);

        if (is_array($observaciones)) {
            foreach ($observaciones as $obs) {
                $pdf->AddPage();
                // === Dibuja el marco general ===
                $xStart = 10;
                $yStart = $pdf->GetY();
                $pdf->SetDrawColor(180,180,180);
                $pdf->SetLineWidth(0.2);

                // Guarda posición para calcular alto dinámico
                $startY = $pdf->GetY();

                // --- Contenido ---
                $pdf->SetFont('Arial','B',9);

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

                $pdf->Cell(0,6, utf8_decode("Equipo: " . $codigoEquipo . " - " . $nombreEquipo), 0, 1, 'L');

                $pdf->SetFont('Arial','',9);
                $texto = isset($obs['texto']) ? $obs['texto'] : '';
                $pdf->MultiCell(0,6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', "Observación: " . $texto), 0, 'L');
                $pdf->Ln(2);

                // --- Imágenes (2 por fila) ---
                if (!empty($obs['imagenes']) && is_array($obs['imagenes'])) {
                    $maxWidth = 60;
                    $maxHeight = 45;
                    $margin = 10;
                    $count = 0;

                    foreach ($obs['imagenes'] as $imgPath) {
                        $realPath = __DIR__ . '/' . $imgPath;
                        if (file_exists($realPath)) {
                            $x = 10 + ($count % 2) * ($maxWidth + $margin);
                            $y = $pdf->GetY();

                            $pdf->Image($realPath, $x, $y, $maxWidth, $maxHeight);

                            // Cada 2 imágenes, salta de línea
                            if ($count % 2 == 1) {
                                $pdf->Ln($maxHeight + 5);
                            }
                            $count++;
                        } else {
                            $pdf->SetFont('Arial','I',8);
                            $pdf->Cell(0,5, utf8_decode("Imagen no encontrada: $imgPath"), 0, 1, 'L');
                        }
                    }

                    // Si quedó una sola imagen sin pareja, baja de línea igual
                    if ($count % 2 == 1) {
                        $pdf->Ln($maxHeight + 5);
                    }
                }

                // --- Fin del cuadro ---
                $endY = $pdf->GetY();
                $pdf->Rect($xStart, $yStart, 190 - $xStart, $endY - $yStart);
                $pdf->Ln(6);
            }
        } else {
            $pdf->SetFont('Arial','I',9);
            $pdf->Cell(0,6, utf8_decode("No hay observaciones registradas."), 0, 1);
        }
    } else {
        $pdf->SetFont('Arial','I',9);
        $pdf->Cell(0,6, utf8_decode("No hay observaciones registradas."), 0, 1);
    }

    $pdf->Ln(4);







    // ---------- FOTOS ----------
    if (!empty($fotos)) {
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(0,7, txt("Fotos del Servicio"), 1, 1, 'C');
        $pdf->Ln(2);

        $colsPerRow = 3;
        $imgCellW = ($usableW - 4) / $colsPerRow;
        $imgCellH = 50;
        $colCounter = 0;
        foreach ($fotos as $foto) {
            $x = $pdf->GetX();
            $y = $pdf->GetY();
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
                $pdf->Cell($imgCellW, 6, txt("[No encontrada]"), 0, 0, 'C');
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
    $pdf->Ln(4);
    $pdf->SetFont('Arial','',9);

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

    $pdf->Cell($sigW, 8, txt("Firma Cliente"), 1, 0, 'C');
    $pdf->Cell($sigW, 8, txt("Firma Supervisor"), 1, 0, 'C');
    $pdf->Cell($sigW, 8, txt("Firma Técnico"), 1, 1, 'C');

    // Nombres debajo de las firmas
    $pdf->SetFont('Arial','I',8);
    $pdf->Cell($sigW, 6, txt($m['nombre_cliente'] ?? ''), 0, 0, 'C');
    $pdf->Cell($sigW, 6, txt($m['nombre_supervisor'] ?? ''), 0, 0, 'C');
    $pdf->Cell($sigW, 6, txt($m['tecnico'] ?? ''), 0, 1, 'C');

    // ----------------- Forzar descarga -----------------
    if (ob_get_length()) {
        @ob_end_clean();
    }
    $fileName = "reporte_cortinas_{$m['id']}.pdf";
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
