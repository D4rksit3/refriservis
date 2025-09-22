<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../config/db.php';

$accion = $_POST['accion'] ?? '';

if($accion=='agregar'){
    $stmt = $pdo->prepare("INSERT INTO equipos (Nombre, Descripcion, Cliente, Categoria, Estatus, Fecha_validad) VALUES (?,?,?,?,?,?)");
    $success = $stmt->execute([
        $_POST['Nombre'],
        $_POST['Descripcion'],
        $_POST['Cliente'],
        $_POST['Categoria'],
        $_POST['Estatus'],
        $_POST['Fecha_validad']
    ]);
    echo json_encode(['success'=>$success]);
    exit;
}

if($accion=='editar'){
    $stmt = $pdo->prepare("UPDATE equipos SET Nombre=?, Descripcion=?, Cliente=?, Categoria=?, Estatus=?, Fecha_validad=? WHERE id_equipo=?");
    $success = $stmt->execute([
        $_POST['Nombre'],
        $_POST['Descripcion'],
        $_POST['Cliente'],
        $_POST['Categoria'],
        $_POST['Estatus'],
        $_POST['Fecha_validad'],
        $_POST['id_equipo']
    ]);
    echo json_encode(['success'=>$success]);
    exit;
}

if($accion=='eliminar'){
    $stmt = $pdo->prepare("DELETE FROM equipos WHERE id_equipo=?");
    $success = $stmt->execute([$_POST['id_equipo']]);
    echo json_encode(['success'=>$success]);
    exit;
}
