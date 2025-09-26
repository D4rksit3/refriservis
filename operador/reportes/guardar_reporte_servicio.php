<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header('Location: /../index.php');
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // Asegúrate que mPDF está instalado con composer

use Mpdf\Mpdf;

// =======================
// Capturar datos del form
// =======================
$mantenimiento_id = $_POST['mantenimiento_id'] ?? null;
$equipos = $_POST['equipos'] ?? [];
$parametros = $_POST['parametros'] ?? [];
$trabajos = $_POST['trabajos'] ?? '';
$observaciones = $_POST['observaciones'] ?? '';
$firma_cliente = $_POST['firma_cliente'] ?? '';
$firma_supervisor = $_POST['firma_supervisor'] ?? '';
$firma_tecnico = $_POST['firma_tecnico'] ?? '';

// Traer datos del mantenimiento + cliente
$stmt = $pdo->prepare("
  SELECT m.*, c.cliente, c.direccion, c.responsable, c.telefono
  FROM mantenimientos m
  LEFT JOIN clientes c ON c.id = m.cliente_id
  WHERE m.id = ?
");
$stmt->execute([$mantenimiento_id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$m) {
    die("No se encontró mantenimiento");
}

// =======================
// Procesar fotos (BLOB)
// =======================
$fotos = [];
if (!empty($_FILES['fotos']['tmp_name'][0])) {
    foreach ($_FILES['fotos']['tmp_name'] as $k => $tmp) {
        if (is_uploaded_file($tmp)) {
            $data = file_get_contents($tmp);
            $fotos[] = "data:" . mime_content_type($tmp) . ";base64," . base64_encode($data);
        }
    }
}

// =======================
// Generar HTML del PDF
// =======================
$html = '
<style>
body { font-family: Arial, sans-serif; font-size: 10pt; }
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #000; padding: 4px; text-align: center; font-size: 9pt; }
h3 { margin: 0; }
.titulo { background: #cfe2f3; font-weight: bold; }
.seccion { background: #f4cccc; font-weight: bold; text-align:left; padding:5px; }
</style>

<table width="100%">
  <tr>
    <td width="30%"><img src="/ruta/logo.png" height="60"></td>
    <td width="40%" align="center">
      <h3>REPORTE DE SERVICIO TECNICO</h3>
      <div>Oficina: (01) 6557907</div>
      <div>Emergencias: +51 943 048 606</div>
      <div>ventas@refriservissac.com</div>
    </td>
    <td width="30%" align="right">
      <b>REFRISERVIS S.A.C.</b><br>
      <small>001-N°'.str_pad($m['id'], 6, "0", STR_PAD_LEFT).'</small>
    </td>
  </tr>
</table>

<br>

<table>
  <tr><td><b>CLIENTE</b></td><td colspan="5">'.htmlspecialchars($m['cliente']).'</td></tr>
  <tr><td><b>DIRECCION</b></td><td colspan="5">'.htmlspecialchars($m['direccion']).'</td></tr>
  <tr><td><b>RESPONSABLE</b></td><td colspan="2">'.htmlspecialchars($m['responsable']).'</td>
      <td><b>FECHA</b></td><td colspan="2">'.htmlspecialchars($m['fecha']).'</td></tr>
</table>

<br>

<div class="titulo">DATOS DE IDENTIFICACIÓN DE LOS EQUIPOS A INTERVENIR</div>
<table>
  <tr>
    <th>#</th><th>Identificador</th><th>Marca</th><th>Modelo</th><th>Ubicación</th><th>Voltaje</th>
  </tr>';
for($i=1;$i<=7;$i++){
    $eq = $equipos[$i] ?? [];
    $html .= '<tr>
      <td>'.$i.'</td>
      <td>'.htmlspecialchars($eq['id_equipo'] ?? '').'</td>
      <td>'.htmlspecialchars($eq['marca'] ?? '').'</td>
      <td>'.htmlspecialchars($eq['modelo'] ?? '').'</td>
      <td>'.htmlspecialchars($eq['ubicacion'] ?? '').'</td>
      <td>'.htmlspecialchars($eq['voltaje'] ?? '').'</td>
    </tr>';
}
$html .= '</table><br>';

$html .= '<div class="titulo">PARÁMETROS DE FUNCIONAMIENTO</div>
<table>
  <tr>
    <th>Medida</th>';
for($i=1;$i<=7;$i++) $html .= '<th>Eq '.$i.' Antes</th><th>Eq '.$i.' Desp.</th>';
$html .= '</tr>';

$parametrosNombres = [
    'Corriente eléctrica nominal (Amperios) L1',
    'Corriente L2','Corriente L3',
    'Tensión eléctrica nominal V1','Tensión V2','Tensión V3',
    'Presión de descarga (PSI)','Presión de succión (PSI)'
];
foreach($parametrosNombres as $p){
    $key = md5($p);
    $html .= '<tr><td>'.$p.'</td>';
    for($i=1;$i<=7;$i++){
        $antes = $parametros[$key][$i]['antes'] ?? '';
        $desp = $parametros[$key][$i]['despues'] ?? '';
        $html .= '<td>'.$antes.'</td><td>'.$desp.'</td>';
    }
    $html .= '</tr>';
}
$html .= '</table><br>';

$html .= '<div class="seccion">TRABAJOS REALIZADOS</div>
<p>'.nl2br(htmlspecialchars($trabajos)).'</p>';

$html .= '<div class="seccion">OBSERVACIONES Y RECOMENDACIONES</div>
<p>'.nl2br(htmlspecialchars($observaciones)).'</p>';

if($fotos){
    $html .= '<div class="seccion">FOTOS</div><table><tr>';
    foreach($fotos as $foto){
        $html .= '<td><img src="'.$foto.'" style="max-width:150px; max-height:150px;"></td>';
    }
    $html .= '</tr></table>';
}

// Firmas
$html .= '<br><div class="seccion">FIRMAS</div>
<table>
  <tr>
    <td align="center">Cliente<br><img src="'.$firma_cliente.'" height="80"></td>
    <td align="center">Supervisor<br><img src="'.$firma_supervisor.'" height="80"></td>
    <td align="center">Técnico<br><img src="'.$firma_tecnico.'" height="80"></td>
  </tr>
</table>';

// =======================
// Generar PDF
// =======================
$mpdf = new Mpdf(['format' => 'A4']);
$mpdf->WriteHTML($html);
$mpdf->Output("Reporte_Mantenimiento_{$mantenimiento_id}.pdf", "I");
