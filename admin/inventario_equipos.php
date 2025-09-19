<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Conexion a la BD
require_once __DIR__ . '/../config/db.php';

// Procesar formulario (Agregar, Editar, Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];

        if ($accion === 'agregar') {
            $sql = "INSERT INTO equipos 
                (nombre, descripcion, identificador, colaborador, cliente, categoria, equipo_asociado, estatus, planilla_especificaciones, fecha_validacion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['nombre'],
                $_POST['descripcion'],
                $_POST['identificador'],
                $_POST['colaborador'],
                $_POST['cliente'],
                $_POST['categoria'],
                $_POST['equipo_asociado'],
                $_POST['estatus'],
                $_POST['planilla_especificaciones'],
                $_POST['fecha_validacion']
            ]);
        }

        if ($accion === 'editar') {
            $sql = "UPDATE equipos 
                SET nombre=?, descripcion=?, identificador=?, colaborador=?, cliente=?, categoria=?, equipo_asociado=?, estatus=?, planilla_especificaciones=?, fecha_validacion=? 
                WHERE id_equipo=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['nombre'],
                $_POST['descripcion'],
                $_POST['identificador'],
                $_POST['colaborador'],
                $_POST['cliente'],
                $_POST['categoria'],
                $_POST['equipo_asociado'],
                $_POST['estatus'],
                $_POST['planilla_especificaciones'],
                $_POST['fecha_validacion'],
                $_POST['id_equipo']
            ]);
        }

        if ($accion === 'eliminar') {
            $sql = "DELETE FROM equipos WHERE id_equipo=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_POST['id_equipo']]);
        }

        header("Location: inventario_equipos.php");
        exit;
    }
}

// Listar equipos
$stmt = $pdo->query("SELECT * FROM equipos ORDER BY id_equipo DESC");
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Inventario de Equipos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="#">Refriservis - Inventario</a>
  </div>
</nav>

<main class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Inventario de Equipos</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregar">+ Agregar Equipo</button>
  </div>

  <table id="tablaEquipos" class="table table-striped table-bordered">
    <thead class="table-dark">
      <tr>
        <th>Nombre</th>
        <th>Descripción</th>
        <th>Identificador</th>
        <th>Colaborador</th>
        <th>Cliente</th>
        <th>Categoría</th>
        <th>Equipo asociado</th>
        <th>Estatus</th>
        <th>Planilla</th>
        <th>Fecha validación</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($equipos as $e): ?>
      <tr>
        <td><?= htmlspecialchars($e['nombre']) ?></td>
        <td><?= htmlspecialchars($e['descripcion']) ?></td>
        <td><?= htmlspecialchars($e['identificador']) ?></td>
        <td><?= htmlspecialchars($e['colaborador']) ?></td>
        <td><?= htmlspecialchars($e['cliente']) ?></td>
        <td><?= htmlspecialchars($e['categoria']) ?></td>
        <td><?= htmlspecialchars($e['equipo_asociado']) ?></td>
        <td><?= htmlspecialchars($e['estatus']) ?></td>
        <td><?= htmlspecialchars($e['planilla_especificaciones']) ?></td>
        <td><?= htmlspecialchars($e['fecha_validacion']) ?></td>
        <td>
          <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditar<?= $e['id_equipo'] ?>">Editar</button>
          <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $e['id_equipo'] ?>">Eliminar</button>
        </td>
      </tr>

      <!-- Modal Editar -->
      <div class="modal fade" id="modalEditar<?= $e['id_equipo'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <form method="POST" class="modal-content">
            <div class="modal-header bg-warning">
              <h5 class="modal-title">Editar Equipo</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="accion" value="editar">
              <input type="hidden" name="id_equipo" value="<?= $e['id_equipo'] ?>">
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label">Nombre</label>
                  <input type="text" name="nombre" value="<?= $e['nombre'] ?>" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Identificador</label>
                  <input type="text" name="identificador" value="<?= $e['identificador'] ?>" class="form-control">
                </div>
                <div class="col-md-12">
                  <label class="form-label">Descripción</label>
                  <textarea name="descripcion" class="form-control"><?= $e['descripcion'] ?></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Colaborador</label>
                  <input type="text" name="colaborador" value="<?= $e['colaborador'] ?>" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Cliente</label>
                  <input type="text" name="cliente" value="<?= $e['cliente'] ?>" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Categoría</label>
                  <input type="text" name="categoria" value="<?= $e['categoria'] ?>" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Equipo asociado</label>
                  <input type="text" name="equipo_asociado" value="<?= $e['equipo_asociado'] ?>" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Estatus</label>
                  <select name="estatus" class="form-select">
                    <option value="Activo" <?= $e['estatus']=='Activo'?'selected':'' ?>>Activo</option>
                    <option value="Inactivo" <?= $e['estatus']=='Inactivo'?'selected':'' ?>>Inactivo</option>
                    <option value="Mantenimiento" <?= $e['estatus']=='Mantenimiento'?'selected':'' ?>>Mantenimiento</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Fecha validación</label>
                  <input type="date" name="fecha_validacion" value="<?= $e['fecha_validacion'] ?>" class="form-control">
                </div>
                <div class="col-md-12">
                  <label class="form-label">Planilla de especificaciones</label>
                  <input type="text" name="planilla_especificaciones" value="<?= $e['planilla_especificaciones'] ?>" class="form-control">
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-warning">Guardar Cambios</button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Eliminar -->
      <div class="modal fade" id="modalEliminar<?= $e['id_equipo'] ?>" tabindex="-1">
        <div class="modal-dialog">
          <form method="POST" class="modal-content">
            <div class="modal-header bg-danger text-white">
              <h5 class="modal-title">Eliminar Equipo</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <p>¿Seguro que deseas eliminar el equipo <strong><?= $e['nombre'] ?></strong>?</p>
              <input type="hidden" name="accion" value="eliminar">
              <input type="hidden" name="id_equipo" value="<?= $e['id_equipo'] ?>">
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-danger">Eliminar</button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>

<!-- Modal Agregar -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Agregar Equipo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="accion" value="agregar">
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Identificador</label>
            <input type="text" name="identificador" class="form-control">
          </div>
          <div class="col-md-12">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" class="form-control"></textarea>
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
              <option value="Activo">Activo</option>
              <option value="Inactivo">Inactivo</option>
              <option value="Mantenimiento">Mantenimiento</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Fecha validación</label>
            <input type="date" name="fecha_validacion" class="form-control">
          </div>
          <div class="col-md-12">
            <label class="form-label">Planilla de especificaciones</label>
            <input type="text" name="planilla_especificaciones" class="form-control">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Agregar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
  $('#tablaEquipos').DataTable({
    pageLength: 10,
    lengthChange: false,
    language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
  });
});
</script>
</body>
</html>
