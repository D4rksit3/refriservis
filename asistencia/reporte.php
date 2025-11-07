<?php
include "conexion.php";
$registros = $pdo->query("SELECT m.*, u.nombre FROM marcaciones m 
                          LEFT JOIN usuarios u ON m.id_usuario = u.id 
                          ORDER BY fecha DESC, hora DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Marcaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
    <h3 class="mb-4">Reporte de Marcaciones</h3>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Empleado</th>
                <th>Tipo</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Dirección</th>
                <th>Distrito</th>
                <th>Mapa</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registros as $r): ?>
            <tr>
                <td><?=htmlspecialchars($r['nombre'] ?? 'Desconocido')?></td>
                <td><?=htmlspecialchars($r['tipo'])?></td>
                <td><?=$r['fecha']?></td>
                <td><?=$r['hora']?></td>
                <td><?=htmlspecialchars($r['direccion'])?></td>
                <td><?=htmlspecialchars($r['distrito'])?></td>
                <td><a href="https://www.google.com/maps?q=<?=$r['latitud']?>,<?=$r['longitud']?>" target="_blank">Ver mapa</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
