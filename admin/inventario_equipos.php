<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';
?>

<div class="container my-4">

  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
  <h2 class="h4 mb-2">📋 Inventario de Equipos</h2>

  <div class="d-flex gap-2 mb-2">
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarEquipo">
      ➕ Nuevo
    </button>

    <button class="btn btn-primary btn-sm" id="btnExportar">
      📥 Exportar a Excel
    </button>

    <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalImportar">
      📤 Importar desde CSV
    </button>
  </div>
</div>

  <div class="table-responsive shadow-sm rounded">
    <table id="tablaEquipos" class="table table-striped table-bordered align-middle">
      <thead class="table-primary">
        <tr>
          <th>ID</th>
          <th>Identificador</th>
          <th>Nombre</th>
          <th>Marca</th>
          <th>Modelo</th>
          <th>Ubicación</th>
          <th>Voltaje</th>
          <th>Cliente</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

</div>

<!-- MODAL AGREGAR EQUIPO -->
<div class="modal fade" id="modalAgregarEquipo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formAgregarEquipo" method="post">
        
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">➕ Nuevo Equipo</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="accion" value="agregar">
          <div class="mb-2"><label>Identificador</label><input type="text" class="form-control" name="Identificador" required></div>
          <div class="mb-2"><label>Nombre</label><input type="text" class="form-control" name="Nombre" required></div>
          <div class="mb-2"><label>marca</label><input type="text" class="form-control" name="marca" required></div>
          <div class="mb-2"><label>modelo</label><input type="text" class="form-control" name="modelo" required></div>
          <div class="mb-2"><label>ubicacion</label><input type="text" class="form-control" name="ubicacion" required></div>
          <div class="mb-2"><label>voltaje</label><input type="text" class="form-control" name="voltaje" required></div>
          <div class="mb-2"><label>Cliente</label><input type="text" class="form-control" name="Cliente"></div>
          <div class="mb-2"><label>Categoria</label><input type="text" class="form-control" name="Categoria"></div>
          <div class="mb-2"><label>Estatus</label>
            <select class="form-select" name="Estatus">
              <option>Activo</option>
              <option>Inactivo</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Agregar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL EDITAR EQUIPO -->
<div class="modal fade" id="modalEditarEquipo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formEditarEquipo" method="post">
        <div class="modal-header bg-warning">
          <h5 class="modal-title">✏️ Editar Equipo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="accion" value="editar">
          <input type="hidden" name="id_equipo" id="editIdEquipo">
          <div class="mb-2"><label>Identificador</label><input type="text" class="form-control" id="editIdentificador" name="Identificador" required></div>
          <div class="mb-2"><label>Nombre</label><input type="text" class="form-control" id="editNombreEquipo" name="Nombre" required></div>
          <div class="mb-2"><label>marca</label><input type="text" class="form-control" id="editmarca" name="marca" required></div>
          <div class="mb-2"><label>modelo</label><input type="text" class="form-control" id="editmodelo" name="modelo" required></div>
          <div class="mb-2"><label>ubicacion</label><input type="text" class="form-control" id="editubicacion" name="ubicacion" required></div>
          <div class="mb-2"><label>voltaje</label><input type="text" class="form-control" id="editvoltaje" name="voltaje" required></div>
          <div class="mb-2"><label>Descripcion</label><textarea class="form-control" id="editDescripcionEquipo" name="Descripcion"></textarea></div>
          <div class="mb-2"><label>Cliente</label><input type="text" class="form-control" id="editClienteEquipo" name="Cliente"></div>
          <div class="mb-2"><label>Categoria</label><input type="text" class="form-control" id="editCategoriaEquipo" name="Categoria"></div>
          <div class="mb-2"><label>Estatus</label>
            <select class="form-select" id="editEstatusEquipo" name="Estatus">
              <option>Activo</option>
              <option>Inactivo</option>
            </select>
          </div>
          <div class="mb-2"><label>Fecha Validación</label><input type="date" class="form-control" id="editFechaEquipo" name="Fecha_validad"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Guardar Cambios</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL ELIMINAR EQUIPO -->
