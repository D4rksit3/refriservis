<?php
function obtenerDireccion($lat, $lon) {
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lon&zoom=18&addressdetails=1";
    $opts = ['http' => ['header' => "User-Agent: asistenciaApp/1.0\r\n"]];
    $context = stream_context_create($opts);
    $json = file_get_contents($url, false, $context);
    $data = json_decode($json, true);

    if (isset($data['address'])) {
        $direccion = $data['display_name'] ?? 'Sin dirección';
        $distrito = $data['address']['suburb'] 
                 ?? $data['address']['city_district'] 
                 ?? $data['address']['city'] 
                 ?? $data['address']['town'] 
                 ?? 'Sin distrito';
        return [$direccion, $distrito];
    }
    return ['Sin dirección', 'Sin distrito'];
}
?>
