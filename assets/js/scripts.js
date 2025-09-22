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

  



















});
