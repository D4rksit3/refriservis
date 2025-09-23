<?php
// ==========================
// CONFIGURACIÓN Y CONEXIÓN
// ==========================
require_once __DIR__.'/../config/db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Productos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
  <h2 class="mb-3">Gestión de Productos</h2>
  <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalAgregar">➕ Nuevo Producto</button>
  <div id="productosTable"></div>
</div>

<!-- Modal Agregar -->
<div class="modal fade" id="modalAgregar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Agregar Producto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formAgregar">
          <div class="mb-3">
            <label>Nombre</label>
            <input type="text" name="Nombre" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Categoría</label>
            <input type="text" name="Categoria" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Estatus</label>
            <select name="Estatus" class="form-control">
              <option value="Activo">Activo</option>
              <option value="Inactivo">Inactivo</option>
            </select>
          </div>
          <div class="mb-3">
            <label>Valor Unitario</label>
            <input type="number" name="Valor_unitario" class="form-control" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btnGuardar">Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar Producto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="editarContent">
        <!-- Se carga con AJAX -->
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function cargarProductos(){
  $("#productosTable").load("productos_data.php");
}

// Cargar tabla al inicio
$(document).ready(function(){
  cargarProductos();

  // Guardar producto
  $("#btnGuardar").click(function(){
    $.post("productos_actions.php", $("#formAgregar").serialize() + "&action=add", function(res){
      if(res.trim() == "ok"){
        $("#modalAgregar").modal("hide");
        $("#formAgregar")[0].reset();
        cargarProductos();
      } else {
        alert("Error: " + res);
      }
    });
  });

  // Cuando cierre modal, limpiar backdrop
  $('#modalAgregar, #modalEditar').on('hidden.bs.modal', function () {
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');
  });
});

// Eliminar producto
function eliminarProducto(id){
  if(confirm("¿Seguro que deseas eliminar este producto?")){
    $.post("productos_actions.php", {action:"delete", id:id}, function(res){
      if(res.trim() == "ok"){
        cargarProductos();
      } else {
        alert("Error: " + res);
      }
    });
  }
}

// Editar producto (abrir modal con datos)
function editarProducto(id){
  $("#editarContent").load("productos_actions.php?action=form&id="+id, function(){
    $("#modalEditar").modal("show");
  });
}

// Guardar edición
function guardarEdicion(id){
  $.post("productos_actions.php", $("#formEditar").serialize()+"&action=update&id="+id, function(res){
    if(res.trim()=="ok"){
      $("#modalEditar").modal("hide");
      cargarProductos();
    } else {
      alert("Error: "+res);
    }
  });
}
</script>
</body>
</html>
