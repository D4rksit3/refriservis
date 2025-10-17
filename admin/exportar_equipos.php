<?php
require_once __DIR__.'/../config/db.php';

// Cabeceras para forzar descarga
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=inventario_equipos.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Obtenemos los datos
$stmt = $pdo->query("SELECT * FROM equipos"); // Cambia "equipos" al nombre real de tu tabla
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cabeceras de tabla
echo "ID\tIdentificador\tNombre\tMarca\tModelo\tUbicación\tVoltaje\tCliente\tCategoria\tEstatus\tFecha_Validacion\n";

foreach($equipos as $e){
    echo "{$e['id']}\t{$e['Identificador']}\t{$e['Nombre']}\t{$e['marca']}\t{$e['modelo']}\t{$e['ubicacion']}\t{$e['voltaje']}\t{$e['Cliente']}\t{$e['Categoria']}\t{$e['Estatus']}\t{$e['Fecha_validad']}\n";
}
?>
