<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') { exit; }

require_once __DIR__.'/../config/db.php';

$id = (int)($_POST['id'] ?? 0);
$estatus = (int)($_POST['estatus'] ?? 0);

$stmt = $pdo->prepare("UPDATE clientes SET estatus=? WHERE id=?");
$stmt->execute([$estatus, $id]);

echo "ok";
