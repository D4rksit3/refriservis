<?php
// digitador/mis_registros.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'digitador') {
    header("Location: /login.php?error=sesion");
    exit();
}

require_once __DIR__ . "/../config/db.php";

// Consulta con JOIN a usuarios
$query = "SELECT m.id, m.fecha, m.descripcion, 
                 op.nombre AS operador, 
                 dig.nombre AS digitador
          FROM mantenimientos m
          LEFT JOIN usuarios op ON m.operador_id = op.id
          LEFT JOIN usuarios dig ON m.digitador_id = dig.id
          ORDER BY m.fecha DESC";

$stmt = $pdo->query($query);
$result = $stmt->fetchAll();

// Exportar Excel
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
            <td>Subido por</td>
          </tr>";
    foreach ($result as $fila) {
        echo "<tr>
                <td>{$fila['id']}</td>
                <td>{$fila['fecha']}</td>
                <td>{$fila['descripcion']}</td>
                <td>{$fila['operador']}</td>
                <td>{$fila['digitador']}</td>
              </tr>";
    }
    echo "</table>";
    exit();
}

// Exportar PDF con FPDF
if (isset($_GET['action']) && $_GET['action'] === 'pdf') {
    require("../vendor/fpdf/fpdf.php"); // asegúrate de tener fpdf en vendor

    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, "Reporte de Mantenimientos", 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(20, 8, "ID", 1, 0, 'C');
    $pdf->Cell(30, 8, "Fecha", 1, 0, 'C');
    $pdf->Cell(100, 8, "Descripción", 1, 0, 'C');
    $pdf->Cell(60, 8, "Operador", 1, 0, 'C');
    $pdf->Cell(60, 8, "Subido por", 1, 1, 'C');

    $pdf->SetFont('Arial', '', 10);
    foreach ($result as $fila) {
        $pdf->Cell(20, 8, $fila['id'], 1, 0, 'C');
        $pdf->Cell(30, 8, $fila['fecha'], 1, 0, 'C');
        $pdf->Cell(100, 8, $fila['descripcion'], 1, 0, 'L');
        $pdf->Cell(60, 8, $fila['operador'], 1, 0, 'L');
        $pdf->Cell(60, 8, $fila['digitador'], 1, 1, 'L');
    }

    $pdf->Output("D", "reporte_mantenimientos.pdf");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Registros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <h3 class="mb-3">📋 Mis Registros</h3>

            <div class="mb-3">
                <a href="mis_registros.php?action=excel" class="btn btn-success btn-sm">📊 Exportar a Excel</a>
                <a href="mis_registros.php?action=pdf" class="btn btn-danger btn-sm">📄 Exportar a PDF</a>
                <a href="subir_mantenimiento.php" class="btn btn-primary btn-sm">⬆️ Subir CSV</a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Descripción</th>
                            <th>Operador</th>
                            <th>Subido por</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $row) { ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['fecha']) ?></td>
                                <td><?= htmlspecialchars($row['descripcion']) ?></td>
                                <td><?= htmlspecialchars($row['operador']) ?></td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($row['digitador']) ?></span></td>
                            </tr>
                        <?php } ?>
                        <?php if (empty($result)) { ?>
                            <tr><td colspan="5" class="text-center text-muted">No hay registros</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
