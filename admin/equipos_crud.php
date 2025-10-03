<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../config/db.php';

// If GET with id => return single record (used to open Edit modal)
if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])){
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM equipos WHERE id_equipo = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($row ?: []);
    exit;
}

// POST actions (add / edit / delete)
$accion = $_POST['accion'] ?? '';

if($accion === 'agregar'){
    try {
        $stmt = $pdo->prepare("INSERT INTO equipos (Identificador, Nombre, marca, modelo, ubicacion, voltaje, Descripcion, Cliente, Categoria, Estatus, Fecha_validad) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $success = $stmt->execute([
            $_POST['Identificador'] ?? null,
            $_POST['Nombre'] ?? null,
            $_POST['marca'] ?? null,
            $_POST['modelo'] ?? null,
            $_POST['ubicacion'] ?? null,
            $_POST['voltaje'] ?? null,
            $_POST['Descripcion'] ?? null,
            $_POST['Cliente'] ?? null,
            $_POST['Categoria'] ?? null,
            $_POST['Estatus'] ?? null,
            $_POST['Fecha_validad'] ?? null
        ]);
        echo json_encode(['success' => (bool)$success]);
    } catch (PDOException $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}

if($accion === 'editar'){
    try {
        $stmt = $pdo->prepare("UPDATE equipos SET Identificador=?, Nombre=?, marca=?, modelo=?, ubicacion=?, voltaje=?, Descripcion=?, Cliente=?, Categoria=?, Estatus=?, Fecha_validad=? WHERE id_equipo=?");
        $success = $stmt->execute([
            $_POST['Identificador'] ?? null,
            $_POST['Nombre'] ?? null,
            $_POST['marca'] ?? null,
            $_POST['modelo'] ?? null,
            $_POST['ubicacion'] ?? null,
            $_POST['voltaje'] ?? null,
            $_POST['Descripcion'] ?? null,
            $_POST['Cliente'] ?? null,
            $_POST['Categoria'] ?? null,
            $_POST['Estatus'] ?? null,
            $_POST['Fecha_validad'] ?? null,
            $_POST['id_equipo'] ?? 0
        ]);
        echo json_encode(['success' => (bool)$success]);
    } catch (PDOException $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}

if($accion === 'eliminar'){
    try {
        $stmt = $pdo->prepare("DELETE FROM equipos WHERE id_equipo = ?");
        $success = $stmt->execute([$_POST['id_equipo'] ?? 0]);
        echo json_encode(['success' => (bool)$success]);
    } catch (PDOException $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit;
}

echo json_encode(['success'=>false, 'message'=>'Acción no reconocida']);
