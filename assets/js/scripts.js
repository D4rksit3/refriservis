document.addEventListener('DOMContentLoaded', function () {
    const tabla = document.getElementById('tablaEquipos');
    if (!tabla) {
        console.warn("⚠️ No existe la tabla #tablaEquipos en el DOM");
        return;
    }

    $('#tablaEquipos').DataTable({
        processing: true,
        serverSide: false,
        ajax: "inventario_equipos.php?ajax=1",
        columns: [
            { data: "id_equipo" },
            { data: "nombre" },
            { data: "descripcion" },
            { data: "cliente" },
            { data: "categoria" },
            { data: "estatus" },
            { data: "fecha_validad" },
            {
                data: null,
                render: function (data) {
                    return `
                        <form method="post" style="display:inline-block">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_equipo" value="${data.id_equipo}">
                            <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                        </form>`;
                }
            }
        ],
        language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
    });
});
