<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../config/db.php';

try {
  if (!isset($_GET['id'])) {
    echo json_encode(["error" => "Falta parámetro id"]);
    exit;
  }

  $idCliente = intval($_GET['id']);

  // Obtener nombre del cliente
  $stmtCliente = $pdo->prepare("SELECT cliente FROM refriservis.clientes WHERE id = ?");
  $stmtCliente->execute([$idCliente]);
  $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

  if (!$cliente) {
    echo json_encode([]);
    exit;
  }

  $nombreCliente = trim($cliente['cliente']);
  // Extraemos la parte después del primer espacio (por ejemplo: "FALABELLA Bellavista AP" -> "Bellavista AP")
  $partes = explode(' ', $nombreCliente, 2);
  $ubicacionClave = isset($partes[1]) ? $partes[1] : $nombreCliente;

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
          WHERE LOWER(e.ubicacion) LIKE LOWER(CONCAT('%', :ubicacionClave, '%'))
             OR LOWER(e.Cliente) LIKE LOWER(CONCAT('%', :nombreCliente, '%'))";

  $stmtEquipos = $pdo->prepare($sql);
  $stmtEquipos->execute([
    ':ubicacionClave' => $ubicacionClave,
    ':nombreCliente' => $nombreCliente
  ]);

  $equipos = $stmtEquipos->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode($equipos);

} catch (PDOException $e) {
  echo json_encode(['error' => $e->getMessage()]);
}
