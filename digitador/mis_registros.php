<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?error=sesion");
    exit();
}

// Conexión a la BD
require_once __DIR__ . "/../config/db.php";

// Consulta
$query = "SELECT m.id, m.fecha, m.descripcion, o.nombre AS operador, d.nombre AS digitador
          FROM mantenimientos m
          LEFT JOIN operadores o ON m.operador_id = o.id
          LEFT JOIN digitadores d ON m.digitador_id = d.id
          ORDER BY m.fecha DESC";

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
            <td>Digitador</td>
          </tr>";

    foreach ($pdo->query($query) as $fila) {
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

// Exportar PDF
if (isset($_GET['action']) && $_GET['action'] === 'pdf') {
    require("fpdf.php");

    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, "Reporte de Mantenimientos", 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(20, 8, "ID", 1, 0, 'C');
    $pdf->Cell(30, 8, "Fecha", 1, 0, 'C');
    $pdf->Cell(100, 8, "Descripción", 1, 0, 'C');
    $pdf->Cell(60, 8, "Operador", 1, 0, 'C');
    $pdf->Cell(60, 8, "Digitador", 1, 1, 'C');

    $pdf->SetFont('Arial', '', 10);
    foreach ($pdo->query($query) as $fila) {
        $pdf->Cell(20, 8, $fila['id'], 1, 0, 'C');
        $pdf->Cell(30, 8, $fila['fecha'], 1, 0, 'C');
        $pdf->Cell(100, 8, $fila['descripcion'], 1, 0, 'L');
        $pdf->Cell(60, 8, $fila['operador'], 1, 0, 'L');
        $pdf->Cell(60, 8, $fila['digitador'], 1, 1, 'L');
    }

    $pdf->Output("D", "reporte_mantenimientos.pdf");
    exit();
}

// Obtener resultados para mostrar en la tabla HTML
$stmt = $pdo->query($query);
$result = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Registros</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        .btn { display: inline-block; padding: 8px 15px; margin: 5px; text-decoration: none; color: white; border-radius: 5px; }
        .btn-excel { background: green; }
        .btn-pdf { background: red; }
    </style>
</head>
<body>
    <h1>Mis Registros</h1>
    <a href="mis_registros.php?action=excel" class="btn btn-excel">📊 Exportar a Excel</a>
    <a href="mis_registros.php?action=pdf" class="btn btn-pdf">📄 Exportar a PDF</a>

    <table>
        <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Descripción</th>
            <th>Operador</th>
            <th>Digitador</th>
        </tr>
        <?php foreach ($result as $row) { ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['fecha']) ?></td>
                <td><?= htmlspecialchars($row['descripcion']) ?></td>
                <td><?= htmlspecialchars($row['operador']) ?></td>
                <td><?= htmlspecialchars($row['digitador']) ?></td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>
