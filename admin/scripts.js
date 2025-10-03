// scripts.js - unificado para equipos y productos
$(document).ready(function(){

    // ---------- TABLE: EQUIPOS ----------
    var tablaEquipos = $('#tablaEquipos').length ? $('#tablaEquipos').DataTable({
        processing: true,
       /*  serverSide: true, */
        ajax: {
            url: 'equipos_data.php',
            type: 'GET'

        },
        columns: [
            {data:'id_equipo'},
            {data:'Identificador'},
            {data:'Nombre'},
            {data:'marca'},
            {data:'modelo'},
            {data:'ubicacion'},
            {data:'voltaje'},
            {data:'Descripcion'},
            {data:'Cliente'},
            {data:'Categoria'},
            {data:'Estatus'},
            /* {data:'Fecha_validad'}, */
            {data:'acciones', orderable:false, searchable:false}
        ],
        language:{ url:'//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
    }) : null;



    // ---------- UTILS: cerrar modal y limpiar backdrop ----------
    function cerrarModalById(modalId){
        var el = document.getElementById(modalId);
        if(!el) return;
        var modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
        modal.hide();
        // forzar limpieza (seguro)
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
        $('body').css('overflow','auto');
        $('body').css('padding-right','');
    }

    // Limpieza global cuando se oculta cualquier modal (backup)
    $(document).on('hidden.bs.modal', '.modal', function(){
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
        $('body').css('overflow','auto');
        $('body').css('padding-right','');
    });

    // -------------- EQUIPOS: AGREGAR --------------
    // ---------------- EQUIPOS: AGREGAR ----------------
    // Primero eliminamos cualquier submit previo para evitar duplicados
    $(document).off('submit', '#formAgregarEquipo');

    $(document).on('submit', '#formAgregarEquipo', function(e){
        e.preventDefault();

        $.post('equipos_crud.php', $(this).serialize(), function(resp){
            if(resp.success){
                // Cierra modal
                $('#modalAgregar').modal('hide');

                // Recarga tabla cuando se cierre
                $('#modalAgregar').one('hidden.bs.modal', function(){
                    if (tablaEquipos) tablaEquipos.ajax.reload(null, false);
                    $('#formAgregarEquipo')[0].reset(); // limpiar form
                });

            } else {
                alert(resp.message ? resp.message : 'Error al agregar');
            }
        }, 'json').fail(function(){
            alert('Error de red al agregar');
        });
    });


    // -------------- EQUIPOS: EDITAR --------------
    // abrir edit - delegación
    $(document).on('click', '.editar-equipo', function(){
        var id = $(this).data('id');
        if(!id) return;
        $.getJSON('equipos_crud.php', {id: id}, function(row){
            if(row){
                $('#editIdEquipo').val(row.id_equipo);
                $('#editIdentificador').val(row.Identificador);
                $('#editNombreEquipo').val(row.Nombre);
                $('#editmarca').val(row.marca);
                $('#editmodelo').val(row.modelo);
                $('#editubicacion').val(row.ubicacion);
                $('#editvoltaje').val(row.voltaje);
                $('#editDescripcionEquipo').val(row.Descripcion);
                $('#editClienteEquipo').val(row.Cliente);
                $('#editCategoriaEquipo').val(row.Categoria);
                $('#editEstatusEquipo').val(row.Estatus);
                $('#editFechaEquipo').val(row.Fecha_validad);
                var modal = new bootstrap.Modal(document.getElementById('modalEditarEquipo'));
                modal.show();
            }
        }).fail(function(){ alert('No se pudo cargar datos'); });
    });

    $('#formEditarEquipo').on('submit', function(e){
        e.preventDefault();
        $.post('equipos_crud.php', $(this).serialize(), function(resp){
            if(resp && resp.success){
                if(tablaEquipos) tablaEquipos.ajax.reload(null,false);
                cerrarModalById('modalEditarEquipo');
            } else {
                alert(resp && resp.message ? resp.message : 'Error al editar');
            }
        }, 'json').fail(function(){ alert('Error de red'); });
    });

    // -------------- EQUIPOS: ELIMINAR --------------
    $(document).on('click', '.eliminar-equipo', function(){
        var id = $(this).data('id');
        $('#deleteIdEquipo').val(id);
        var modal = new bootstrap.Modal(document.getElementById('modalEliminarEquipo'));
        modal.show();
    });

    $('#formEliminarEquipo').on('submit', function(e){
        e.preventDefault();
        $.post('equipos_crud.php', $(this).serialize(), function(resp){
            if(resp && resp.success){
                if(tablaEquipos) tablaEquipos.ajax.reload(null,false);
                cerrarModalById('modalEliminarEquipo');
            } else {
                alert(resp && resp.message ? resp.message : 'Error al eliminar');
            }
        }, 'json').fail(function(){ alert('Error de red'); });
    });

    // -------------- PRODUCTOS: AGREGAR --------------
    $('#formAgregar').on('submit', function(e){
        e.preventDefault();
        $.post('productos_crud.php', $(this).serialize(), function(resp){
            if(resp && resp.success){
                $('#formAgregar')[0].reset();
                if(tablaProductos) tablaProductos.ajax.reload(null,false);
                cerrarModalById('modalAgregar');
            } else {
                alert(resp && resp.message ? resp.message : 'Error al agregar producto');
            }
        }, 'json').fail(function(){ alert('Error de red'); });
    });

    // -------------- PRODUCTOS: EDITAR --------------
    $(document).on('click', '.editar-producto', function(){
        var id = $(this).data('id');
        if(!id) return;
        $.getJSON('productos_crud.php', {id: id}, function(row){
            if(row){
                $('#editId').val(row.productos_id);
                $('#editIdentificador').val(row.productos_Identificador);   
                $('#editNombre').val(row.Nombre);   
                $('#editmarca').val(row.marca);   
                $('#editmodelo').val(row.modelo);   
                $('#editubicacion').val(row.ubicacion);   
                $('#editvoltaje').val(row.voltaje);
                $('#editCategoria').val(row.Categoria);
                $('#editEstatus').val(row.Estatus);
                $('#editValor').val(row.Valor_unitario);
                var modal = new bootstrap.Modal(document.getElementById('modalEditar'));
                modal.show();
            }
        }).fail(function(){ alert('No se pudo cargar producto'); });
    });

    $('#formEditar').on('submit', function(e){
        e.preventDefault();
        $.post('productos_crud.php', $(this).serialize(), function(resp){
            if(resp && resp.success){
                if(tablaProductos) tablaProductos.ajax.reload(null,false);
                cerrarModalById('modalEditar');
            } else {
                alert(resp && resp.message ? resp.message : 'Error al editar producto');
            }
        }, 'json').fail(function(){ alert('Error de red'); });
    });

    // -------------- PRODUCTOS: ELIMINAR --------------
    $(document).on('click', '.eliminar-producto', function(){
        var id = $(this).data('id');
        $('#deleteId').val(id);
        var modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
        modal.show();
    });

    $('#formEliminar').on('submit', function(e){
        e.preventDefault();
        $.post('productos_crud.php', $(this).serialize(), function(resp){
            if(resp && resp.success){
                if(tablaProductos) tablaProductos.ajax.reload(null,false);
                cerrarModalById('modalEliminar');
            } else {
                alert(resp && resp.message ? resp.message : 'Error al eliminar producto');
            }
        }, 'json').fail(function(){ alert('Error de red'); });
    });

});
