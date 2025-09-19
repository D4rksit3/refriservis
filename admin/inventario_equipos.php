<!-- Modal Equipo -->
<div class="modal fade" id="modalEquipo" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form id="formEquipo" method="post" action="procesar_equipo.php">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Equipo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_equipo" id="equipo_id">
          <div class="row">
            <div class="col-md-6 mb-2"><label>Nombre</label><input class="form-control" name="nombre" id="equipo_nombre" required></div>
            <div class="col-md-6 mb-2"><label>Identificador</label><input class="form-control" name="identificador" id="equipo_identificador"></div>
            <div class="col-md-12 mb-2"><label>Descripción</label><textarea class="form-control" name="descripcion" id="equipo_descripcion"></textarea></div>
            <div class="col-md-6 mb-2"><label>Colaborador</label><input class="form-control" name="colaborador" id="equipo_colaborador"></div>
            <div class="col-md-6 mb-2"><label>Cliente</label><input class="form-control" name="cliente" id="equipo_cliente"></div>
            <div class="col-md-6 mb-2"><label>Categoría</label><input class="form-control" name="categoria" id="equipo_categoria"></div>
            <div class="col-md-6 mb-2"><label>Equipo Asociado</label><input class="form-control" name="equipo_asociado" id="equipo_asociado"></div>
            <div class="col-md-6 mb-2"><label>Estatus</label><input class="form-control" name="estatus" id="equipo_estatus"></div>
            <div class="col-md-6 mb-2"><label>Planilla</label><input class="form-control" name="planilla" id="equipo_planilla"></div>
            <div class="col-md-6 mb-2"><label>Fecha Validación</label><input type="date" class="form-control" name="fecha_validacion" id="equipo_fecha"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-success">Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal Producto -->
<div class="modal fade" id="modalProducto" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form id="formProducto" method="post" action="procesar_producto.php">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_producto" id="producto_id">
          <div class="row">
            <div class="col-md-6 mb-2"><label>Nombre</label><input class="form-control" name="nombre" id="producto_nombre" required></div>
            <div class="col-md-6 mb-2"><label>Categoría</label><input class="form-control" name="categoria" id="producto_categoria"></div>
            <div class="col-md-12 mb-2"><label>Descripción</label><textarea class="form-control" name="descripcion" id="producto_descripcion"></textarea></div>
            <div class="col-md-6 mb-2"><label>Equipo</label><input class="form-control" name="equipo" id="producto_equipo"></div>
            <div class="col-md-6 mb-2"><label>Estatus</label><input class="form-control" name="estatus" id="producto_estatus"></div>
            <div class="col-md-6 mb-2"><label>Stock Actual</label><input type="number" class="form-control" name="stock_actual" id="producto_stock_actual"></div>
            <div class="col-md-6 mb-2"><label>Stock Mínimo</label><input type="number" class="form-control" name="stock_minimo" id="producto_stock_minimo"></div>
            <div class="col-md-6 mb-2"><label>Valor Unitario</label><input type="number" step="0.01" class="form-control" name="valor_unitario" id="producto_valor_unitario"></div>
            <div class="col-md-6 mb-2"><label>Costo Unitario</label><input type="number" step="0.01" class="form-control" name="costo_unitario" id="producto_costo_unitario"></div>
            <div class="col-md-6 mb-2"><label>Entrada de Stock</label><input type="date" class="form-control" name="entrada_stock" id="producto_entrada"></div>
            <div class="col-md-6 mb-2"><label>Planilla</label><input class="form-control" name="planilla" id="producto_planilla"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-success">Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal Servicio -->
<div class="modal fade" id="modalServicio" tabindex="-1">
  <div class="modal-dialog">
    <form id="formServicio" method="post" action="procesar_servicio.php">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Servicio</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_servicio" id="servicio_id">
          <div class="mb-2"><label>Nombre</label><input class="form-control" name="nombre" id="servicio_nombre" required></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-success">Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </div>
    </form>
  </div>
</div>
