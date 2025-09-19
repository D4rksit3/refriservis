<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// inventario_equipos.php
session_start();
require_once __DIR__ . '/../config/db.php';

// --- AGREGAR ---
if (isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO equipos 
        (nombre, descripcion, identificador, colaborador, cliente, categoria, equipo_asociado, estatus, planilla_especificaciones, fecha_validacion) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['nombre'], $_POST['descripcion'], $_POST['identificador'],
        $_POST['colaborador'], $_POST['cliente'], $_POST['categoria'],
        $_POST['equipo_asociado'], $_POST['estatus'], $_POST['planilla_especificaciones'],
        $_POST['fecha_validacion']
    ]);
    header("Location: inventario_equipos.php");
    exit;
}

// --- EDITAR ---
if (isset($_POST['edit'])) {
    $stmt = $pdo->prepare("UPDATE equipos SET 
        nombre=?, descripcion=?, identificador=?, colaborador=?, cliente=?, categoria=?, 
        equipo_asociado=?, estatus=?, planilla_especificaciones=?, fecha_validacion=? 
        WHERE equipo_id=?");
    $stmt->execute([
        $_POST['nombre'], $_POST['descripcion'], $_POST['identificador'],
        $_POST['colaborador'], $_POST['cliente'], $_POST['categoria'],
        $_POST['equipo_asociado'], $_POST['estatus'], $_POST['planilla_especificaciones'],
        $_POST['fecha_validacion'], $_POST['equipo_id']
    ]);
    header("Location: inventario_equipos.php");
    exit;
}

// --- ELIMINAR ---
if (isset($_POST['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM equipos WHERE equipo_id=?");
    $stmt->execute([$_POST['equipo_id']]);
    header("Location: inventario_equipos.php");
    exit;
}

// --- PAGINACIÓN ---
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$total = $pdo->query("SELECT COUNT(*) FROM equipos")->fetchColumn();
$pages = ceil($total / $limit);

$stmt = $pdo->prepare("SELECT * FROM equipos ORDER BY equipo_id DESC LIMIT :start, :limit");
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario de Equipos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    
<!-- HEADER -->
<nav class="navbar navbar-dark bg-primary">
  <div class="container-fluid">
    <span class="navbar-brand mb-0 h1">📦 Inventario de Equipos</span>
  </div>
</nav>

<div class="container py-4">

    <!-- Botón agregar -->
    <div class="d-flex justify-content-between mb-3">
        <h2 class="fw-bold">Lista de Equipos</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">➕ Agregar</button>
    </div>

    <!-- Tabla -->
    <div class="table-responsive shadow bg-white rounded">
        <table class="table table-striped align-middle mb-0">
            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Identificador</th>
                    <th>Colaborador</th>
                    <th>Cliente</th>
                    <th>Categoría</th>
                    <th>Estatus</th>
                    <th>Fecha Validación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($equipos as $eq): ?>
                <tr>
                    <td><?= $eq['equipo_id'] ?></td>
                    <td><?= htmlspecialchars($eq['nombre']) ?></td>
                    <td><?= htmlspecialchars($eq['descripcion']) ?></td>
                    <td><?= htmlspecialchars($eq['identificador']) ?></td>
                    <td><?= htmlspecialchars($eq['colaborador']) ?></td>
                    <td><?= htmlspecialchars($eq['cliente']) ?></td>
                    <td><?= htmlspecialchars($eq['categoria']) ?></td>
                    <td><span class="badge bg-<?= $eq['estatus']=='activo'?'success':'secondary' ?>"><?= $eq['estatus'] ?></span></td>
                    <td><?= $eq['fecha_validacion'] ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editModal<?= $eq['equipo_id'] ?>">✏️</button>
                        <button class="btn btn-sm btn-danger" 
                            data-bs-toggle="modal" 
                            data-bs-target="#deleteModal<?= $eq['equipo_id'] ?>">🗑️</button>
                    </td>
                </tr>

                <!-- MODAL EDITAR -->
                <div class="modal fade" id="editModal<?= $eq['equipo_id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <form method="post">
                                <div class="modal-header bg-warning">
                                    <h5 class="modal-title">Editar Equipo</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body row g-3">
                                    <input type="hidden" name="equipo_id" value="<?= $eq['equipo_id'] ?>">
                                    <div class="col-md-6">
                                        <label class="form-label">Nombre</label>
                                        <input type="text" name="nombre" class="form-control" value="<?= $eq['nombre'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Descripción</label>
                                        <input type="text" name="descripcion" class="form-control" value="<?= $eq['descripcion'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Identificador</label>
                                        <input type="text" name="identificador" class="form-control" value="<?= $eq['identificador'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Colaborador</label>
                                        <input type="text" name="colaborador" class="form-control" value="<?= $eq['colaborador'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Cliente</label>
                                        <input type="text" name="cliente" class="form-control" value="<?= $eq['cliente'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Categoría</label>
                                        <input type="text" name="categoria" class="form-control" value="<?= $eq['categoria'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Equipo asociado</label>
                                        <input type="text" name="equipo_asociado" class="form-control" value="<?= $eq['equipo_asociado'] ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Estatus</label>
                                        <select name="estatus" class="form-select">
                                            <option value="activo" <?= $eq['estatus']=='activo'?'selected':'' ?>>Activo</option>
                                            <option value="inactivo" <?= $eq['estatus']=='inactivo'?'selected':'' ?>>Inactivo</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Planilla Especificaciones</label>
                                        <textarea name="planilla_especificaciones" class="form-control"><?= $eq['planilla_especificaciones'] ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Fecha de Validación</label>
                                        <input type="date" name="fecha_validacion" class="form-control" value="<?= $eq['fecha_validacion'] ?>">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="edit" class="btn btn-warning">Guardar Cambios</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- MODAL ELIMINAR -->
                <div class="modal fade" id="deleteModal<?= $eq['equipo_id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-sm modal-dialog-centered">
                        <div class="modal-content">
                            <form method="post">
                                <input type="hidden" name="equipo_id" value="<?= $eq['equipo_id'] ?>">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">Eliminar</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    ¿Seguro que deseas eliminar <strong><?= $eq['nombre'] ?></strong>?
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="delete" class="btn btn-danger">Eliminar</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINACIÓN -->
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php for($i=1; $i <= $pages; $i++): ?>
                <li class="page-item <?= $i==$page?'active':'' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- MODAL AGREGAR -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Agregar Equipo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Descripción</label>
                        <input type="text" name="descripcion" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Identificador</label>
                        <input type="text" name="identificador" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Colaborador</label>
                        <input type="text" name="colaborador" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cliente</label>
                        <input type="text" name="cliente" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Categoría</label>
                        <input type="text" name="categoria" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Equipo asociado</label>
                        <input type="text" name="equipo_asociado" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Estatus</label>
                        <select name="estatus" class="form-select">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Planilla Especificaciones</label>
                        <textarea name="planilla_especificaciones" class="form-control"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fecha Validación</label>
                        <input type="date" name="fecha_validacion" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add" class="btn btn-success">Agregar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer class="bg-primary text-white text-center py-2 mt-4">
    <small>&copy; <?= date("Y") ?> Refriservis - Inventario</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
