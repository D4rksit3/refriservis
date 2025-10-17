// scripts.js - unificado para equipos y productos
$(document).ready(function(){

    // ---------------- EXPORTAR ----------------
    $('#btnExportar').click(function(){
        window.location.href = 'exportar_equipos.php';
    });

    // ---------------- IMPORTAR ----------------
    $('#btnImportar').click(function(){
        $('#modalImportar').modal('show');
    });

    // ---------------- VARIABLES ----------------
    var tablaEquipos = null;
    let enviando = false; // evitar doble envío

    // ---------------- DATATABLE ----------------
    if ($('#tablaEquipos').length && !$.fn.DataTable.isDataTable('#tablaEquipos')) {
        tablaEquipos = $('#tablaEquipos').DataTable({
            processing: true,
            serverSide: true,
            ajax: { url: 'equipos_data.php', type: 'GET' },
            columns: [
                {data:'id_equipo'},
                {data:'Identificador'},
                {data:'Nombre'},
                {data:'marca'},
                {data:'modelo'},
                {data:'ubicacion'},
                {data:'voltaje'},
                {data:'Cliente'},
                {data:'acciones', orderable:false, searchable:false}
            ],
            language:{ url:'//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
        }).destroy;
    }

    // ---------------- UTILS: CERRAR MODAL ----------------
    function cerrarModalById(modalId){
        var el = document.getElementById(modalId);
        if(!el) return;
        var modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
        modal.hide();
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css({'overflow':'auto','padding-right':''});
    }

    // limpieza global al ocultar cualquier modal
    $(document).on('hidden.bs.modal', '.modal', function(){
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css({'overflow':'auto','padding-right':''});
    });

    // ---------------- EQUIPOS: AGREGAR ----------------
    $('#formAgregarEquipo').off('submit').on('submit', function(e){
        e.preventDefault();
        if(enviando) return;
        enviando = true;

        const formData = new FormData(this);
        formData.append('accion','agregar');

        fetch('equipos_add_crud.php', { method:'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            console.log(data);
            if(data.success){
                alert('Equipo agregado correctamente');
                cerrarModalById('modalAgregarEquipo');
                if(tablaEquipos) tablaEquipos.ajax.reload(null,false);
            } else {
                alert('Error: ' + (data.message || 'No se pudo agregar'));
            }
        })
        .catch(err => console.error(err))
        .finally(()=> enviando = false);
    });

    // ---------------- EQUIPOS: EDITAR ----------------
    $(document).on('click', '.editar-equipo', function(){
        var id = $(this).data('id');
        if(!id) return;
        $.getJSON('equipos_crud.php', {id:id}, function(row){
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
                new bootstrap.Modal(document.getElementById('modalEditarEquipo')).show();
            }
        }).fail(()=> alert('No se pudo cargar datos'));
    });

    $('#formEditarEquipo').on('submit', function(e){
        e.preventDefault();
        if(enviando) return;
        enviando = true;

        $.post('equipos_crud.php', $(this).serialize(), function(resp){
            if(resp && resp.success){
                if(tablaEquipos) tablaEquipos.ajax.reload(null,false);
                cerrarModalById('modalEditarEquipo');
            } else {
                alert(resp && resp.message ? resp.message : 'Error al editar');
            }
        }, 'json').fail(()=> alert('Error de red'))
        .always(()=> enviando=false);
    });

    // ---------------- EQUIPOS: ELIMINAR ----------------
    $(document).on('click', '.eliminar-equipo', function(){
        var id = $(this).data('id');
        $('#deleteIdEquipo').val(id);
        new bootstrap.Modal(document.getElementById('modalEliminarEquipo')).show();
    });

    $('#formEliminarEquipo').on('submit', function(e){
        e.preventDefault();
        if(enviando) return;
        enviando = true;

        $.post('equipos_crud.php', $(this).serialize(), function(resp){
            if(resp && resp.success){
                if(tablaEquipos) tablaEquipos.ajax.reload(null,false);
                cerrarModalById('modalEliminarEquipo');
            } else {
                alert(resp && resp.message ? resp.message : 'Error al eliminar');
            }
        }, 'json').fail(()=> alert('Error de red'))
        .always(()=> enviando=false);
    });

    // ---------------- EQUIPOS: IMPORTAR ----------------
    $('#formImportarEquipo').on('submit', function(e){
        e.preventDefault();
        if(enviando) return;
        enviando = true;

        const formData = new FormData(this);
        formData.append('accion','importar');

        fetch('equipos_import_crud.php', { method:'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success){
                alert('Archivo importado correctamente');
                cerrarModalById('modalImportar');
                if(tablaEquipos) tablaEquipos.ajax.reload(null,false);
            } else {
                alert('Error: ' + (data.message || 'No se pudo importar'));
            }
        })
        .catch(err => console.error(err))
        .finally(()=> enviando=false);
    });

});
