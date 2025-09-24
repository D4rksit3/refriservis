<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../vendor/autoload.php'; // mPDF

$id = $_GET['id'] ?? null;
if(!$id) exit("⚠️ ID no proporcionado");

$m = $pdo->query("SELECT * FROM mantenimientos WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
if(!$m || $m['estado']!=='finalizado') exit("⚠️ Reporte solo disponible para finalizados");

$cliente = $pdo->query("SELECT cliente FROM clientes WHERE id=".$m['cliente_id'])->fetchColumn();

$html = "
<h2>FORMATO DE CALIDAD</h2>
<h3>REFRISERVIS S.A.C.</h3>
<p><b>Reporte N°:</b> 001-".str_pad($m['id'],6,'0',STR_PAD_LEFT)."</p>
<p><b>Cliente:</b> $cliente</p>
<p><b>Fecha:</b> ".$m['fecha']."</p>
<hr>
<p>--- Aquí agregas los demás campos de tu formato ---</p>
";

$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML($html);
$mpdf->Output("reporte_{$m['id']}.pdf","D");
