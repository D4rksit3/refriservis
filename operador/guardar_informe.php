<?php
// operador/guardar_informe.php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: /index.php'); exit;
}

require_once __DIR__.'/../config/db.php';

// helper: guardar dataURL base64 como archivo, devuelve nombre de archivo o null
function saveDataUrlImage($dataurl, $prefix='firma') {
    if (!$dataurl) return null;
    if (strpos($dataurl, 'data:') !== 0) return null;
    $parts = explode(',', $dataurl);
    if (count($parts) !== 2) return null;
    $meta = $parts[0]; $data = $parts[1];
    // obtener extensión
    if (strpos($meta, 'image/png') !== false) $ext = 'png';
    elseif (strpos($meta, 'image/jpeg') !== false) $ext = 'jpg';
    else $ext = 'png';
    $bin = base64_decode($data);
    if ($bin === false) return null;
    $filename = $prefix . '_' . uniqid() . '.' . $ext;
    $path = __DIR__ . "/../uploads/" . $filename;
    if (file_put_contents($path, $bin) === false) return null;
    return $filename;
}

try {
    // recibir datos
    $mantenimiento_id = (int)($_POST['mantenimiento_id'] ?? 0);
    $reporte_id = isset($_POST['reporte_id']) ? (int)$_POST['reporte_id'] : null;
    $trabajos = trim($_POST['trabajos'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $usuario_id = $_SESSION['usuario_id'] ?? null;

    if (!$mantenimiento_id || !$usuario_id) {
        throw new Exception("Datos de sesión o mantenimiento faltantes.");
    }

    $pdo->beginTransaction();

    // Si ya existe reporte_id => actualizar, sino insertar
    if ($reporte_id) {
        $stmt = $pdo->prepare("UPDATE reportes SET trabajos=?, observaciones=?, usuario_id=?, actualizado_en=NOW() WHERE id=?");
        $stmt->execute([$trabajos, $observaciones, $usuario_id, $reporte_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO reportes (mantenimiento_id, usuario_id, trabajos, observaciones) VALUES (?,?,?,?)");
        $stmt->execute([$mantenimiento_id, $usuario_id, $trabajos, $observaciones]);
        $reporte_id = $pdo->lastInsertId();
    }

    // Firmas (venían como dataurls en campos ocultos)
    $firma_cliente_file = saveDataUrlImage($_POST['firma_cliente_dataurl'] ?? '', 'firma_cliente');
    $firma_tecnico_file = saveDataUrlImage($_POST['firma_tecnico_dataurl'] ?? '', 'firma_tecnico');
    $firma_supervisor_file = saveDataUrlImage($_POST['firma_supervisor_dataurl'] ?? '', 'firma_supervisor');

    // Si se obtuvieron archivos, actualizar reportes con paths relativos
    $updateParts = [];
    $updateValues = [];
    if ($firma_cliente_file) { $updateParts[] = "firma_cliente=?"; $updateValues[] = $firma_cliente_file; }
    if ($firma_tecnico_file) { $updateParts[] = "firma_tecnico=?"; $updateValues[] = $firma_tecnico_file; }
    if ($firma_supervisor_file) { $updateParts[] = "firma_supervisor=?"; $updateValues[] = $firma_supervisor_file; }
    if (!empty($updateParts)) {
        $sql = "UPDATE reportes SET " . implode(',', $updateParts) . " WHERE id=?";
        $updateValues[] = $reporte_id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateValues);
    }

    // Parámetros: eliminar los existentes si estamos actualizando y reinsertar
    $stmtDel = $pdo->prepare("DELETE FROM parametros_funcionamiento WHERE reporte_id=?");
    $stmtDel->execute([$reporte_id]);

    $stmtParam = $pdo->prepare("INSERT INTO parametros_funcionamiento (reporte_id, medida, antes1, despues1, antes2, despues2) VALUES (?,?,?,?,?,?)");
    $medidas = $_POST['medida'] ?? [];
    $antes1 = $_POST['antes1'] ?? [];
    $despues1 = $_POST['despues1'] ?? [];
    $antes2 = $_POST['antes2'] ?? [];
    $despues2 = $_POST['despues2'] ?? [];
    foreach ($medidas as $i => $m) {
        $m_trim = trim($m);
        if ($m_trim === '') continue;
        $a1 = trim($antes1[$i] ?? '');
        $d1 = trim($despues1[$i] ?? '');
        $a2 = trim($antes2[$i] ?? '');
        $d2 = trim($despues2[$i] ?? '');
        $stmtParam->execute([$reporte_id, $m_trim, $a1, $d1, $a2, $d2]);
    }

    // Fotos: si se subieron archivos (fotos[]), mover y guardar en fotos_reporte
    if (!empty($_FILES['fotos']['name'][0])) {
