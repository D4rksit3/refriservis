<?php
// digitador/mis_registros.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'digitador') {
    header("Location: /login.php?error=sesion");
    exit();
}

require_once __DIR__ . "/../config/db.php";

// ------------- comprobar si la tabla tiene columnas de "actualizado" -------------
$hasUpdatedCols = false;
try {
    $q = $pdo->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'mantenimientos'
          AND COLUMN_NAME IN ('actualizado_por','actualizado_en')
    ");
    $q->execute();
    $hasUpdatedCols = ($q->fetchColumn() == 2);
} catch (Exception $e) {
    // si falla, asumimos que no existen las columnas
    $hasUpdatedCols = false;
}

// ------------- construir consulta según columnas disponibles -------------
if ($hasUpdatedCols) {
    $sql = "SELECT m.id, m.fecha, m.descripcion,
                   op.nombre AS operador,
                   dig.nombre AS digitador,
                   upd.nombre AS actualizado_por,
                   m.creado_en,
                   m.actualizado_en
            FROM mantenimientos m
            LEFT JOIN usuarios op ON m.operador_id = op.id
            LEFT JOIN usuarios dig ON m.digitador_id = dig.id
            LEFT JOIN usuarios upd ON m.actualizado_por = upd.id
            ORDER BY COALESCE(m.actualizado_en, m.creado_en) DESC";
} else {
    $sql = "SELECT m.id, m.fecha, m.descripcion,
                   op.nombre AS operador,
                   dig.nombre AS digitador,
                   NULL AS actualizado_por,
                   m.creado_en,
                   NULL AS actualizado_en
            FROM mantenimientos m
            LEFT JOIN usuarios op ON m.operador_id = op.id
            LEFT JOIN usuarios dig ON m.digitador_id = dig.id
            ORDER BY m.creado_en DESC";
}

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ------------- Exportar a Excel -------------
if (isset($_GET['action']) && $_GET['action'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=reporte_mantenimientos.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<table border='1'>";
    echo "<tr style='background:#f2f2f2; font-weight:bold;'>
            <td>ID</td>
            <td>Fecha</td>
            <td>Descripción</td>
            <td>Operador</td>
            <td>Subido por (usuario - fecha/hora)</td>
            <td>Última modificación (usuario - fecha/hora)</td>
          </tr>";

    foreach ($rows as $r) {
        $subido_usr = $r['digitador'] ?? '-';
        $subido_fecha = $r['creado_en'] ?? '';
        $mod_usr = $r['actualizado_por'] ?? '-';
        $mod_fecha = $r['actualizado_en'] ?? '-';

        echo "<tr>
                <td>{$r['id']}</td>
                <td>{$r['fecha']}</td>
                <td>{$r['descripcion']}</td>
                <td>{$r['operador']}</td>
                <td>" . htmlspecialchars($subido_usr) . " - " . htmlspecialchars($subido_fecha) . "</td>
                <td>" . htmlspecialchars($mod_usr) . " - " . htmlspecialchars($mod_fecha) . "</td>
              </tr>";
    }
    echo "</table>";
    exit();
}

// ------------- Exportar a PDF (FPDF) -------------
if (isset($_GET['action']) && $_GET['action'] === 'pdf') {
    // intentar cargar fpdf desde vendor o ruta alternativa
    if (file_exists(__DIR__ . "/../vendor/fpdf/fpdf.php")) {
        require_once __DIR__ . "/../vendor/fpdf/fpdf.php";
    } elseif (file_exists(__DIR__ . "/../fpdf.php")) {
        require_once __DIR__ . "/../fpdf.php";
    } else {
        die("FPDF no encontrado. Coloca fpdf.php en la raíz del proyecto o instala via composer.");
    }

    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, "Reporte de Mantenimientos", 0, 1, 'C');

    // encabezados
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(10, 8, "ID", 1, 0, 'C');
    $pdf->Cell(25, 8, "Fecha", 1, 0, 'C');
    $pdf->Cell(100, 8, "Descripción", 1, 0, 'C');
    $pdf->Cell(40, 8, "Operador", 1, 0, 'C');
    $pdf->Cell(55, 8, "Subido por (user - fecha)", 1, 0, 'C');
    $pdf->Cell(55, 8, "Última modificación", 1, 1, 'C');

    $pdf->SetFont('Arial', '', 9);
    foreach ($rows as $r) {
        $subido = ($r['digitador'] ?? '-') . " - " . ($r['creado_en'] ?? '-');
        $mod = ($r['actualizado_por'] ?? '-') . " - " . ($r['actualizado_en'] ?? '-');

        $pdf->Cell(10, 8, $r['id'], 1, 0, 'C');
        $pdf->Cell(25, 8, $r['fecha'], 1, 0, 'C');
        $pdf->Cell(100, 8, substr($r['descripcion'], 0, 80), 1, 0, 'L');
        $pdf->Cell(40, 8, substr($r['operador'] ?? '-',0,20), 1, 0, 'L');
        $pdf->Cell(55, 8, substr($subido,0,35), 1, 0, 'L');
        $pdf->Cell(55, 8, substr($mod,0,35), 1, 1, 'L');
    }

    $pdf->Output("D", "reporte_mantenimientos.pdf");
    exit();
}

