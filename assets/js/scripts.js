$(document).ready(function () {

    // ===============================
    // Tabla Equipos
    // ===============================
    var tablaEquipos = null;
    if (!$.fn.DataTable.isDataTable('#tablaEquipos')) {
        tablaEquipos = $('#tablaEquipos').DataTable({
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
        tablaEquipos = $('#tablaEquipos').DataTable();
    }

    // Delegated events Equipos
    $(document).on('click', '#tablaEquipos .editar', function () {
        var data = tablaEquipos.row($(this).closest('tr')).data();
        if (!data) return;

        $('#editId').val(data.id_equipo);
        $('#editNombre').val(data.Nombre);
        $('#editDescripcion').val(data.Descripcion);
        $('#editCliente').val(data.Cliente);
        $('#editCategoria').val(data.Categoria);
        $('#editEstatus').val(data.Estatus);
        $('#editFecha').val(data.Fecha_validad);
        $('#modalEditar').modal('show');
    });

    $(document).on('click', '#tablaEquipos .eliminar', function () {
        var data = tablaEquipos.row($(this).closest('tr')).data();
        if (!data) return;
        $('#deleteId').val(data.id_equipo);
        $('#modalEliminar').modal('show');
    });

    // ===============================
    // Tabla Productos
    // ===============================
    var tablaProductos = null;
    if (!$.fn.DataTable.isDataTable('#tablaProductos')) {
        tablaProductos = $('#tablaProductos').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'productos_data.php',
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
            ]
        });
    } else {
        tablaProductos = $('#tablaProductos').DataTable();
    }

    // Delegated events Productos
    $(document).on('click', '#tablaProductos .editar', function() {
        var data = tablaProductos.row($(this).closest('tr')).data();
        if (!data) return;

        $('#editId').val(data.productos_id);
        $('#editNombre').val(data.Nombre);
        $('#editCategoria').val(data.Categoria);
        $('#editEstatus').val(data.Estatus);
        $('#editValor').val(data.Valor_unitario);
        $('#modalEditar').modal('show');
    });

    $(document).on('click', '#tablaProductos .eliminar', function() {
        var data = tablaProductos.row($(this).closest('tr')).data();
        if (!data) return;

        $('#deleteId').val(data.productos_id);
        $('#modalEliminar').modal('show');
    });

});
