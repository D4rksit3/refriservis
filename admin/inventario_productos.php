<?php
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../includes/header.php';

// Procesar formulario (Agregar, Editar, Eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    if ($accion === 'agregar') {
        $sql = "INSERT INTO productos 
        (nombre, descripcion, categoria, equipo, estatus, stock_actual, stock_minimo, valor_unitario, entrada_stock, planilla_especificaciones, costo_unitario) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['nombre'],
            $_POST['descripcion'],
            $_POST['categoria'],
            $_POST['equipo'],
            $_POST['estatus'],
            $_POST['stock_actual'],
            $_POST['stock_minimo'],
            $_POST['valor_unitario'],
            $_POST['entrada_stock'],
            $_POST['planilla_especificaciones'],
            $_POST['costo_unitario']
        ]);
    }

    if ($accion === 'editar') {
        $sql = "UPDATE productos SET 
        nombre=?, descripcion=?, categoria=?, equipo=?, estatus=?, stock_actual=?, stock_minimo=?, valor_unitario=?, entrada_stock=?, planilla_especificaciones=?, costo_unitario=? 
        WHERE id_producto=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['nombre'],
            $_POST['descripcion'],
            $_POST['categoria'],
            $_POST['equipo'],
            $_POST['estatus'],
            $_POST['stock_actual'],
            $_POST['stock_minimo'],
            $_POST['valor_unitario'],
            $_POST['entrada_stock'],
            $_POST['planilla_especificaciones'],
            $_POST['costo_unitario'],
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
  <h2 class="h4">📦 Gestión de Productos</h2>
  <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregar">➕ Nuevo Producto</button>
</div>

<div class="table-responsive shadow-sm">
  <table id="tablaProductos" class="table table-striped align-middle">
    <thead class="table-primary">
      <tr>
        <th>Nombre</th>
        <th>Categoría</th>
        <th>Equipo</th>
        <th>Estatus</th>
        <th>Stock Actual</th>
        <th>Stock Mínimo</th>
        <th>Valor Unitario</th>
        <th>Costo Unitario</th>
        <th>Entrada Stock</th>
        <th>Planilla</th>
        <th class="text-center">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($productos as $p): ?>
        <tr>
          <td><?=htmlspecialchars($p['nombre'])?></td>
          <td><?=htmlspecialchars($p['categoria'])?></td>
          <td><?=htmlspecialchars($p['equipo'])?></td>
          <td>
            <span class="badge bg-<?=
                $p['estatus']==='Disponible' ? 'success' : 
                ($p['estatus']==='Agotado' ? 'danger' : 'warning')
            ?>">
              <?=htmlspecialchars($p['estatus'])?>
            </span>
          </td>
          <td><?=htmlspecialchars($p['stock_actual'])?></td>
          <td><?=htmlspecialchars($p['stock_minimo'])?></td>
          <td>S/ <?=number_format($p['valor_unitario'],2)?></td>
          <td>S/ <?=number_format($p['costo_unitario'],2)?></td>
          <td><?=htmlspecialchars($p['entrada_stock'])?></td>
          <td><?=htmlspecialchars($p['planilla_especificaciones'])?></td>
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
                    <div class="col-md-6"><label>Categoría</label><input type="text" class="form-control" name="categoria" value="<?=$p['categoria']?>"></div>
                    <div class="col-md-6"><label>Equipo</label><input type="text" class="form-control" name="equipo" value="<?=$p['equipo']?>"></div>
                    <div class="col-md-6"><label>Estatus</label>
                      <select class="form-select" name="estatus">
                        <option <?=$p['estatus']==='Disponible'?'selected':''?>>Disponible</option>
                        <option <?=$p['estatus']==='Agotado'?'selected':''?>>Agotado</option>
                        <option <?=$p['estatus']==='Mantenimiento'?'selected':''?>>Mantenimiento</option>
                      </select>
                    </div>
                    <div class="col-md-6"><label>Stock Actual</label><input type="number" class="form-control" name="stock_actual" value="<?=$p['stock_actual']?>"></div>
                    <div class="col-md-6"><label>Stock Mínimo</label><input type="number" class="form-control" name="stock_minimo" value="<?=$p['stock_minimo']?>"></div>
                    <div class="col-md-6"><label>Valor Unitario (S/)</label><input type="number" step="0.01" class="form-control" name="valor_unitario" value="<?=$p['valor_unitario']?>"></div>
                    <div class="col-md-6"><label>Costo Unitario (S/)</label><input type="number" step="0.01" class="form-control" name="costo_unitario" value="<?=$p['costo_unitario']?>"></div>
                    <div class="col-md-6"><label>Entrada Stock</label><input type="date" class="form-control" name="entrada_stock" value="<?=$p['entrada_stock']?>"></div>
                    <div class="col-12"><label>Planilla</label><textarea class="form-control" name="planilla_especificaciones"><?=$p['planilla_especificaciones']?></textarea></div>
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
            <div class="col-md-6"><label>Categoría</label><input type="text" class="form-control" name="categoria"></div>
            <div class="col-md-6"><label>Equipo</label><input type="text" class="form-control" name="equipo"></div>
            <div class="col-md-6"><label>Estatus</label>
              <select class="form-select" name="estatus">
                <option>Disponible</option>
                <option>Agotado</option>
                <option>Mantenimiento</option>
              </select>
            </div>
            <div class="col-md-6"><label>Stock Actual</label><input type="number" class="form-control" name="stock_actual" value="0"></div>
            <div class="col-md-6"><label>Stock Mínimo</label><input type="number" class="form-control" name="stock_minimo" value="0"></div>
            <div class="col-md-6"><label>Valor Unitario (S/)</label><input type="number" step="0.01" class="form-control" name="valor_unitario" value="0.00"></div>
            <div class="col-md-6"><label>Costo Unitario (S/)</label><input type="number" step="0.01" class="form-control" name="costo_unitario" value="0.00"></div>
            <div class="col-md-6"><label>Entrada Stock</label><input type="date" class="form-control" name="entrada_stock"></div>
            <div class="col-12"><label>Planilla</label><textarea class="form-control" name="planilla_especificaciones"></textarea></div>
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