// ------------- renderizado HTML -------------
// incluye header si existe, si no usar fallback simple
$headerFile = __DIR__ . "/../includes/header.php";
$footerFile = __DIR__ . "/../includes/footer.php";

$useFallbackLayout = !file_exists($headerFile) || !file_exists($footerFile);

if (file_exists($headerFile)) {
    require_once $headerFile;
} else {
    // fallback header (básico)
    ?><!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <title>Mis Registros</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
      <div class="container"><a class="navbar-brand" href="/">Refriservis</a></div>
    </nav>
    <div class="container py-4"><?php
}
?>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">📋 Mis Registros</h4>
      <div>
        <a href="mis_registros.php?action=excel" class="btn btn-success btn-sm">📊 Exportar Excel</a>
        <a href="mis_registros.php?action=pdf" class="btn btn-danger btn-sm">📄 Exportar PDF</a>
        <a href="subir_mantenimiento.php" class="btn btn-primary btn-sm">⬆️ Subir CSV</a>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-striped table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:60px">ID</th>
            <th style="width:120px">Fecha</th>
            <th>Descripción</th>
            <th style="width:140px">Operador</th>
            <th style="width:220px">Subido por (usuario - fecha/hora)</th>
            <th style="width:220px">Última modificación (usuario - fecha/hora)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center text-muted">No hay registros</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $r): 
                $subido_usr = htmlspecialchars($r['digitador'] ?? '-');
                $subido_fecha = htmlspecialchars($r['creado_en'] ?? '-');
                $mod_usr = htmlspecialchars($r['actualizado_por'] ?? '-');
                $mod_fecha = htmlspecialchars($r['actualizado_en'] ?? '-');
            ?>
              <tr>
                <td><?= htmlspecialchars($r['id']) ?></td>
                <td><?= htmlspecialchars($r['fecha']) ?> <br><small class="text-muted"><?= htmlspecialchars($r['creado_en'] ?? '') ?></small></td>
                <td><?= nl2br(htmlspecialchars($r['descripcion'])) ?></td>
                <td><?= htmlspecialchars($r['operador'] ?? '-') ?></td>
                <td><?= $subido_usr ?> <br><small class="text-muted"><?= $subido_fecha ?></small></td>
                <td><?= $mod_usr ?> <br><small class="text-muted"><?= $mod_fecha ?></small></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
// footer include o fallback
if (file_exists($footerFile)) {
    require_once $footerFile;
} else {
    // fallback footer
    ?>
    </div> <!-- container -->
    <footer class="mt-4 py-3 bg-white text-center small">
      &copy; <?= date('Y') ?> Refriservis
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    </body></html>
    <?php
}
