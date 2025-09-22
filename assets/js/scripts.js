
$(document).ready(function () {
  // Inicializar DataTable con AJAX
  var tabla = $('#tablaEquipos').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: 'inventario_equipos.php?ajax=1',
        type: 'GET'
    },
    columns: [
        { data: 'id_equipo' },
        { data: 'Nombre' },
        { data: 'Descripcion' },
        { data: 'Cliente' },
        { data: 'Categoria' },
        { data: 'Estatus' },
        { data: 'Fecha_validad' },
        { data: 'acciones', orderable: false, searchable: false }
    ]
});

  // Editar
  $(document).on('click', '.btnEditar', function () {
    $('#editId').val($(this).data('id'));
    $('#editNombre').val($(this).data('nombre'));
    $('#editDescripcion').val($(this).data('descripcion'));
    $('#editCliente').val($(this).data('cliente'));
    $('#editCategoria').val($(this).data('categoria'));
    $('#editEstatus').val($(this).data('estatus'));
    $('#editFecha').val($(this).data('fecha'));
    $('#modalEditar').modal('show');
  });

  // Eliminar
  $(document).on('click', '.btnEliminar', function () {
    $('#deleteId').val($(this).data('id'));
    $('#modalEliminar').modal('show');
  });
});
