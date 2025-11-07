<?php
include "conexion.php";
include "geolocalizacion.php";

$lat = $_POST['lat'] ?? null;
$lon = $_POST['lon'] ?? null;
$tipo = $_POST['tipo'] ?? '';
$id_usuario = 1; // Cambiar según usuario autenticado

if ($lat && $lon && $tipo) {
    list($direccion, $distrito) = obtenerDireccion($lat, $lon);

    $stmt = $pdo->prepare("INSERT INTO marcaciones (id_usuario, tipo, fecha, hora, latitud, longitud, direccion, distrito)
                           VALUES (?, ?, CURDATE(), CURTIME(), ?, ?, ?, ?)");
    $stmt->execute([$id_usuario, $tipo, $lat, $lon, $direccion, $distrito]);

    echo "✅ Marcación registrada desde <br><b>$direccion</b><br><small>($distrito)</small>";
} else {
    echo "❌ Error: datos incompletos";
}
?>
