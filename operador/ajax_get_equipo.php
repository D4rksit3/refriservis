<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (isset($_GET['id_equipo'])) {
    $id_equipo = $_GET['id_equipo'];

    $stmt = $pdo->prepare("SELECT Identificador,Nombre, Marca, Modelo, Ubicacion, Voltaje FROM equipos WHERE id_equipo = ?");
    $stmt->execute([$id_equipo]);
    $equipo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($equipo) {
        echo json_encode([
            "success" => true,
            "identificador" => $equipo['Identificador'],
            "nombre" => $equipo['Nombre'],
            "marca" => $equipo['Marca'],
            "modelo" => $equipo['Modelo'],
            "ubicacion" => $equipo['Ubicacion'],
            "voltaje" => $equipo['Voltaje']
        ]);
    } else {
        echo json_encode(["success" => false]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Parámetro faltante"]);
}
