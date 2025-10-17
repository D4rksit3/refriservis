<?php
require_once __DIR__.'/../config/db.php';

if(isset($_FILES['archivo_excel'])){
    $file = $_FILES['archivo_excel']['tmp_name'];
    
    if(($handle = fopen($file, "r")) !== FALSE)){
        $row = 0;
        while(($data = fgetcsv($handle, 1000, "\t")) !== FALSE){
            if($row == 0){ $row++; continue; } // Saltar cabecera

            // Asignar columnas
            $id = $data[0]; // opcional
            $identificador = $data[1];
            $nombre = $data[2];
            $marca = $data[3];
            $modelo = $data[4];
            $ubicacion = $data[5];
            $voltaje = $data[6];
            $cliente = $data[7];
            $categoria = $data[8];
            $estatus = $data[9];
            $fecha_validad = $data[10];

            // Insertar en BD
            $stmt = $pdo->prepare("INSERT INTO equipos (Identificador, Nombre, marca, modelo, ubicacion, voltaje, Cliente, Categoria, Estatus, Fecha_validad) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$identificador, $nombre, $marca, $modelo, $ubicacion, $voltaje, $cliente, $categoria, $estatus, $fecha_validad]);
            
            $row++;
        }
        fclose($handle);
        header("Location: index.php?msg=Importación_exitosa");
    }
}
?>
