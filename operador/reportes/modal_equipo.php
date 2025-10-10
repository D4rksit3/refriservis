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
          <div class="mb-2"><label>Marca</label><input type="text" class="form-control" name="marca" required></div>
          <div class="mb-2"><label>Modelo</label><input type="text" class="form-control" name="modelo" required></div>
          <div class="mb-2"><label>Ubicación</label><input type="text" class="form-control" name="ubicacion" required></div>
          <div class="mb-2"><label>Voltaje</label><input type="text" class="form-control" name="voltaje" required></div>
          <div class="mb-2"><label>Cliente</label><input type="text" class="form-control" name="Cliente"></div>
          <div class="mb-2"><label>Categoría</label><input type="text" class="form-control" name="Categoria"></div>
          <div class="mb-2"><label>Estatus</label>
            <select class="form-select" name="Estatus">
              <option>Activo</option>
              <option>Inactivo</option>
            </select>
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
