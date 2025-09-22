<?php
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';

// Procesar formulario (Agregar, Editar, Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    if ($accion === 'agregar') {
        $sql = "INSERT INTO equipos (nombre, descripcion, cliente, categoria, estatus, fecha_validacion) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['nombre'],
            $_POST['descripcion'],
            $_POST['cliente'],
            $_POST['categoria'],
            $_POST['estatus'],
            $_POST['fecha_validacion']
        ]);
    }

    if ($accion === 'editar') {
        $sql = "UPDATE equipos SET 
                nombre=?, descripcion=?, cliente=?, categoria=?, estatus=?, fecha_validacion=? 
                WHERE id_equipo=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['nombre'],
            $_POST['descripcion'],
            $_POST['cliente'],
            $_POST['categoria'],
            $_POST['estatus'],
            $_POST['fecha_validacion'],
            $_POST['id_equipo']
        ]);
    }

    if ($accion === 'eliminar') {
        $sql = "DELETE FROM equipos WHERE id_equipo=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['id_equipo']]);
    }
}

// Traer equipos
$stmt = $pdo->query("SELECT * FROM equipos ORDER BY id_equipo DESC");
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h4">📋 Inventario</h2>
  <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregar">➕ Nuevo</button>
</div>

<div class="table-responsive shadow-sm">
  <table id="tablaEquipos" class="table table-striped align-middle">
    <thead class="table-primary">
      <tr>
        <th>Nombre</th>
        <th>Descripción</th>
        <th>Cliente</th>
        <th>Categoría</th>
        <th>Estatus</th>
        <th>Fecha Validación</th>
        <th class="text-center">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($equipos as $eq): ?>
        <tr>
          <td><?=htmlspecialchars($eq['nombre'])?></td>
          <td><?=htmlspecialchars($eq['descripcion'])?></td>
          <td><?=htmlspecialchars($eq['cliente'])?></td>
          <td><?=htmlspecialchars($eq['categoria'])?></td>
          <td>
            <span class="badge bg-<?=($eq['estatus']==='Activo'?'success':'danger')?>">
              <?=htmlspecialchars($eq['estatus'])?>
            </span>
          </td>
          <td><?=htmlspecialchars($eq['fecha_validacion'])?></td>
          <td class="text-center">
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditar<?=$eq['id_equipo']?>">✏️</button>
            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalEliminar<?=$eq['id_equipo']?>">🗑️</button>
          </td>
        </tr>

        <!-- Modal Editar -->
        <div class="modal fade" id="modalEditar<?=$eq['id_equipo']?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form method="post">
                <div class="modal-header bg-warning">
                  <h5 class="modal-title">✏️ Editar</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="accion" value="editar">
                  <input type="hidden" name="id_equipo" value="<?=$eq['id_equipo']?>">
                  <div class="mb-2"><label>Nombre</label><input type="text" class="form-control" name="nombre" value="<?=$eq['nombre']?>" required></div>
                  <div class="mb-2"><label>Descripción</label><textarea class="form-control" name="descripcion"><?=$eq['descripcion']?></textarea></div>
                  <div class="mb-2"><label>Cliente</label><input type="text" class="form-control" name="cliente" value="<?=$eq['cliente']?>"></div>
                  <div class="mb-2"><label>Categoría</label><input type="text" class="form-control" name="categoria" value="<?=$eq['categoria']?>"></div>
                  <div class="mb-2"><label>Estatus</label>
                    <select class="form-select" name="estatus">
                      <option <?=$eq['estatus']==='Activo'?'selected':''?>>Activo</option>
                      <option <?=$eq['estatus']==='Inactivo'?'selected':''?>>Inactivo</option>
                    </select>
                  </div>
                  <div class="mb-2"><label>Fecha Validación</label><input type="date" class="form-control" name="fecha_validacion" value="<?=$eq['fecha_validacion']?>"></div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-warning">Guardar Cambios</button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Modal Eliminar -->
        <div class="modal fade" id="modalEliminar<?=$eq['id_equipo']?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form method="post">
                <div class="modal-header bg-danger text-white">
                  <h5 class="modal-title">⚠️ Eliminar</h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="id_equipo" value="<?=$eq['id_equipo']?>">
                  ¿Seguro que deseas eliminar <strong><?=$eq['nombre']?></strong>?
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-danger">Eliminar</button>
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

<!-- Modal Agregar -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">➕ Nuevo</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="accion" value="agregar">
          <div class="mb-2"><label>Nombre</label><input type="text" class="form-control" name="nombre" required></div>
          <div class="mb-2"><label>Descripción</label><textarea class="form-control" name="descripcion"></textarea></div>
          <div class="mb-2"><label>Cliente</label><input type="text" class="form-control" name="cliente"></div>
          <div class="mb-2"><label>Categoría</label><input type="text" class="form-control" name="categoria"></div>
          <div class="mb-2"><label>Estatus</label>
            <select class="form-select" name="estatus">
              <option>Activo</option>
              <option>Inactivo</option>
            </select>
          </div>
          <div class="mb-2"><label>Fecha Validación</label><input type="date" class="form-control" name="fecha_validacion"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Agregar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
  const tabla = new DataTable('#tablaEquipos', {
    pageLength: 10,
    language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
  });
</script>
