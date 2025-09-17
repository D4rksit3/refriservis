<?php
session_start();
require_once __DIR__.'/../config/db.php';

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("INSERT INTO reportes (mantenimiento_id, usuario_id, trabajos, observaciones, firma_cliente, firma_tecnico, firma_supervisor)
                           VALUES (?,?,?,?,?,?,?)");

    // Guardar firmas
    $firma_cliente = $_FILES['firma_cliente']['name'] ? uniqid().'_cliente.png' : null;
    $firma_tecnico = $_FILES['firma_tecnico']['name'] ? uniqid().'_tecnico.png' : null;
    $firma_supervisor = $_FILES['firma_supervisor']['name'] ? uniqid().'_supervisor.png' : null;

    if ($firma_cliente) move_uploaded_file($_FILES['firma_cliente']['tmp_name'], __DIR__."/../uploads/$firma_cliente");
    if ($firma_tecnico) move_uploaded_file($_FILES['firma_tecnico']['tmp_name'], __DIR__."/../uploads/$firma_tecnico");
    if ($firma_supervisor) move_uploaded_file($_FILES['firma_supervisor']['tmp_name'], __DIR__."/../uploads/$firma_supervisor");

    $stmt->execute([
        $_POST['mantenimiento_id'],
        $_SESSION['usuario_id'],
        $_POST['trabajos'],
        $_POST['observaciones'],
        $firma_cliente,
        $firma_tecnico,
        $firma_supervisor
    ]);

    $reporte_id = $pdo->lastInsertId();

    // Guardar parámetros
    $stmt2 = $pdo->prepare("INSERT INTO parametros_funcionamiento (reporte_id, medida, antes1, despues1, antes2, despues2)
                            VALUES (?,?,?,?,?,?)");
    foreach ($_POST['medida'] as $i=>$medida) {
        $stmt2->execute([
            $reporte_id,
            $medida,
            $_POST['antes1'][$i],
            $_POST['despues1'][$i],
            $_POST['antes2'][$i],
            $_POST['despues2'][$i],
        ]);
    }

    // Guardar fotos
    if (!empty($_FILES['fotos']['name'][0])) {
        foreach ($_FILES['fotos']['name'] as $i=>$name) {
            $nombreFoto = uniqid().'_'.$name;
            move_uploaded_file($_FILES['fotos']['tmp_name'][$i], __DIR__."/../uploads/$nombreFoto");
            $stmt3 = $pdo->prepare("INSERT INTO fotos_reporte (reporte_id, archivo) VALUES (?,?)");
            $stmt3->execute([$reporte_id, $nombreFoto]);
        }
    }

    $pdo->commit();
    header("Location: reporte.php?id=$reporte_id");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error: ".$e->getMessage());
}
