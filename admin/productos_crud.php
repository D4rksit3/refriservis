<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/../config/db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $accion = $_POST['accion'] ?? '';

    if($accion === 'agregar'){
        $nombre = $_POST['Nombre'];
        $categoria = $_POST['Categoria'];
        $estatus = $_POST['Estatus'];
        $valor = $_POST['Valor_unitario'];

        $stmt = $pdo->prepare("INSERT INTO productos (Nombre, Categoria, Estatus, Valor_unitario) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $categoria, $estatus, $valor]);
        header("Location: index.php");
        exit;
    }

    if($accion === 'editar'){
        $id = $_POST['productos_id'];
        $nombre = $_POST['Nombre'];
        $categoria = $_POST['Categoria'];
        $estatus = $_POST['Estatus'];
        $valor = $_POST['Valor_unitario'];

        $stmt = $pdo->prepare("UPDATE productos SET Nombre=?, Categoria=?, Estatus=?, Valor_unitario=? WHERE productos_id=?");
        $stmt->execute([$nombre, $categoria, $estatus, $valor, $id]);
        header("Location: index.php");
        exit;
    }

    if($accion === 'eliminar'){
        $id = $_POST['productos_id'];
        $stmt = $pdo->prepare("DELETE FROM productos WHERE productos_id=?");
        $stmt->execute([$id]);
        header("Location: index.php");
        exit;
    }
}
