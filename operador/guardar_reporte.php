<?php
// operador/guardar_reporte.php
session_start();
header('Content-Type: application/json'); // si lo llamas por AJAX; si no, quitalo y usa redirect
require_once __DIR__ . '/../config/db.php';

// Config
$uploadDir = __DIR__ . '/../uploads/reportes/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

// Validación básica
if (!isset($_POST['mantenimiento_id'])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'msg'=>'Falta mantenimiento_id']);
    exit;
}
$mantenimiento_id = (int)$_POST['mantenimiento_id'];

// parámetros recibidos
$equipos = $_POST['equipos'] ?? [];
$parametros = $_POST['parametros'] ?? [];
$trabajos = $_POST['trabajos'] ?? null;
$observaciones = $_POST['observaciones'] ?? null;

// Firmas como base64 (si enviadas)
$firma_cliente_b64 = $_POST['firma_cliente'] ?? null;
$firma_supervisor_b64 = $_POST['firma_supervisor'] ?? null;
$firma_tecnico_b64 = $_POST['firma_tecnico'] ?? null;

// Fotos subidas (input file fotos[])
$fotos_paths = [];
if (!empty($_FILES['fotos']) && isset($_FILES['fotos']['tmp_name'])) {
    for($i=0;$i<count($_FILES['fotos']['tmp_name']);$i++){
        if (!is_uploaded_file($_FILES['fotos']['tmp_name'][$i])) continue;
        $tmp = $_FILES['fotos']['tmp_name'][$i];
        $name = preg_replace('/[^A-Za-z0-9\-_\.]/','_', $_FILES['fotos']['name'][$i]);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $newName = 'foto_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
        $dest = $uploadDir.$newName;
        if (move_uploaded_file($tmp,$dest)) {
            $fotos_paths[] = '/uploads/reportes/'.$newName;
        }
    }
}

// Guardar firmas base64 como archivos PNG
function save_base64_image($b64, $prefix, $uploadDir) {
    if (!$b64) return null;
    if (preg_match('/^data:image\/(\w+);base64,/', $b64, $type)) {
        $b64 = substr($b64, strpos($b64, ',') + 1);
        $type = strtolower($type[1]);
        if (!in_array($type, ['png','jpg','jpeg'])) $type = 'png';
        $data = base64_decode($b64);
        if ($data === false) return null;
        $filename = $prefix.'_'.time().'_'.bin2hex(random_bytes(4)).'.'.$type;
        $path = $uploadDir.$filename;
        if(file_put_contents($path, $data)) {
            return '/uploads/reportes/'.$filename;
        }
    }
    return null;
}

$firma_cliente_path = save_base64_image($firma_cliente_b64,'firma_cliente',$uploadDir);
$firma_supervisor_path = save_base64_image($firma_supervisor_b64,'firma_supervisor',$uploadDir);
$firma_tecnico_path = save_base64_image($firma_tecnico_b64,'firma_tecnico',$uploadDir);

// Convertir arrays a JSON para guardar
$parametros_json = json_encode($parametros, JSON_UNESCAPED_UNICODE);
$equipos_json = json_encode($equipos, JSON_UNESCAPED_UNICODE);
$fotos_json = json_encode($fotos_paths, JSON_UNESCAPED_UNICODE);

// Generar numero_reporte secuencial: 001-N°000001
try {
    $pdo->beginTransaction();
    // obtener siguiente id aproximado
    $r = $pdo->query("SELECT IFNULL(MAX(id),0) as mx FROM reportes FOR UPDATE")->fetch(PDO::FETCH_ASSOC);
    $nextId = ((int)$r['mx']) + 1;
    $numero_reporte = sprintf("001-N°%06d", $nextId);

    // Insertar
    $stmt = $pdo->prepare("INSERT INTO reportes
      (mantenimiento_id, numero_reporte, parametros, trabajos_realizados, observaciones, fotos, firma_cliente, firma_supervisor, firma_tecnico)
      VALUES (?,?,?,?,?,?,?,?,?)");
    $ok = $stmt->execute([
        $mantenimiento_id,
        $numero_reporte,
        $parametros_json,
        $trabajos,
        $observaciones,
        $fotos_json,
        $firma_cliente_path,
        $firma_supervisor_path,
        $firma_tecnico_path
    ]);
    $insertId = $pdo->lastInsertId();
    $pdo->commit();

    // Respuesta: redirigir al PDF o devolver JSON
    // Si quieres redirigir (no AJAX):
    // header("Location: reporte_pdf.php?id=".$insertId);
    // exit;

    //echo json_encode(['success'=>true,'id'=>$insertId,'numero_reporte'=>$numero_reporte]);
    header("Location: reporte_pdf.php?id=".$insertId);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'msg'=>$e->getMessage()]);
    exit;
}
