<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../vendor/autoload.php'; // mPDF

$id = $_GET['id'] ?? null;
if(!$id) exit('ID no proporcionado');

// Traer mantenimiento y datos
$m = $pdo->query("SELECT * FROM mantenimientos WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
if(!$m || $m['estado'] !== 'finalizado') exit('Reporte solo disponible para finalizados');

// Traer cliente
$cliente = $pdo->query("SELECT cliente FROM clientes WHERE id=".$m['cliente_id'])->fetchColumn();

// Traer equipos
$equiposAll = $pdo->query("SELECT id_equipo, Nombre FROM equipos")->fetchAll(PDO::FETCH_KEY_PAIR);
$equipoNombres = [];
for($i=1;$i<=7;$i++){
    $eqId = $m['equipo'.$i];
    $equipoNombres[$i] = $eqId ? $equiposAll[$eqId] : '';
}

// Generar HTML según tu formato
$html = "
<h2>FORMATO DE CALIDAD</h2>
<h3>REFRISERVIS S.A.C.</h3>
<p>REPORTE DE SERVICIO TECNICO</p>
<p>Oficina: (01) 6557907<br>Emergencias: +51 943 048 606<br>ventas@refriservissac.com</p>
<p>Reporte N°: 001-N°" . str_pad($m['id'],6,'0',STR_PAD_LEFT) . "</p>
<p>Cliente: $cliente</p>
<p>Fecha: {$m['fecha']}</p>
<h4>EQUIPOS</h4>
<ul>";
foreach($equipoNombres as $eq) {
    if($eq) $html .= "<li>$eq</li>";
}
$html .= "</ul>
<!-- Aquí agregas el resto de tu formato HTML como tablas para equipos, parámetros y observaciones -->";

// Generar PDF
$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML($html);
$mpdf->Output("reporte_{$m['id']}.pdf","D");
