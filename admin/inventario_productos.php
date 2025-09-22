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
    <h2 class="h4 mb-2">📋 Inventario de Productos</h2>
    <button class="btn btn-success btn-sm mb-2" data-bs-toggle="modal" data-bs-target="#modalAgregar">
      ➕ Nuevo
    </button>
  </div>

  <!-- Tabla responsive -->
  <div class="table-responsive shadow-sm rounded">
    <table id="tablaProductos" class="table table-striped table-bordered align-middle">
      <thead class="table-primary">
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Categoria</th>
          <th>Estatus</th>
          <th>Valor Unitario</th>
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
      <form id="formAgregar" method="post">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">➕ Nuevo Producto</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="accion" value="agregar">
          <div class="mb-2"><label>Nombre</label><input type="text" class="form-control" name="Nombre" required></div>
          <div class="mb-2"><label>Categoria</label><input type="text" class="form-control" name="Categoria"></div>
          <div class="mb-2"><label>Estatus</label>
            <select class="form-select" name="Estatus">
              <option>Activo</option>
              <option>Inactivo</option>
            </select>
          </div>
          <div class="mb-2"><label>Valor Unitario</label><input type="number" class="form-control" name="Valor_unitario"></div>
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
      <form id="formEditar" method="post">
        <div class="modal-header bg-warning">
          <h5 class="modal-title">✏️ Editar Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="accion" value="editar">
          <input type="hidden" name="productos_id" id="editId">
          <div class="mb-2"><label>Nombre</label><input type="text" class="form-control" id="editNombre" name="Nombre" required></div>
          <div class="mb-2"><label>Categoria</label><input type="text" class="form-control" id="editCategoria" name="Categoria"></div>
          <div class="mb-2"><label>Estatus</label>
            <select class="form-select" id="editEstatus" name="Estatus">
              <option>Activo</option>
              <option>Inactivo</option>
            </select>
          </div>
          <div class="mb-2"><label>Valor Unitario</label><input type="number" class="form-control" id="editValor" name="Valor_unitario"></div>
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
      <form id="formEliminar" method="post">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title">🗑️ Eliminar Producto</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="accion" value="eliminar">
          <input type="hidden" name="productos_id" id="deleteId">
          <p>¿Estás seguro de que deseas eliminar este producto?</p>
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

<script>
$(document).ready(function () {
    var tabla = $('#tablaProductos').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'productos_data.php?ajax=1',
            type: 'GET'
        },
        pageLength: 10,
        lengthMenu: [10,25,50,100],
        columns: [
            { data: 'productos_id' },
            { data: 'Nombre' },
            { data: 'Categoria' },
            { data: 'Estatus' },
            { data: 'Valor_unitario' },
            { data: 'acciones', orderable:false, searchable:false }
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
    });

    // AGREGAR
    $('#formAgregar').submit(function(e){
        e.preventDefault();
        $.post('productos_data.php', $(this).serialize(), function(){
            $('#modalAgregar').modal('hide');
            tabla.ajax.reload(null,false);
            $('#formAgregar')[0].reset();
        });
    });

    // EDITAR
    $(document).on('click','.btnEditar', function(){
        $('#editId').val($(this).data('id'));
        $('#editNombre').val($(this).data('nombre'));
        $('#editCategoria').val($(this).data('categoria'));
        $('#editEstatus').val($(this).data('estatus'));
        $('#editValor').val($(this).data('valor'));
        $('#modalEditar').modal('show');
    });

    $('#formEditar').submit(function(e){
        e.preventDefault();
        $.post('productos_data.php', $(this).serialize(), function(){
            $('#modalEditar').modal('hide');
            tabla.ajax.reload(null,false);
        });
    });

    // ELIMINAR
    $(document).on('click','.btnEliminar', function(){
        $('#deleteId').val($(this).data('id'));
        $('#modalEliminar').modal('show');
    });

    $('#formEliminar').submit(function(e){
        e.preventDefault();
        $.post('productos_data.php', $(this).serialize(), function(){
            $('#modalEliminar').modal('hide');
            tabla.ajax.reload(null,false);
        });
    });
});
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>
