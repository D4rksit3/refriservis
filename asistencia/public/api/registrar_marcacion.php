<?php
// api/registrar_marcacion.php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/geolocalizacion.php';

// Suponemos que el usuario está autenticado y su id está en sesión
// Para pruebas local: puedes definir $_SESSION['user_id'] = 1;
$user_id = $_SESSION['user_id'] ?? ($_POST['test_user_id'] ?? null);

$lat = $_POST['lat'] ?? null;
$lon = $_POST['lon'] ?? null;
$tipo = $_POST['tipo'] ?? null;

if (!$user_id || !$lat || !$lon || !$tipo) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']);
    exit;
}

// obtener dirección/distrito
list($direccion, $distrito) = obtenerDireccion($lat, $lon);

// insertar
$stmt = $pdo->prepare("INSERT INTO marcaciones 
    (id_usuario, tipo, fecha, hora, latitud, longitud, direccion, distrito)
    VALUES (?, ?, CURDATE(), CURTIME(), ?, ?, ?, ?)");
$stmt->execute([$user_id, $tipo, $lat, $lon, $direccion, $distrito]);

echo json_encode(['ok' => true, 'msg' => 'Marcación registrada', 'direccion' => $direccion, 'distrito' => $distrito]);
