<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'digitador') {
    header('Location: /index.php');
    exit;
}

$_SESSION['usuario_id'] = $row['id'];   // id del usuario en la tabla usuarios
$_SESSION['usuario'] = $row['usuario'];
$_SESSION['rol'] = $row['rol'];


require_once __DIR__ . '/../config/db.php';

// Obtener mantenimientos del digitador actual
$stmt = $pdo->prepare("
    SELECT m.id, m.titulo, m.fecha, m.estado,
           c.nombre AS cliente
    FROM mantenimientos m
    LEFT JOIN clientes c ON c.id = m.cliente_id
    WHERE m.usuario_id = ?
    ORDER BY m.fecha DESC
");
$stmt->execute([$_SESSION['usuario_id']]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Registros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h3 class="mb-3">📋 Mis Registros de Mantenimiento</h3>

    <form method="post" action="combinar.php">
        <div class="table-responsive shadow-sm rounded bg-white p-3">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width:40px;"></th>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows): ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="seleccionados[]" value="<?= $r['id'] ?>">
                                </td>
                                <td><?= htmlspecialchars($r['id']) ?></td>
                                <td><?= htmlspecialchars($r['titulo']) ?></td>
                                <td><?= date("d/m/Y", strtotime($r['fecha'])) ?></td>
                                <td><?= htmlspecialchars($r['cliente']) ?></td>
                                <td>
                                    <?php if ($r['estado'] === 'pendiente'): ?>
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    <?php elseif ($r['estado'] === 'finalizado'): ?>
                                        <span class="badge bg-success">Finalizado</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($r['estado']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="editar.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        Ver / Editar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No tienes registros aún.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($rows): ?>
        <div class="text-end mt-3">
            <button type="submit" class="btn btn-success">
                📑 Combinar Seleccionados
            </button>
        </div>
        <?php endif; ?>
    </form>
</div>
</body>
</html>