<div class="modal fade" id="modalEliminarEquipo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formEliminarEquipo" method="post">
        <input type="hidden" name="accion" value="eliminar">
        <input type="hidden" name="id_equipo" id="deleteIdEquipo">
        <div class="modal-body p-4">
          <p>¿Estás seguro de que deseas eliminar este equipo?</p>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-danger">Eliminar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL IMPORTAR CSV -->
<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title">📤 Importar Equipos desde CSV</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formImportarCSV" enctype="multipart/form-data">
          
          <!-- Instrucciones -->
          <div class="alert alert-info">
            <h6 class="alert-heading">📝 Instrucciones:</h6>
            <ol class="mb-0 small">
              <li>El archivo debe ser formato CSV (separado por comas)</li>
              <li>La primera fila debe contener los encabezados</li>
              <li>Columnas requeridas: <strong>Identificador, Nombre, marca, modelo, ubicacion, voltaje</strong></li>
              <li>Columnas opcionales: Descripcion, Cliente, Categoria, Estatus, Fecha_validad</li>
            </ol>
          </div>

          <!-- Botón descargar plantilla -->
          <div class="mb-3">
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnDescargarPlantilla">
              📥 Descargar Plantilla CSV
            </button>
          </div>

          <!-- Selector de archivo -->
          <div class="mb-3">
            <label class="form-label">Seleccionar archivo CSV:</label>
            <input type="file" class="form-control" name="archivo_csv" id="archivo_csv" accept=".csv" required>
          </div>

          <!-- Vista previa -->
          <div id="preview-container" style="display: none;">
            <h6>Vista previa (primeras 5 filas):</h6>
            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
              <table class="table table-sm table-bordered" id="tabla-preview">
                <thead class="table-light"></thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          <!-- Resumen de importación -->
          <div id="resumen-importacion" style="display: none;"></div>

        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btnProcesarCSV" disabled>
          <span class="spinner-border spinner-border-sm d-none" id="spinner-import"></span>
          Importar Equipos
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>

<script src="scripts.js"></script>

<script>
// ========================================
// IMPORTACIÓN CSV
// ========================================

let datosCSV = [];

// Descargar plantilla CSV
$('#btnDescargarPlantilla').on('click', function(){
  const csvContent = `Identificador,Nombre,marca,modelo,ubicacion,voltaje,Descripcion,Cliente,Categoria,Estatus,Fecha_validad
EQUIPO-001,Aire Acondicionado,LG,MODELO-123,Oficina Principal,220V,Equipo de climatización,Cliente Ejemplo,Split,Activo,2025-12-31
EQUIPO-002,Chiller,Carrier,CH-500,Planta Baja,380V,Sistema de enfriamiento industrial,Cliente Ejemplo,Chillers,Activo,2025-12-31`;

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'plantilla_equipos.csv';
  link.click();
});

// Vista previa al seleccionar archivo
$('#archivo_csv').on('change', function(e){
  const file = e.target.files[0];
  if (!file) return;

  Papa.parse(file, {
    header: true,
    skipEmptyLines: true,
    complete: function(results) {
      datosCSV = results.data;
      
      if (datosCSV.length === 0) {
        alert('El archivo CSV está vacío');
        return;
      }

      // Mostrar vista previa
      mostrarVistaPrevia(datosCSV.slice(0, 5), Object.keys(datosCSV[0]));
      
      // Habilitar botón de importar
      $('#btnProcesarCSV').prop('disabled', false);
      $('#preview-container').show();
    },
    error: function(error) {
      alert('Error al leer el archivo: ' + error.message);
    }
  });
});

