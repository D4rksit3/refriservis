$(document).ready(function(){

    var tabla = $('#tablaProductos').DataTable({
        processing:true,
        serverSide:true,
        ajax:'productos_data.php',
        columns:[
            {data:'productos_id'},
            {data:'Nombre'},
            {data:'Categoria'},
            {data:'Estatus'},
            {data:'Valor_unitario'},
            {data:'acciones', orderable:false, searchable:false}
        ],
        language:{ url:'//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
    });

    // AGREGAR
    $('#formAgregar').submit(function(e){
        e.preventDefault();
        $.post('productos_crud.php', $(this).serialize(), function(resp){
            tabla.ajax.reload();
            $('#modalAgregar').modal('hide');
            $('#formAgregar')[0].reset();
        });
    });

    // EDITAR - abrir modal
    $('#tablaProductos').on('click', '.editar', function(){
        var id = $(this).data('id');
        $.getJSON('productos_data.php', function(resp){
            var producto = resp.data.find(p => p.productos_id == id);
            if(producto){
                $('#editId').val(producto.productos_id);
                $('#editNombre').val(producto.Nombre);
                $('#editCategoria').val(producto.Categoria);
                $('#editEstatus').val(producto.Estatus);
                $('#editValor').val(producto.Valor_unitario);
                $('#modalEditar').modal('show');
            }
        });
    });

    // EDITAR - enviar
    $('#formEditar').submit(function(e){
        e.preventDefault();
        $.post('productos_crud.php', $(this).serialize(), function(resp){
            tabla.ajax.reload();
            $('#modalEditar').modal('hide');
        });
    });

    // ELIMINAR - abrir modal
    $('#tablaProductos').on('click', '.eliminar', function(){
        var id = $(this).data('id');
        $('#deleteId').val(id);
        $('#modalEliminar').modal('show');
    });

    // ELIMINAR - enviar
    $('#formEliminar').submit(function(e){
        e.preventDefault();
        $.post('productos_crud.php', $(this).serialize(), function(resp){
            tabla.ajax.reload();
            $('#modalEliminar').modal('hide');
        });
    });

});
