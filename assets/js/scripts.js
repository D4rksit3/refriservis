$(document).ready(function () {

    // Inicializar DataTable solo una vez y guardar la instancia
    var tabla = null;
    if (!$.fn.DataTable.isDataTable('#tablaEquipos')) {
        tabla = $('#tablaEquipos').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'equipos_data.php',
                type: 'GET'
            },
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            columns: [
                { data: 'id_equipo' },
                { data: 'Nombre' },
                { data: 'Descripcion' },
                { data: 'Cliente' },
                { data: 'Categoria' },
                { data: 'Estatus' },
                { data: 'Fecha_validad' },
                { data: 'acciones', orderable: false, searchable: false }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            }
        });
    } else {
        tabla = $('#tablaEquipos').DataTable();
    }

    // Delegated event para Editar
    $(document).on('click', '.editar', function () {
        var row = $(this).closest('tr');
        var data = tabla.row(row).data();
        if (!data) return; // prevenir errores si fila no existe

        $('#editId').val(data.id_equipo);
        $('#editNombre').val(data.Nombre);
        $('#editDescripcion').val(data.Descripcion);
        $('#editCliente').val(data.Cliente);
        $('#editCategoria').val(data.Categoria);
        $('#editEstatus').val(data.Estatus);
        $('#editFecha').val(data.Fecha_validad);
        $('#modalEditar').modal('show');
    });

    // Delegated event para Eliminar
    $(document).on('click', '.eliminar', function () {
        var row = $(this).closest('tr');
        var data = tabla.row(row).data();
        if (!data) return;

        $('#deleteId').val(data.id_equipo);
        $('#modalEliminar').modal('show');
    });

    $(document).ready(function () {
  // Inicializar DataTable
  var tabla = $('#tablaProductos').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: 'productos_data.php?ajax=1',
        type: 'GET'
    },
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    columns: [
        { data: 'productos_id' },
        { data: 'Nombre' },
        { data: 'Categoria' },
        { data: 'Estatus' },
        { data: 'Valor_unitario' },
        { data: 'acciones', orderable: false, searchable: false }
    ],
    language: {
        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
    },
    responsive: true
  });

  // Editar
  $(document).on('click', '.btnEditar', function () {
    $('#editId').val($(this).data('id'));
    $('#editNombre').val($(this).data('nombre'));
    $('#editCategoria').val($(this).data('categoria'));
    $('#editEstatus').val($(this).data('estatus'));
    $('#editValor').val($(this).data('valor'));
    $('#modalEditar').modal('show');
  });

  // Eliminar
  $(document).on('click', '.btnEliminar', function () {
    $('#deleteId').val($(this).data('id'));
    $('#modalEliminar').modal('show');
  });
});



});
