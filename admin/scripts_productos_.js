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

    // -------- FUNCION CERRAR MODAL + LIMPIEZA --------
    function cerrarModal(modalId) {
        var modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
        if(modal){ modal.hide(); }
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
        $('body').css('overflow', 'auto');
    }

    // -------- AGREGAR --------
    

    // -------- EDITAR (abrir modal) --------
    $('#tablaProductos').on('click', '.editar', function(){
        var id = $(this).data('id');
        $.getJSON('productos_crud.php', { action:'get', id:id }, function(p){
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
        $.post('productos_crud.php', $(this).serialize(), function(resp){
            tabla.ajax.reload(null,false);
            cerrarModal('modalEditar');
        }, 'json');
    });

    // -------- ELIMINAR --------
    $('#tablaProductos').on('click', '.eliminar', function(){
        var id = $(this).data('id');
        $('#deleteId').val(id);
        var modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
        modal.show();
    });

    $('#formEliminar').submit(function(e){
        e.preventDefault();
        $.post('productos_crud.php', $(this).serialize(), function(resp){
            tabla.ajax.reload(null,false);
            cerrarModal('modalEliminar');
        }, 'json');
    });

});
