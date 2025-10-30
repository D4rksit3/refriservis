<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: /index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

try {
    // ==========================
    // CONSULTA PRINCIPAL
    // ==========================
    $sql = "
    SELECT DISTINCT 
        e.id_equipo,
        e.Identificador,
        e.Nombre AS nombre_equipo,
        e.marca,
        e.modelo,
        e.ubicacion,
        e.Categoria,
        e.Descripcion,
        COUNT(m.id) AS cantidad_mantenimientos
    FROM equipos e
    LEFT JOIN mantenimientos m 
        ON e.id_equipo IN (
            m.equipo1, m.equipo2, m.equipo3,
            m.equipo4, m.equipo5, m.equipo6, m.equipo7
        )
    GROUP BY e.id_equipo
    ORDER BY cantidad_mantenimientos DESC, e.Nombre ASC;
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error en la consulta: " . $e->getMessage());
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Análisis de Equipos - RefriServis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <h2 class="mb-4 text-center text-primary fw-bold">📊 Análisis de Equipos</h2>

    <div class="card shadow">
        <div class="card-body">
            <?php if (count($equipos) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Identificador</th>
                            <th>Nombre del Equipo</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Ubicación</th>
                            <th>Categoría</th>
                            <th>Descripción</th>
                            <th>Mantenimientos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipos as $eq): ?>
                        <tr>
                            <td><?= htmlspecialchars($eq['id_equipo']) ?></td>
                            <td><?= htmlspecialchars($eq['Identificador']) ?></td>
                            <td><?= htmlspecialchars($eq['nombre_equipo']) ?></td>
                            <td><?= htmlspecialchars($eq['marca']) ?></td>
                            <td><?= htmlspecialchars($eq['modelo']) ?></td>
                            <td><?= htmlspecialchars($eq['ubicacion']) ?></td>
                            <td><?= htmlspecialchars($eq['Categoria']) ?></td>
                            <td><?= htmlspecialchars($eq['Descripcion']) ?></td>
                            <td class="text-center fw-bold text-primary"><?= htmlspecialchars($eq['cantidad_mantenimientos']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="alert alert-warning text-center">No hay equipos registrados o asociados a mantenimientos.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center mt-3">
        <a href="/admin/" class="btn btn-secondary">⬅ Volver al Panel</a>
    </div>
</div>

</body>
</html>
