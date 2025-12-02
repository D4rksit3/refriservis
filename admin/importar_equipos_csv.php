<?php
//admin/importar_equipos_csv.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Obtener datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['equipos']) || !is_array($data['equipos'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$equipos = $data['equipos'];
$total = count($equipos);
$insertados = 0;
$errores = 0;
$detalles_errores = [];

// Preparar statement
$stmt = $pdo->prepare("
    INSERT INTO equipos 
    (Identificador, Nombre, marca, modelo, ubicacion, voltaje, Descripcion, Cliente, Categoria, Estatus, Fecha_validad) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

// Procesar cada equipo
foreach ($equipos as $index => $equipo) {
    $fila = $index + 2; // +2 porque empieza en fila 1 y tiene encabezado
    
    try {
        // Validar campos requeridos
        $camposRequeridos = ['Identificador', 'Nombre', 'marca', 'modelo', 'ubicacion', 'voltaje'];
        $faltantes = [];
        
        foreach ($camposRequeridos as $campo) {
            if (empty($equipo[$campo])) {
                $faltantes[] = $campo;
            }
        }
        
        if (!empty($faltantes)) {
            $errores++;
            $detalles_errores[] = [
                'fila' => $fila,
                'error' => 'Campos requeridos faltantes: ' . implode(', ', $faltantes)
            ];
            continue;
        }
        
        // Validar que no exista el identificador
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM equipos WHERE Identificador = ?");
        $checkStmt->execute([$equipo['Identificador']]);
        
        if ($checkStmt->fetchColumn() > 0) {
            $errores++;
            $detalles_errores[] = [
                'fila' => $fila,
                'error' => 'El identificador "' . $equipo['Identificador'] . '" ya existe'
            ];
            continue;
        }
        
        // Insertar equipo
        $success = $stmt->execute([
            $equipo['Identificador'],
            $equipo['Nombre'],
            $equipo['marca'] ?? null,
            $equipo['modelo'] ?? null,
            $equipo['ubicacion'] ?? null,
            $equipo['voltaje'] ?? null,
            $equipo['Descripcion'] ?? null,
            $equipo['Cliente'] ?? null,
            $equipo['Categoria'] ?? null,
            $equipo['Estatus'] ?? 'Activo',
            !empty($equipo['Fecha_validad']) ? $equipo['Fecha_validad'] : null
        ]);
        
        if ($success) {
            $insertados++;
        } else {
            $errores++;
            $detalles_errores[] = [
                'fila' => $fila,
                'error' => 'Error al insertar en base de datos'
            ];
        }
        
    } catch (PDOException $e) {
        $errores++;
        $detalles_errores[] = [
            'fila' => $fila,
            'error' => 'Error SQL: ' . $e->getMessage()
        ];
    } catch (Exception $e) {
        $errores++;
        $detalles_errores[] = [
            'fila' => $fila,
            'error' => $e->getMessage()
        ];
    }
}

// Respuesta final
echo json_encode([
    'success' => true,
    'total' => $total,
    'insertados' => $insertados,
    'errores' => $errores,
    'detalles_errores' => $detalles_errores
]);