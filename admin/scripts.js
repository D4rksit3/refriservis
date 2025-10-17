// scripts.js - unificado para equipos y productos
$(document).ready(function(){


    $('#btnExportar').click(function(){
    window.location.href = 'exportar_equipos.php';
    });

    let enviando = false; // declarar variable de control global para evitar doble envío


    // ---------- TABLE: EQUIPOS ----------
    var tablaEquipos = $('#tablaEquipos').length ? $('#tablaEquipos').DataTable({
        processing: true,
       serverSide: true,
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
            /* {data:'Descripcion'}, */
            {data:'Cliente'},
            /* {data:'Categoria'}, */
            /* {data:'Estatus'}, */
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
        // ---------- EQUIPOS: AGREGAR ----------
    $('#formAgregarEquipo').off('submit').on('submit', function (e) {
    e.preventDefault();
    console.log('EVENTO SUBMIT DISPARADO UNA SOLA VEZ ✅');

    const formData = new FormData(this);
    formData.append('accion', 'agregar');

    fetch('equipos_add_crud.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        console.log(data);
        if (data.success) {
            alert('Equipo agregado correctamente');
            $('#modalAgregar').modal('hide');
            $('#tablaEquipos').DataTable().ajax.reload();
        } else {
            alert('Error: ' + (data.message || 'No se pudo agregar'));
        }
    })
    .catch(err => console.error(err));
});




    // Limpieza global de backdrop al cerrar cualquier modal
    $(document).on('hidden.bs.modal', '.modal', function () {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
        $('body').css('overflow', 'auto');
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


   
});
