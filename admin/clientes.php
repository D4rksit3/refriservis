<?php
// admin/clientes.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
  header('Location: /index.php');
  exit;
}
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

// Consultar clientes
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if (!in_array($limit, [10, 20, 100])) $limit = 10;

$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM clientes";
$params = [];

if ($search) {
    $sql .= " WHERE cliente LIKE ? OR direccion LIKE ? OR telefono LIKE ? OR responsable LIKE ? OR email LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"];
}
$sql .= " ORDER BY ultima_visita DESC LIMIT ?";
$params[] = $limit;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="fw-bold">Gestión de Clientes</h2>
    <div>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregar">➕ Nuevo Cliente</button>
      <a href="exportar_clientes.php" class="btn btn-success">📤 Exportar</a>
    </div>
  </div>

  <!-- Filtro y búsqueda -->
  <form method="get" class="row g-2 mb-3">
    <div class="col-md-3">
      <select name="limit" class="form-select" onchange="this.form.submit()">
        <option value="10" <?= $limit==10?'selected':'' ?>>Últimos 10</option>
        <option value="20" <?= $limit==20?'selected':'' ?>>Últimos 20</option>
        <option value="100" <?= $limit==100?'selected':'' ?>>Últimos 100</option>
      </select>
    </div>
    <div class="col-md-6">
      <input type="text" name="search" value="<?=htmlspecialchars($search)?>" class="form-control" placeholder="Buscar cliente, dirección, teléfono...">
    </div>
    <div class="col-md-3">
      <button class="btn btn-outline-secondary w-100">Buscar</button>
    </div>
  </form>

  <!-- Tabla -->
  <div class="table-responsive shadow rounded">
    <table class="table table-hover align-middle">
      <thead class="table-dark">
        <tr>
          <th>Cliente</th>
          <th>Dirección</th>
          <th>Teléfono</th>
          <th>Responsable</th>
          <th>Email</th>
          <th>Última Visita</th>
          <th>Estatus</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($clientes): ?>
          <?php foreach ($clientes as $c): ?>
          <tr>
            <td><?= htmlspecialchars($c['cliente']) ?></td>
            <td><?= htmlspecialchars($c['direccion']) ?></td>
            <td><?= htmlspecialchars($c['telefono']) ?></td>
            <td><?= htmlspecialchars($c['responsable']) ?></td>
            <td><?= htmlspecialchars($c['email']) ?></td>
            <td><?= htmlspecialchars($c['ultima_visita']) ?></td>
            <td>
              <span class="badge <?= $c['estatus']=='Activo'?'bg-success':'bg-danger' ?>">
                <?= htmlspecialchars($c['estatus']) ?>
              </span>
            </td>
            <td>
              <button class="btn btn-warning btn-sm" 
                      data-bs-toggle="modal" 
                      data-bs-target="#modalEditar"
                      data-id="<?=$c['id']?>"
                      data-cliente="<?=$c['cliente']?>"
                      data-direccion="<?=$c['direccion']?>"
                      data-telefono="<?=$c['telefono']?>"
                      data-responsable="<?=$c['responsable']?>"
                      data-email="<?=$c['email']?>"
                      data-estatus="<?=$c['estatus']?>">✏️ Editar</button>

              <button class="btn btn-danger btn-sm" 
                      data-bs-toggle="modal" 
                      data-bs-target="#modalEliminar"
                      data-id="<?=$c['id']?>"
                      data-cliente="<?=$c['cliente']?>">🗑️ Eliminar</button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="8" class="text-center text-muted">No hay resultados</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Agregar -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="post" action="procesar_cliente.php?action=agregar" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Agregar Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-3">
        <div class="col-md-6"><input name="cliente" class="form-control" placeholder="Cliente" required></div>
        <div class="col-md-6"><input name="direccion" class="form-control" placeholder="Dirección"></div>
        <div class="col-md-6"><input name="telefono" class="form-control" placeholder="Teléfono"></div>
        <div class="col-md-6"><input name="responsable" class="form-control" placeholder="Responsable"></div>
        <div class="col-md-6"><input type="email" name="email" class="form-control" placeholder="Email"></div>
        <div class="col-md-6">
          <select name="estatus" class="form-select">
            <option value="Activo">Activo</option>
            <option value="Inactivo">Inactivo</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form method="post" action="procesar_cliente.php?action=editar" class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">Editar Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-3">
        <input type="hidden" name="id" id="edit-id">
        <div class="col-md-6"><input name="cliente" id="edit-cliente" class="form-control" required></div>
        <div class="col-md-6"><input name="direccion" id="edit-direccion" class="form-control"></div>
        <div class="col-md-6"><input name="telefono" id="edit-telefono" class="form-control"></div>
        <div class="col-md-6"><input name="responsable" id="edit-responsable" class="form-control"></div>
        <div class="col-md-6"><input type="email" name="email" id="edit-email" class="form-control"></div>
        <div class="col-md-6">
          <select name="estatus" id="edit-estatus" class="form-select">
            <option value="Activo">Activo</option>
            <option value="Inactivo">Inactivo</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-warning">Actualizar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="procesar_cliente.php?action=eliminar" class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Confirmar Eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>¿Seguro que deseas eliminar al cliente <strong id="del-cliente"></strong>?</p>
        <input type="hidden" name="id" id="del-id">
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-danger">Eliminar</button>
      </div>
    </form>
  </div>
</div>

<script>
const modalEditar = document.getElementById('modalEditar');
modalEditar.addEventListener('show.bs.modal', e => {
  let btn = e.relatedTarget;
  document.getElementById('edit-id').value = btn.dataset.id;
  document.getElementById('edit-cliente').value = btn.dataset.cliente;
  document.getElementById('edit-direccion').value = btn.dataset.direccion;
  document.getElementById('edit-telefono').value = btn.dataset.telefono;
  document.getElementById('edit-responsable').value = btn.dataset.responsable;
  document.getElementById('edit-email').value = btn.dataset.email;
  document.getElementById('edit-estatus').value = btn.dataset.estatus;
});

const modalEliminar = document.getElementById('modalEliminar');
modalEliminar.addEventListener('show.bs.modal', e => {
  let btn = e.relatedTarget;
  document.getElementById('del-id').value = btn.dataset.id;
  document.getElementById('del-cliente').textContent = btn.dataset.cliente;
});
</script>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
