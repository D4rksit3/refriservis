<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../config/db.php'; // conexión PDO

try {
  if (!isset($_GET['id'])) {
    echo json_encode(["error" => "Falta parámetro id"]);
    exit;
  }

  $idCliente = intval($_GET['id']);

  // Obtener nombre completo del cliente (ej: "FALABELLA Bellavista AP")
  $stmtCliente = $pdo->prepare("SELECT cliente FROM refriservis.clientes WHERE id = ?");
  $stmtCliente->execute([$idCliente]);
  $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

  if (!$cliente) {
    echo json_encode([]);
    exit;
  }

  $nombreCliente = trim($cliente['cliente']); // ej: "FALABELLA Bellavista AP"

  // Buscar equipos donde cliente + ubicación coincida exactamente
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
          FROM refriservis.equipos e
          WHERE TRIM(LOWER(CONCAT(e.Cliente, ' ', e.ubicacion))) = TRIM(LOWER(:nombreCliente))";

  $stmtEquipos = $pdo->prepare($sql);
  $stmtEquipos->execute([':nombreCliente' => $nombreCliente]);
  $equipos = $stmtEquipos->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode($equipos);

} catch (PDOException $e) {
  echo json_encode(['error' => $e->getMessage()]);
}
