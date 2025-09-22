$(document).ready(function () {
    var tabla = $('#tablaEquipos').DataTable({
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

    // EDITAR - delegación para botones creados dinámicamente
    $('#tablaEquipos tbody').on('click', '.editar', function () {
        var tr = $(this).closest('tr');
        var rowData = tabla.row(tr).data();

        $('#editId').val(rowData.id_equipo);
        $('#editNombre').val(rowData.Nombre);
        $('#editDescripcion').val(rowData.Descripcion);
        $('#editCliente').val(rowData.Cliente);
        $('#editCategoria').val(rowData.Categoria);
        $('#editEstatus').val(rowData.Estatus);
        $('#editFecha').val(rowData.Fecha_validad);

        $('#modalEditar').modal('show');
    });

    // ELIMINAR - delegación para botones creados dinámicamente
    $('#tablaEquipos tbody').on('click', '.eliminar', function () {
        var tr = $(this).closest('tr');
        var rowData = tabla.row(tr).data();

        $('#deleteId').val(rowData.id_equipo);
        $('#modalEliminar').modal('show');
    });
});
