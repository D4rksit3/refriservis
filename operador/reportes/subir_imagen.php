<?php
// subir_imagen.php

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$rutas = [];

if (!empty($_FILES['imagenes']['name'][0])) {
    foreach ($_FILES['imagenes']['tmp_name'] as $i => $tmpName) {
        $nombreOriginal = basename($_FILES['imagenes']['name'][$i]);
        $nombreSeguro = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombreOriginal);
        $destino = $uploadDir . $nombreSeguro;

        if (move_uploaded_file($tmpName, $destino)) {
            $rutas[] = 'uploads/' . $nombreSeguro;
        }
    }
}

header('Content-Type: application/json');
echo json_encode($rutas);
