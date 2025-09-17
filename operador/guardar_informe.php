<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header("Location: /index.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';

$idMantenimiento = $_POST['mantenimiento_id'];
$trabajos = $_POST['trabajos'] ?? '';
$observaciones = $_POST['observaciones'] ?? '';
$firmaData = $_POST['firma'] ?? '';

$firmaFile = null;
if (!empty($firmaData)) {
    $firmaData = str_replace('data:image/png;base64,', '', $firmaData);
    $firmaData = str_replace(' ', '+', $firmaData);
    $imagen = base64_decode($firmaData);

    $dir = __DIR__ . '/../uploads/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $firmaFile = 'firma_' . time() . '.png';
    file_put_contents($dir . $firmaFile, $imagen);
}

$stmt = $pdo->prepare("INSERT INTO reportes (mantenimiento_id, usuario_id, trabajos, observaciones, firma) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$idMantenimiento, $_SESSION['usuario_id'], $trabajos, $observaciones, $firmaFile]);

$id = $pdo->lastInsertId();
header("Location: reporte.php?id=" . $id);
exit;
