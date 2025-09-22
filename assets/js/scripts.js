document.addEventListener("DOMContentLoaded", function(){
    let tabla = new DataTable('#tablaEquipos', {
        processing: true,
        serverSide: true,
        ajax: "inventario_equipos.php?ajax=1",
        pageLength: 10,
        columns: [
            { data: "id_equipo" },
            { data: "Nombre" },
            { data: "Descripcion" },
            { data: "Cliente" },
            { data: "Categoria" },
            { data: "Estatus" },
            { data: "Fecha_validad" },
            { data: "acciones" }
        ],
        language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
    });

    // Editar
    $(document).on("click", ".btnEditar", function(){
        $("#editId").val($(this).data("id"));
        $("#editNombre").val($(this).data("nombre"));
        $("#editDescripcion").val($(this).data("descripcion"));
        $("#editCliente").val($(this).data("cliente"));
        $("#editCategoria").val($(this).data("categoria"));
        $("#editEstatus").val($(this).data("estatus"));
        $("#editFecha").val($(this).data("fecha"));
        $("#modalEditar").modal("show");
    });

    // Eliminar
    $(document).on("click", ".btnEliminar", function(){
        $("#deleteId").val($(this).data("id"));
        $("#modalEliminar").modal("show");
    });
});
