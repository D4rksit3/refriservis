<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'agregar') {
        $nombre = $_POST['Nombre'];
        $descripcion = $_POST['Descripcion'];
        $cliente = $_POST['Cliente'];
        $categoria = $_POST['Categoria'];
        $estatus = $_POST['Estatus'];
        $fecha = $_POST['Fecha_validad'];

        $stmt = $pdo->prepare("INSERT INTO equipos (Nombre, Descripcion, Cliente, Categoria, Estatus, Fecha_validad) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $descripcion, $cliente, $categoria, $estatus, $fecha]);

        header("Location: index.php"); // Redirige a la página principal
        exit;
    }

    if ($accion === 'editar') {
        $id = $_POST['id_equipo'];
        $nombre = $_POST['Nombre'];
        $descripcion = $_POST['Descripcion'];
        $cliente = $_POST['Cliente'];
        $categoria = $_POST['Categoria'];
        $estatus = $_POST['Estatus'];
        $fecha = $_POST['Fecha_validad'];

        $stmt = $pdo->prepare("UPDATE equipos SET Nombre=?, Descripcion=?, Cliente=?, Categoria=?, Estatus=?, Fecha_validad=? WHERE id_equipo=?");
        $stmt->execute([$nombre, $descripcion, $cliente, $categoria, $estatus, $fecha, $id]);

        header("Location: index.php");
        exit;
    }

    if ($accion === 'eliminar') {
        $id = $_POST['id_equipo'];
        $stmt = $pdo->prepare("DELETE FROM equipos WHERE id_equipo=?");
        $stmt->execute([$id]);

        header("Location: inventario_equipos.php");
        exit;
    }
}
