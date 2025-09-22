<?php
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';

// Procesar formulario (Agregar, Editar, Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    if ($accion === 'agregar') {
        $sql = "INSERT INTO productos 
        (nombre, descripcion, precio, stock, categoria, estatus) 
        VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['nombre'],
            $_POST['descripcion'],
            $_POST['precio'],
            $_POST['stock'],
            $_POST['categoria'],
            $_POST['estatus']
        ]);
    }

    if ($accion === 'editar') {
        $sql = "UPDATE productos SET 
        nombre=?, descripcion=?, precio=?, stock=?, categoria=?, estatus=? 
        WHERE id_producto=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['nombre'],
            $_POST['descripcion'],
            $_POST['precio'],
            $_POST['stock'],
            $_POST['categoria'],
            $_POST['estatus'],
            $_POST['id_producto']
        ]);
    }

    if ($accion === 'eliminar') {
        $sql = "DELETE FROM productos WHERE id_producto=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['id_producto']]);
    }
}

// Traer productos
$stmt = $pdo->query("SELECT * FROM productos ORDER BY id_producto DESC");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h4">📦 Inventario de Productos</h2>
  <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregar">➕ Nuevo Producto</button>
</div>

<div class="table-responsive shadow-sm">
  <table id="tablaProductos" class="table table-striped align-middle">
    <thead class="table-primary">
      <tr>
        <th>Nombre</th>
        <th>Descripción</th>
        <th>Precio</th>
        <th>Stock</th>
        <th>Categoría</th>
        <th>Estatus</th>
        <th class="text-center">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($productos as $p): ?>
        <tr>
          <td><?=htmlspecialchars($p['nombre'])?></td>
          <td><?=htmlspecialchars($p['descripcion'])?></td>
          <td>S/ <?=number_format($p['precio'],2)?></td>
          <td><?=htmlspecialchars($p['stock'])?></td>
          <td><?=htmlspecialchars($p['categoria'])?></td>
          <td>
            <span class="badge bg-<?=($p['estatus']==='Activo'?'success':'danger')?>">
              <?=htmlspecialchars($p['estatus'])?>
            </span>
          </td>
          <td class="text-center">
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditar<?=$p['id_producto']?>">✏️</button>
            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalEliminar<?=$p['id_producto']?>">🗑️</button>
          </td>
        </tr>

        <!-- Modal Editar -->
        <div class="modal fade" id="modalEditar<?=$p['id_producto']?>" tabindex="-1">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <form method="post">
                <div class="modal-header bg-warning">
                  <h5 class="modal-title">✏️ Editar Producto</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="accion" value="editar">
                  <input type="hidden" name="id_producto" value="<?=$p['id_producto']?>">
                  <div class="row g-2">
                    <div class="col-md-6"><label>Nombre</label><input type="text" class="form-control" name="nombre" value="<?=$p['nombre']?>" required></div>
                    <div class="col-md-6"><label>Precio</label><input type="number" step="0.01" class="form-control" name="precio" value="<?=$p['precio']?>" required></div>
                    <div class="col-12"><label>Descripción</label><textarea class="form-control" name="descripcion"><?=$p['descripcion']?></textarea></div>
                    <div class="col-md-6"><label>Stock</label><input type="number" class="form-control" name="stock" value="<?=$p['stock']?>"></div>
                    <div class="col-md-6"><label>Categoría</label><input type="text" class="form-control" name="categoria" value="<?=$p['categoria']?>"></div>
                    <div class="col-md-6"><label>Estatus</label>
                      <select class="form-select" name="estatus">
                        <option <?=$p['estatus']==='Activo'?'selected':''?>>Activo</option>
                        <option <?=$p['estatus']==='Inactivo'?'selected':''?>>Inactivo</option>
                      </select>
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
        </div>

        <!-- Modal Eliminar -->
        <div class="modal fade" id="modalEliminar<?=$p['id_producto']?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form method="post">
                <div class="modal-header bg-danger text-white">
                  <h5 class="modal-title">⚠️ Eliminar Producto</h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="id_producto" value="<?=$p['id_producto']?>">
                  ¿Seguro que deseas eliminar <strong><?=$p['nombre']?></strong>?
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
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">➕ Nuevo Producto</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="accion" value="agregar">
          <div class="row g-2">
            <div class="col-md-6"><label>Nombre</label><input type="text" class="form-control" name="nombre" required></div>
            <div class="col-md-6"><label>Precio</label><input type="number" step="0.01" class="form-control" name="precio" required></div>
            <div class="col-12"><label>Descripción</label><textarea class="form-control" name="descripcion"></textarea></div>
            <div class="col-md-6"><label>Stock</label><input type="number" class="form-control" name="stock"></div>
            <div class="col-md-6"><label>Categoría</label><input type="text" class="form-control" name="categoria"></div>
            <div class="col-md-6"><label>Estatus</label>
              <select class="form-select" name="estatus">
                <option>Activo</option>
                <option>Inactivo</option>
              </select>
            </div>
          </div>
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
  const tabla = new DataTable('#tablaProductos', {
    pageLength: 10,
    language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
  });
</script>