// Mostrar vista previa de datos
function mostrarVistaPrevia(datos, columnas) {
  const thead = $('#tabla-preview thead');
  const tbody = $('#tabla-preview tbody');
  
  thead.empty();
  tbody.empty();

  // Encabezados
  let headerRow = '<tr>';
  columnas.forEach(col => {
    headerRow += `<th>${col}</th>`;
  });
  headerRow += '</tr>';
  thead.append(headerRow);

  // Filas de datos
  datos.forEach(fila => {
    let row = '<tr>';
    columnas.forEach(col => {
      row += `<td>${fila[col] || ''}</td>`;
    });
    row += '</tr>';
    tbody.append(row);
  });
}

// Procesar e importar CSV
$('#btnProcesarCSV').on('click', function(){
  if (datosCSV.length === 0) {
    alert('No hay datos para importar');
    return;
  }

  // Validar campos requeridos
  const camposRequeridos = ['Identificador', 'Nombre', 'marca', 'modelo', 'ubicacion', 'voltaje'];
  const primeraFila = datosCSV[0];
  const columnas = Object.keys(primeraFila);
  
  const faltantes = camposRequeridos.filter(campo => !columnas.includes(campo));
  if (faltantes.length > 0) {
    alert('Faltan columnas requeridas: ' + faltantes.join(', '));
    return;
  }

  // Mostrar spinner
  $('#spinner-import').removeClass('d-none');
  $('#btnProcesarCSV').prop('disabled', true);

  // Enviar datos al servidor
  $.ajax({
    url: '/admin/importar_equipos_csv.php',
    method: 'POST',
    data: JSON.stringify({ equipos: datosCSV }),
    contentType: 'application/json',
    dataType: 'json',
    success: function(response) {
      $('#spinner-import').addClass('d-none');
      
      if (response.success) {
        // Mostrar resumen
        let resumenHTML = `
          <div class="alert alert-success">
            <h6>✅ Importación completada</h6>
            <ul class="mb-0">
              <li>Total procesados: ${response.total}</li>
              <li>Insertados correctamente: ${response.insertados}</li>
              <li>Errores: ${response.errores}</li>
            </ul>
          </div>
        `;
        
        if (response.detalles_errores && response.detalles_errores.length > 0) {
          const erroresHTML = response.detalles_errores.map(e => `<li>Fila ${e.fila}: ${e.error}</li>`).join('');
          resumenHTML += `<div class="alert alert-warning"><h6>⚠️ Detalles de errores:</h6><ul>${erroresHTML}</ul></div>`;
        }
        
        $('#resumen-importacion').html(resumenHTML).show();
        
        // Recargar tabla si existe
        if (typeof tablaEquipos !== 'undefined' && tablaEquipos.ajax) {
          tablaEquipos.ajax.reload();
        } else {
          // Recargar página si no hay tabla ajax
          setTimeout(function(){
            location.reload();
          }, 2000);
        }
        
        // Limpiar formulario
        $('#formImportarCSV')[0].reset();
        $('#preview-container').hide();
        $('#btnProcesarCSV').prop('disabled', true);
        datosCSV = [];
        
      } else {
        alert('Error al importar: ' + (response.message || 'Desconocido'));
        $('#btnProcesarCSV').prop('disabled', false);
      }
    },
    error: function(xhr, status, error) {
      $('#spinner-import').addClass('d-none');
      $('#btnProcesarCSV').prop('disabled', false);
      
      console.error('Error completo:', xhr.responseText);
      
      try {
        const response = JSON.parse(xhr.responseText);
        alert('Error: ' + (response.message || error));
      } catch(e) {
        alert('Error de conexión: ' + error + '\n\nRespuesta del servidor: ' + xhr.responseText);
      }
    }
  });
});

// Limpiar al cerrar modal
$('#modalImportar').on('hidden.bs.modal', function(){
  $('#formImportarCSV')[0].reset();
  $('#preview-container').hide();
  $('#resumen-importacion').hide();
  $('#btnProcesarCSV').prop('disabled', true);
  datosCSV = [];
});
</script>

<?php 
require_once __DIR__.'/../includes/footer.php';
?>