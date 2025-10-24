<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../config/db.php';// tu conexión PDO o mysqli

if (!isset($_GET['id'])) {
  echo json_encode(["error" => "Falta parámetro id"]);
  exit;
}

$idCliente = intval($_GET['id']);

try {
  $sql = "SELECT 
            e.id_equipo,
            e.Identificador,
            e.Nombre AS nombre_equipo,
            e.marca,
            e.modelo,
            e.ubicacion,
            e.voltaje,
            e.Descripcion,
            e.Categoria,
            e.Estatus
          FROM equipos e
          INNER JOIN clientes c ON e.Cliente = c.cliente
          WHERE c.id = :idCliente";

  $stmt = $pdo->prepare($sql);
  $stmt->bindParam(':idCliente', $idCliente, PDO::PARAM_INT);
  $stmt->execute();
  $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode($equipos);
} catch (PDOException $e) {
  echo json_encode(["error" => $e->getMessage()]);
}
