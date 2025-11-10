<?php
// api/geolocalizacion.php

function obtenerDireccion($lat, $lon) {
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}&zoom=18&addressdetails=1";
    $opts = ['http' => ['header' => "User-Agent: asistenciaApp/1.0\r\n"]];
    $context = stream_context_create($opts);
    $json = @file_get_contents($url, false, $context);
    if (!$json) return ['Sin dirección', 'Sin distrito'];

    $data = json_decode($json, true);
    $direccion = $data['display_name'] ?? 'Sin dirección';
    $addr = $data['address'] ?? [];
    // En Perú y en muchas ciudades 'suburb' o 'neighbourhood' o 'city_district' o 'city' puede contener el distrito
    $distrito = $addr['suburb'] ?? $addr['neighbourhood'] ?? $addr['city_district'] ?? $addr['city'] ?? $addr['town'] ?? 'Sin distrito';

    return [$direccion, $distrito];
}
?>
