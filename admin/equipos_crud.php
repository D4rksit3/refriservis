<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

// =======================
// 1) LISTADO PARA DATATABLES
// =======================
// =======================
// 1) LISTADO PARA DATATABLES
// =======================
/* if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['id'])) {
    http_response_code(204); // sin contenido
    exit;
} */

// =======================
// 2) GET por id (abrir modal de edición)
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM equipos WHERE id_equipo = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($row ?: []);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    exit;
}

// =======================
// 3) CRUD con POST
// =======================
$accion = $_POST['accion'] ?? '';



if ($accion === 'editar') {
    try {
        $stmt = $pdo->prepare("UPDATE equipos SET 
            Identificador=?, Nombre=?, marca=?, modelo=?, ubicacion=?, voltaje=?, Descripcion=?, Cliente=?, Categoria=?, Estatus=?, Fecha_validad=? 
            WHERE id_equipo=?");
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
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($accion === 'eliminar') {
    try {
        $stmt = $pdo->prepare("DELETE FROM equipos WHERE id_equipo = ?");
        $success = $stmt->execute([$_POST['id_equipo'] ?? 0]);
        echo json_encode(['success' => (bool)$success]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// =======================
// Si llega aquí, acción no reconocida
// =======================
echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
