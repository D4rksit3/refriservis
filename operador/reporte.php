<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header("Location: /index.php");
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

use PhpOffice\PhpWord\TemplateProcessor;

// Obtener datos del mantenimiento
$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID de mantenimiento no válido.");
}

$stmt = $pdo->prepare("SELECT m.*, c.nombre AS cliente, i.nombre AS inventario 
                       FROM mantenimientos m 
                       LEFT JOIN clientes c ON c.id = m.cliente_id 
                       LEFT JOIN inventario i ON i.id = m.inventario_id 
                       WHERE m.id = ?");
$stmt->execute([$id]);
$mantenimiento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mantenimiento) {
    die("Mantenimiento no encontrado.");
}

// Cargar plantilla
$template = new TemplateProcessor(__DIR__ . '/../plantillas/reporte_servicio.docx');

// Reemplazar valores
$template->setValue('cliente', htmlspecialchars($mantenimiento['cliente']));
$template->setValue('fecha', $mantenimiento['fecha']);
$template->setValue('coordinador', $_SESSION['usuario']);
$template->setValue('equipo', htmlspecialchars($mantenimiento['inventario']));
$template->setValue('trabajos', htmlspecialchars($mantenimiento['descripcion']));
$template->setValue('observaciones', htmlspecialchars($mantenimiento['estado']));

// Descargar archivo
$filename = "Reporte_Mantenimiento_" . $mantenimiento['id'] . ".docx";
header("Content-Description: File Transfer");
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
$template->saveAs("php://output");
exit;
