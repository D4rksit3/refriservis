<?php
// ==========================
// CONFIGURACIÓN Y CONEXIÓN
// ==========================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';
?>

<div class="container my-4">

  <!-- Título y botón -->
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
    <h2 class="h4 mb-2">📋 Inventario de Equipos</h2>
    <button class="btn btn-success btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#modalAgregar">
      ➕ Nuevo
    </button>
  </div>

  <!-- Tabla responsive -->
  <div class="table-responsive shadow-sm rounded">
    <table id="tablaEquipos" class="table table-striped table-bordered align-middle">
      <thead class="table-primary">
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Descripcion</th>
          <th>Cliente</th>
          <th>Categoria</th>
          <th>Estatus</th>
          <th>Fecha Validación</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

</div>

<!-- ========================== -->
<!-- MODALES AGREGAR / EDITAR / ELIMINAR -->
<!-- ========================== -->

<!-- Modal Agregar -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">➕ Nuevo Equipo</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="accion" value="agregar">
          <div class="mb-2"><label>Nombre</label><input type="text" class="form-control" name="Nombre" required></div>
          <div class="mb-2"><label>Descripcion</label><textarea class="form-control" name="Descripcion"></textarea></div>
          <div class="mb-2"><label>Cliente</label><input type="text" class="form-control" name="Cliente"></div>
          <div class="mb-2"><label>Categoria</label><input type="text" class="form-control" name="Categoria"></div>
          <div class="mb-2"><label>Estatus</label>
            <select class="form-select" name="Estatus">
              <option>Activo</option>
              <option>Inactivo</option>
            </select>
          </div>
          <div class="mb-2"><label>Fecha Validación</label><input type="date" class="form-control" name="Fecha_validad"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Agregar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header bg-warning">
          <h5 class="modal-title">✏️ Editar Equipo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="accion" value="editar">
          <input type="hidden" name="id_equipo" id="editId">
          <div class="mb-2"><label>Nombre</label><input type="text" class="form-control" id="editNombre" name="Nombre" required></div>
          <div class="mb-2"><label>Descripcion</label><textarea class="form-control" id="editDescripcion" name="Descripcion"></textarea></div>
          <div class="mb-2"><label>Cliente</label><input type="text" class="form-control" id="editCliente" name="Cliente"></div>
          <div class="mb-2"><label>Categoria</label><input type="text" class="form-control" id="editCategoria" name="Categoria"></div>
          <div class="mb-2"><label>Estatus</label>
            <select class="form-select" id="editEstatus" name="Estatus">
              <option>Activo</option>
              <option>Inactivo</option>
            </select>
          </div>
          <div class="mb-2"><label>Fecha Validación</label><input type="date" class="form-control" id="editFecha" name="Fecha_validad"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">🗑️ Eliminar Equipo</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="accion" value="eliminar">
          <input type="hidden" name="id_equipo" id="deleteId">
          <p>¿Estás seguro de que deseas eliminar este equipo?</p>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-danger">Eliminar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================== -->
<!-- SCRIPTS -->
<!-- ========================== -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



<?php include __DIR__ . '/../includes/footer.php'; ?>
