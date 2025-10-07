$(document).ready(function(){

    var tabla = $('#tablaEquipos').DataTable({
        processing: true,
        serverSide: true,
        ajax: 'equipos_data.php',
        columns: [
            {data:'id_equipo'},
            {data:'Nombre'},
            {data:'Descripcion'},
            {data:'Cliente'},
            {data:'Categoria'},
            {data:'Estatus'},
            {data:'Fecha_validad'},
            {data:'acciones', orderable:false, searchable:false}
        ],
        language:{ url:'//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
    });

    // AGREGAR
    /* $('#formAgregarEquipo').submit(function(e){
        e.preventDefault();
        $.post('equipos_crud.php', $(this).serialize(), function(resp){
            if(resp.success){
                $('#modalAgregar').modal('hide');
                $('#formAgregarEquipo')[0].reset();
                tabla.ajax.reload();
            } else alert('Error al agregar');
        }, 'json');
    }); */

    // EDITAR - abrir modal
    $('#tablaEquipos').on('click', '.editar', function(){
        let id = $(this).data('id');
        $.getJSON('equipos_data.php', function(resp){
            let equipo = resp.data.find(e => e.id_equipo==id);
            if(equipo){
                $('#editId').val(equipo.id_equipo);
                $('#editNombre').val(equipo.Nombre);
                $('#editDescripcion').val(equipo.Descripcion);
                $('#editCliente').val(equipo.Cliente);
                $('#editCategoria').val(equipo.Categoria);
                $('#editEstatus').val(equipo.Estatus);
                $('#editFecha').val(equipo.Fecha_validad);
                $('#modalEditar').modal('show');
            }
        });
    });

    // EDITAR - enviar cambios
    $('#formEditarEquipo').submit(function(e){
        e.preventDefault();
        $.post('equipos_crud.php', $(this).serialize(), function(resp){
            if(resp.success){
                $('#modalEditar').modal('hide');
                tabla.ajax.reload();
            } else alert('Error al editar');
        }, 'json');
    });

    // ELIMINAR - abrir modal
    $('#tablaEquipos').on('click', '.eliminar', function(){
        let id = $(this).data('id');
        $('#deleteId').val(id);
        $('#modalEliminar').modal('show');
    });

    // ELIMINAR - enviar formulario
    $('#formEliminarEquipo').submit(function(e){
        e.preventDefault();
        $.post('equipos_crud.php', $(this).serialize(), function(resp){
            if(resp.success){
                $('#modalEliminar').modal('hide');
                tabla.ajax.reload();
            } else alert('Error al eliminar');
        }, 'json');
    });

});
