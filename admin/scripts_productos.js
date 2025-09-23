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
        columnDefs:[{
            targets:5,
            render:function(data,type,row){
                return data; // renderizar HTML
            }
        }],
        language:{ url:'//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
    });

    // AGREGAR
    $('#formAgregarEquipo').on('submit', function(e){
    e.preventDefault();
    $.post('equipos_crud.php', $(this).serialize(), function(resp){
        if(resp.success){
            $('#formAgregarEquipo')[0].reset();
            $('#modalAgregar').one('hidden.bs.modal', function(){
                $('#tablaEquipos').DataTable().ajax.reload();
            }).modal('hide');
        } else {
            alert('Error al agregar');
        }
    }, 'json');
});

    // EDITAR - abrir modal con eventos delegados
    $('#tablaProductos').on('click', '.editar', function(){
        var id = $(this).data('id');
        $.getJSON('productos_data.php', function(resp){
            var p = resp.data.find(x => x.productos_id == id);
            if(p){
                $('#editId').val(p.productos_id);
                $('#editNombre').val(p.Nombre);
                $('#editCategoria').val(p.Categoria);
                $('#editEstatus').val(p.Estatus);
                $('#editValor').val(p.Valor_unitario);
                var modal = new bootstrap.Modal(document.getElementById('modalEditar'));
                modal.show();
            }
        });
    });

    $('#formEditar').submit(function(e){
        e.preventDefault();
        $.post('productos_crud.php', $(this).serialize(), function(){
            tabla.ajax.reload(null,false);
            var modal = bootstrap.Modal.getInstance(document.getElementById('modalEditar'));
            modal.hide();
        });
    });

    // ELIMINAR
    $('#tablaProductos').on('click', '.eliminar', function(){
        var id = $(this).data('id');
        $('#deleteId').val(id);
        var modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
        modal.show();
    });

    $('#formEliminar').submit(function(e){
        e.preventDefault();
        $.post('productos_crud.php', $(this).serialize(), function(){
            tabla.ajax.reload(null,false);
            var modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminar'));
            modal.hide();
        });
    });

});
