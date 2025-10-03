<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';
?>

<div class="container my-4">

  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
    <h2 class="h4 mb-2">📋 Inventario de Equipos</h2>
    <button class="btn btn-success btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#modalAgregarEquipo">
      ➕ Nuevo
    </button>
  </div>

  <div class="table-responsive shadow-sm rounded">
    <table id="tablaEquipos" class="table table-striped table-bordered align-middle">
      <thead class="table-primary">
        <tr>
          <th>ID</th>
          <th>Identificador</th>
          <th>Nombre</th>
          <th>Marca</th>
          <th>Modelo</th>
          <th>Ubicación</th>
          <th>Voltaje</th>
          <!-- <th>Descripción</th> -->
          <th>Cliente</th>
          <!-- <th>Categoría</th>
          <th>Estatus</th> -->
          <!-- <th>Fecha validación</th> -->
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

</div>

<!-- MODAL AGREGAR EQUIPO -->
<div class="modal fade" id="modalAgregarEquipo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formAgregarEquipo" method="post">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">➕ Nuevo Equipo</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="accion" value="agregar">
          <div class="mb-2"><label>Identificador</label><input type="text" class="form-control" name="Identificador" required></div>
          <div class="mb-2"><label>Nombre</label><input type="text" class="form-control" name="Nombre" required></div>

          <div class="mb-2"><label>marca</label><input type="text" class="form-control" name="marca" required></div>
          <div class="mb-2"><label>modelo</label><input type="text" class="form-control" name="modelo" required></div>
          <div class="mb-2"><label>ubicacion</label><input type="text" class="form-control" name="ubicacion" required></div>
          <div class="mb-2"><label>voltaje</label><input type="text" class="form-control" name="voltaje" required></div>
          <div class="mb-2"><label>Cliente</label><input type="text" class="form-control" name="Cliente"></div>
          <div class="mb-2"><label>Categoria</label><input type="text" class="form-control" name="Categoria"></div>
          <div class="mb-2"><label>Estatus</label>
            <select class="form-select" name="Estatus">
              <option>Activo</option>
              <option>Inactivo</option>
            </select>
          </div>
          <!-- <div class="mb-2"><label>Fecha Validación</label><input type="date" class="form-control" name="Fecha_validad"></div> -->
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Agregar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL EDITAR EQUIPO -->
<div class="modal fade" id="modalEditarEquipo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formEditarEquipo" method="post">
        <div class="modal-header bg-warning">
          <h5 class="modal-title">✏️ Editar Equipo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="accion" value="editar">
          <input type="hidden" name="id_equipo" id="editIdEquipo">
          <div class="mb-2"><label>Identificador</label><input type="text" class="form-control" id="editIdentificador" name="Identificador" required></div>
          <div class="mb-2"><label>Nombre</label><input type="text" class="form-control" id="editNombreEquipo" name="Nombre" required></div>
          <div class="mb-2"><label>marca</label><input type="text" class="form-control" id="editmarca" name="marca" required></div>
          <div class="mb-2"><label>modelo</label><input type="text" class="form-control" id="editmodelo" name="modelo" required></div>
          <div class="mb-2"><label>ubicacion</label><input type="text" class="form-control" id="editubicacion" name="ubicacion" required></div>
          <div class="mb-2"><label>voltaje</label><input type="text" class="form-control" id="editvoltaje" name="voltaje" required></div>
          
          <div class="mb-2"><label>Descripcion</label><textarea class="form-control" id="editDescripcionEquipo" name="Descripcion"></textarea></div>
          <div class="mb-2"><label>Cliente</label><input type="text" class="form-control" id="editClienteEquipo" name="Cliente"></div>
          <div class="mb-2"><label>Categoria</label><input type="text" class="form-control" id="editCategoriaEquipo" name="Categoria"></div>
          <div class="mb-2"><label>Estatus</label>
            <select class="form-select" id="editEstatusEquipo" name="Estatus">
              <option>Activo</option>
              <option>Inactivo</option>
            </select>
          </div>
          <div class="mb-2"><label>Fecha Validación</label><input type="date" class="form-control" id="editFechaEquipo" name="Fecha_validad"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Guardar Cambios</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL ELIMINAR EQUIPO -->
<div class="modal fade" id="modalEliminarEquipo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formEliminarEquipo" method="post">
        <input type="hidden" name="accion" value="eliminar">
        <input type="hidden" name="id_equipo" id="deleteIdEquipo">
        <div class="modal-body p-4">
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

<!-- SCRIPTS: jQuery, DataTables, Bootstrap 5, scripts.js (unificado) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="scripts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
