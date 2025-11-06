<?php
// guardar_reporte_servicio.php
// --- Descarga inmediata del PDF con diseño de "FORMATO DE CALIDAD" ---
// Debe colocarse sin ningún output previo (sin espacios antes de <?php)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


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
        $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if ($userRow && !empty($userRow['nombre'])) {
            $userName = $userRow['nombre'];
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
            global $m;
            $left = $this->GetX();
            $top = $this->GetY();

            // Logo
            $cellW = 40; $cellH = 25;
            $this->Rect($left, $top, $cellW, $cellH);
            if (file_exists(__DIR__ . '/../../lib/logo.jpeg')) {
                $imgW = 30; $imgH = 18;
                $imgX = $left + ($cellW - $imgW) / 2;
                $imgY = $top + ($cellH - $imgH) / 2;
                $this->Image(__DIR__ . '/../../lib/logo.jpeg', $imgX, $imgY, $imgW, $imgH);
            }
            $this->SetXY($left + $cellW + 2, $top);

            // Título
            $this->SetFont('Arial', 'B', 10);
            $this->SetFillColor(207, 226, 243);
            $this->Cell(110, 7, txt("FORMATO DE CALIDAD"), 1, 1, 'C', true);
            $this->SetX($left + $cellW + 2);
            $this->SetFont('Arial','B',12);
            $this->Cell(110, 10, txt("REPORTE DE SERVICIO TÉCNICO"), 1, 1, 'C');
            $this->SetX($left + $cellW + 2);
            $this->SetFont('Arial','',8);
            $this->Cell(110, 8, txt("Oficina: (01) 6557907  |  Emergencias: +51 943 048 606  |  ventas@refriservissac.com"), 1, 0, 'C');

            // Número
            $this->SetXY($left + $cellW + 2 + 110 + 4, $top);
            $this->SetFont('Arial','',9);
            $numCellW = 40; $numCellH = 25;
            $this->Rect($this->GetX(), $this->GetY(), $numCellW, $numCellH);
            $this->SetXY($this->GetX(), $this->GetY() + 6);
            $this->Cell($numCellW, 6, "001-N" . chr(176) . str_pad($this->mantenimientoId ?? '', 6, "0", STR_PAD_LEFT), 0, 1, 'C');

            $this->Ln(6);

            // 👉 ESTA LÍNEA ES CLAVE: baja el cursor debajo del header
            $this->SetY($top + $cellH + 15);
        }
        /* public function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,'Página '.$this->PageNo().'/{nb}',0,0,'C');
        } */
       

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
            $texto = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', "Página " . $this->PageNo() . " de {nb}    |    Generado por: $usuario    |    {$this->fechaModificacion}");

            // Mostrar centrado
            $this->Cell(0, 10, $texto, 0, 0, 'C');
        }




    }



    // Construir PDF
    $pdf = new PDF('P','mm','A4');
    $pdf->mantenimientoId = $m['id'];
    $pdf->user = $userName;
    $pdf->fechaModificacion = $fechaModificacion;
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
    // --- ENCABEZADO DE TABLA ---
    $pdf->SetFont('Arial','B',8);
    $pdf->Cell(10,7,"#",1,0,'C');
    $pdf->Cell(40,7, txt("Identificador / Nombre"), 1, 0, 'C');
    $pdf->Cell(40,7, txt("Marca"), 1, 0, 'C');
    $pdf->Cell(40,7, txt("Modelo"), 1, 0, 'C');
    $pdf->Cell(35,7, txt("Ubicación"), 1, 0, 'C');
    $pdf->Cell(25,7, txt("Voltaje"), 1, 1, 'C');
    $pdf->SetFont('Arial','',8);

    // --- FILAS ---
    for ($i = 1; $i <= 7; $i++) {
        if (!empty($equipos[$i])) {
            $eq = $equipos[$i];
            $identificador = $eq['Identificador'] ?? '';
            $nombre = $eq['Nombre'] ?? '';
            $texto = trim($identificador . ' - ' . $nombre);

            // Configuración general
            $lineHeight = 6;
            $colAnchos = [10, 40, 40, 40, 35, 25];

            // Posición inicial de la fila
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            // --- 1️⃣ Calcular altura real del texto en la celda "Identificador / Nombre" ---
            $pdf->SetXY($x + $colAnchos[0], $y); // después de la columna #
            $pdf->MultiCell($colAnchos[1], $lineHeight, utf8_decode($texto), 0, 'L');
            $alturaTexto = $pdf->GetY() - $y;

            // Altura final de la fila
            $cellHeight = max($lineHeight, $alturaTexto);

            // --- 2️⃣ Dibujar las celdas ---
            // Celda #
            $pdf->SetXY($x, $y);
            $pdf->Cell($colAnchos[0], $cellHeight, $i, 1, 0, 'C');

            // Celda Identificador / Nombre
            $pdf->SetXY($x + $colAnchos[0], $y);
            $pdf->MultiCell($colAnchos[1], $lineHeight, utf8_decode($texto), 1, 'L');

            // Guardar nueva Y luego del MultiCell
            $newY = $pdf->GetY();

            // Otras celdas alineadas horizontalmente
            $pdf->SetXY($x + $colAnchos[0] + $colAnchos[1], $y);
            $pdf->Cell($colAnchos[2], $cellHeight, txt($eq['marca'] ?? ''), 1, 0, 'L');
            $pdf->Cell($colAnchos[3], $cellHeight, txt($eq['modelo'] ?? ''), 1, 0, 'L');
            $pdf->Cell($colAnchos[4], $cellHeight, txt($eq['ubicacion'] ?? ''), 1, 0, 'L');
            $pdf->Cell($colAnchos[5], $cellHeight, txt($eq['voltaje'] ?? ''), 1, 1, 'L');

            // Asegurar que el cursor quede al final de la fila
            $pdf->SetY(max($newY, $y + $cellHeight));

        } else {
            // --- Fila vacía ---
            $pdf->Cell(10,7,"",1,0);
            $pdf->Cell(40,7,"",1,0);
            $pdf->Cell(40,7,"",1,0);
            $pdf->Cell(40,7,"",1,0);
            $pdf->Cell(35,7,"",1,0);
            $pdf->Cell(25,7,"",1,1);
        }
    }


    
    $pdf->Ln(4);

    // ---------- PARÁMETROS ----------
    $pdf->SetFont('Arial','B',9);
    $pdf->Cell(0,7, txt("Parámetros de Funcionamiento (Antes / Después)"), 1, 1, 'C');

    // Etiquetas
    $labels = [
        'Corriente eléctrica nominal (Amperios) L1',
        'Corriente L2', 'Corriente L3',
        'Tensión eléctrica nominal V1', 'Tensión V2', 'Tensión V3',
        'Presión de descarga (PSI)', 'Presión de succión (PSI)'
    ];

    // Medidas de la página
    $pageWidth   = $pdf->GetPageWidth();
    $leftMargin  = $pdf->getLeftMargin();
    $rightMargin = $pdf->getRightMargin();
    $usableW     = $pageWidth - $leftMargin - $rightMargin;

    // Ajuste de anchos
    $labelW  = 55; // ancho para la columna de texto
    $totalCols = 7 * 2; // Eq1A, Eq1D, Eq2A, Eq2D, etc.
    $colW = ($usableW - $labelW) / $totalCols; // ajusta todo el ancho exacto

    // Encabezado de columnas
    $pdf->SetFont('Arial','B',7);
    $pdf->Cell($labelW,7, txt("Medida"), 1, 0, 'C');
    for ($i = 1; $i <= 7; $i++) {
        $pdf->Cell($colW,7, txt("Eq{$i} A"), 1, 0, 'C');
        $pdf->Cell($colW,7, txt("Eq{$i} D"), 1, 0, 'C');
    }
    $pdf->Ln();

    // Cuerpo de la tabla
    $pdf->SetFont('Arial','',7);
    foreach ($labels as $label) {
        $pdf->Cell($labelW,7, txt($label), 1, 0, 'L');
        $hash = md5($label);
        for ($i = 1; $i <= 7; $i++) {
            $antes = $parametrosStored[$hash][$i]['antes'] ?? ($parametrosStored[$label][$i]['antes'] ?? "");
            $desp  = $parametrosStored[$hash][$i]['despues'] ?? ($parametrosStored[$label][$i]['despues'] ?? "");
            $pdf->Cell($colW,7, txt((string)$antes), 1, 0, 'C');
            $pdf->Cell($colW,7, txt((string)$desp), 1, 0, 'C');
        }
        $pdf->Ln();
    }

    $pdf->Ln(4);




