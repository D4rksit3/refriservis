<?php
require_once __DIR__ . '/../api/conexion.php';
require_once __DIR__ . '/../api/funciones.php';
$user = $_GET['user'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d');
if (!$user) die('Usuario no especificado');

$marc = getMarcacionesByUserDate($pdo, $user, $date);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Mapa Diario</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <style> #map { height: 80vh; width: 100%; } </style>
</head>
<body>
  <h4>Marcaciones del día: <?=$date?></h4>
  <div id="map"></div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    const marcaciones = <?= json_encode($marc, JSON_HEX_TAG) ?>;
    const map = L.map('map').setView([ -12.0464, -77.0428 ], 12); // default Lima, ajustar según necesidad
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const latlngs = [];
    marcaciones.forEach((m, idx) => {
      const lat = parseFloat(m.latitud);
      const lon = parseFloat(m.longitud);
      latlngs.push([lat, lon]);
      const marker = L.marker([lat, lon]).addTo(map);
      marker.bindPopup(`<b>${m.tipo}</b><br>${m.hora}<br>${m.direccion || ''}<br><small>${m.distrito||''}</small>`);
    });

    if (latlngs.length) {
      map.fitBounds(latlngs, { padding: [40,40] });
      const poly = L.polyline(latlngs, { weight: 3 }).addTo(map);
    } else {
      // si no hay puntos, centrar en Lima por defecto
      map.setView([-12.0464,-77.0428], 12);
    }
  </script>
</body>
</html>