// ---------- TRABAJOS ----------
    $pdf->SetFont('Arial','B',9);
    $pdf->MultiCell(0,7, txt("Trabajos Realizados:\n" . ($m['trabajos'] ?? '')), 1);
    $pdf->Ln(2);

    // ---------- OBSERVACIONES ----------
   /* $pdf->AddPage(); */
    /* $pdf->SetFont('Arial','B',10);
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
// --- Imágenes (ajustadas con proporción real) ---
if (!empty($obs['imagenes']) && is_array($obs['imagenes'])) {
    $maxWidth = 85;   // ancho máximo por imagen en mm
    $maxHeight = 65;  // alto máximo en mm
    $margin = 10;     // espacio horizontal entre imágenes
    $count = 0;

    foreach ($obs['imagenes'] as $imgPath) {
        $realPath = __DIR__ . '/' . $imgPath;
        if (file_exists($realPath)) {
            [$width, $height] = getimagesize($realPath);

            // Escalar con proporción
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $w_mm = $width * $ratio;
            $h_mm = $height * $ratio;

            // Posición X
            $x = 15 + ($count % 2) * ($maxWidth + $margin);

            // Antes de dibujar, si la imagen no cabe en la página, añadir nueva
            if ($pdf->GetY() + $h_mm > 270) {
                $pdf->AddPage();
            }

            // Dibujar
            $pdf->Image($realPath, $x, $pdf->GetY(), $w_mm, $h_mm);

            // Si es la segunda imagen, saltar de línea
            if ($count % 2 == 1) {
                $pdf->Ln($h_mm + 8);
            }

            $count++;
        } else {
            $pdf->SetFont('Arial','I',8);
            $pdf->Cell(0,5, "Imagen no encontrada: $imgPath",0,1,'L');
        }
    }

    // Si quedó una sola imagen sin pareja, baja igual
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

    // Dibujar los recuadros e imágenes de las firmas
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

    // Etiquetas “Firma de...”
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
    $fileName = "reporte_servicio_{$m['id']}.pdf";
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
