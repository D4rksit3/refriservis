<?php
//admin/mantenimientos.php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: /index.php'); exit; }
require_once __DIR__.'/../config/db.php';

// ============================
// Mapa de descargas por categoría
// ============================
$mapaDescargas = [
    'VRV - FORMATO DE CALIDAD'                            => '/operador/reportes/guardar_reporte_vrv.php',
    'VENTILACION MECANICA (VEX-VIN) - FORMATO DE CALIDAD' => '/operador/reportes/guardar_reporte_vex_vin.php',
    'UMA - FORMATO DE CALIDAD'                            => '/operador/reportes/guardar_reporte_uma.php',
    'SPLIT DECORATIVO - FORMATO DE CALIDAD'               => '/operador/reportes/guardar_reporte_split.php',
    'ROOFTOP - FORMATO DE CALIDAD'                        => '/operador/reportes/guardar_reporte_rooftop.php',
    'CORTINAS DE AIRE - FORMATO DE CALIDAD'               => '/operador/reportes/guardar_reporte_cortinas.php',
    'CHILLERS - FORMATO DE CALIDAD'                       => '/operador/reportes/guardar_reporte_chillers.php',
    'BOMBAS DE AGUA - FORMATO DE CALIDAD'                 => '/operador/reportes/guardar_reporte_bombas.php',
    'REPORTE DE SERVICIO TECNICO'                         => '/operador/reportes/guardar_reporte_servicio.php'
];

// ============================
// Manejo de AJAX para tabla
// ============================
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    $pagina = max(1, (int)($_GET['pagina'] ?? 1));
    $por_pagina = 10;
    $inicio = ($pagina - 1) * $por_pagina;
    $buscar = $_GET['buscar'] ?? '';

    // SIN filtro por rol → todos los mantenimientos
    $where = '1=1';
    $params = [];

    if ($buscar) {
        $where .= " AND titulo LIKE ?";
        $params[] = "%$buscar%";
    }

    // Contar total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM mantenimientos WHERE $where");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    $total_paginas = ceil($total / $por_pagina);

    // Traer mantenimientos
    $stmt = $pdo->prepare("SELECT * FROM mantenimientos WHERE $where ORDER BY creado_en DESC LIMIT $inicio,$por_pagina");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mapear equipos
    $equiposAll = $pdo->query("SELECT id_equipo, Nombre FROM equipos")->fetchAll(PDO::FETCH_KEY_PAIR);

    $result = [];
    foreach ($rows as $r) {
        $equipoNombres = [];
        for ($i=1; $i<=7; $i++) {
            $eqId = $r['equipo'.$i];
            if ($eqId && isset($equiposAll[$eqId])) $equipoNombres[] = $equiposAll[$eqId];
        }

        $estado_color = $r['estado']==='finalizado' ? 'success' : ($r['estado']==='en proceso' ? 'warning text-dark' : 'warning text-dark');

        $cliente = $r['cliente_id'] ? $pdo->query("SELECT cliente FROM clientes WHERE id=".$r['cliente_id'])->fetchColumn() : null;
        $digitador = $r['digitador_id'] ? $pdo->query("SELECT nombre FROM usuarios WHERE id=".$r['digitador_id'])->fetchColumn() : null;
        $operador = $r['operador_id'] ? $pdo->query("SELECT nombre FROM usuarios WHERE id=".$r['operador_id'])->fetchColumn() : null;

        // URL de descarga según categoría
        $categoria = $r['categoria'] ?? null;
        $urlDescarga = $categoria && isset($mapaDescargas[$categoria]) ? $mapaDescargas[$categoria] : null;

        $result[] = [
            'id'=>$r['id'],
            'titulo'=>htmlspecialchars($r['titulo']),
            'descripcion'=>htmlspecialchars($r['descripcion']),
            'fecha'=>$r['fecha'],
            'cliente'=>htmlspecialchars($cliente),
            'cliente_id'=>$r['cliente_id'],
            'operador_id'=>$r['operador_id'],
            'categoria'=>htmlspecialchars($r['categoria']),
            'equipos'=>htmlspecialchars(implode(', ', $equipoNombres)),
            'equipo1'=>$r['equipo1'],
            'equipo2'=>$r['equipo2'],
            'equipo3'=>$r['equipo3'],
            'equipo4'=>$r['equipo4'],
            'equipo5'=>$r['equipo5'],
            'equipo6'=>$r['equipo6'],
            'equipo7'=>$r['equipo7'],
            'estado'=>htmlspecialchars($r['estado']),
            'estado_color'=>$estado_color,
            'digitador'=>htmlspecialchars($digitador),
            'operador'=>htmlspecialchars($operador),
            'url_reporte'=>$urlDescarga
        ];
    }

    echo json_encode([
        'rows'=>$result,
        'total_paginas'=>$total_paginas,
        'total_registros'=>$total
    ]);
    exit;
}

// ============================
// HTML principal
// ============================
require_once __DIR__.'/../includes/header.php';

// Datos para los selects del modal
$clientes = $pdo->query('SELECT id, cliente, direccion, telefono FROM clientes ORDER BY cliente')->fetchAll();
$operadores = $pdo->query('SELECT id, nombre FROM usuarios')->fetchAll();
$categorias = $pdo->query('SELECT nombre FROM categoria ORDER BY nombre')->fetchAll();
?>
<style>
  .btn-editar i,
  .btn-eliminar i,
  .btn-reporte i {
    pointer-events: none;
  }
</style>

<!-- jQuery y dependencias (cargar antes del contenido) -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="card p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5>Mantenimientos</h5>
    <?php if($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'digitador'): ?>
      <a class="btn btn-primary btn-sm" href="/mantenimientos/crear.php">+ Nuevo</a>
    <?php endif; ?>
  </div>

  <input type="text" id="buscar" class="form-control mb-3" placeholder="Buscar por título...">

  <div id="info-registros" class="mb-2"></div>

  <div class="table-responsive">
    <table class="table table-sm" id="tabla-mantenimientos">
      <thead>
        <tr>
          <th>ID</th>
          <th>Título</th>
          <th>Fecha</th>
          <th>Cliente</th>
          <th>Equipos</th>
          <th>Estado</th>
          <th>Digitador</th>
          <th>Operador</th>
          <th></th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <nav>
    <ul class="pagination justify-content-center" id="paginacion"></ul>
  </nav>
</div>

<!-- Modal Editar Completo -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar Mantenimiento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formEditar" class="row g-3">
          <input type="hidden" name="id" id="edit_id">
          
          <div class="col-12">
            <label class="form-label">Título*</label>
            <input type="text" class="form-control" name="titulo" id="edit_titulo" required>
          </div>

          <div class="col-12">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion" id="edit_descripcion" rows="3"></textarea>
          </div>

          <div class="col-md-4">
            <label class="form-label">Fecha*</label>
            <input type="date" class="form-control" name="fecha" id="edit_fecha" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Cliente</label>
            <select class="form-select selectpicker" name="cliente_id" id="edit_cliente" data-live-search="true">
              <option value="">-- Ninguno --</option>
              <?php foreach($clientes as $c): ?>
                <option value="<?=$c['id']?>">
                  <?=htmlspecialchars($c['cliente'].' - '.$c['direccion'])?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Operador</label>
            <select class="form-select" name="operador_id" id="edit_operador">
              <option value="">-- Ninguno --</option>
              <?php foreach($operadores as $o): ?>
                <option value="<?=$o['id']?>"><?=htmlspecialchars($o['nombre'])?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Categoría*</label>
            <select class="form-select" name="categoria" id="edit_categoria" required>
              <?php foreach($categorias as $cat): ?>
                <option value="<?=htmlspecialchars($cat['nombre'])?>">
                  <?=htmlspecialchars($cat['nombre'])?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Equipos (máx 7)</label>
            <select name="equipos[]" id="edit_equipos" class="form-select selectpicker" multiple 
                    data-live-search="true" data-max-options="7">
              <!-- Se cargará dinámicamente según el cliente -->
            </select>
          </div>

        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="guardarEditar">
          <i class="bi bi-save"></i> Guardar Cambios
        </button>
      </div>
    </div>
  </div>
</div>

<script>
let pagina = 1;
const porPagina = 10;
let mantenimientoActual = null;

function cargarMantenimientos() {
  const buscar = document.getElementById('buscar').value;

  fetch(`?ajax=1&pagina=${pagina}&buscar=${encodeURIComponent(buscar)}`)
    .then(res => res.json())
    .then(data => {
      const tbody = document.querySelector('#tabla-mantenimientos tbody');
      tbody.innerHTML = '';

      data.rows.forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${r.id}</td>
          <td>${r.titulo}</td>
          <td>${r.fecha}</td>
          <td>${r.cliente || '-'}</td>
          <td>${r.equipos || '-'}</td>
          <td><span class="badge bg-${r.estado_color}">${r.estado}</span></td>
          <td>${r.digitador || '-'}</td>
          <td>${r.operador || '-'}</td>
          <td class="text-end">
            ${r.estado !== 'finalizado' 
                ? `
                <button class="btn btn-sm btn-outline-primary btn-editar" title="Editar" 
                        data-mantenimiento='${JSON.stringify(r).replace(/'/g, "&apos;")}'>
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger btn-eliminar" title="Eliminar" data-id="${r.id}">
                  <i class="bi bi-trash"></i>
                </button>
                `
                : ''
            }
            ${r.estado === 'finalizado' && r.url_reporte
                ? `<button class="btn btn-sm btn-outline-success btn-reporte" data-id="${r.id}" data-url="${r.url_reporte}"><i class="bi bi-download"></i></button>` 
                : ''
            }
          </td>
        `;
        tbody.appendChild(tr);
      });

      // Info registros
      const inicioRegistro = ((pagina -1) * porPagina) + 1;
      const finRegistro = inicioRegistro + data.rows.length - 1;
      document.getElementById('info-registros').innerText = `Mostrando ${inicioRegistro} a ${finRegistro} de ${data.total_registros} registros`;

      // Paginación bloques de 10
      const pagUl = document.getElementById('paginacion');
      pagUl.innerHTML = '';
      let bloque = Math.floor((pagina - 1) / 10);
      let inicio = bloque * 10 + 1;
      let fin = Math.min(inicio + 9, data.total_paginas);

      if (inicio > 1) {
          const liPrev = document.createElement('li');
          liPrev.className = 'page-item';
          liPrev.innerHTML = `<a class="page-link" href="#">«</a>`;
          liPrev.addEventListener('click', e => { e.preventDefault(); pagina = inicio - 1; cargarMantenimientos(); });
          pagUl.appendChild(liPrev);
      }

      for (let p = inicio; p <= fin; p++) {
          const li = document.createElement('li');
          li.className = `page-item ${p === pagina ? 'active' : ''}`;
          li.innerHTML = `<a class="page-link" href="#">${p}</a>`;
          li.addEventListener('click', e => { e.preventDefault(); pagina = p; cargarMantenimientos(); });
          pagUl.appendChild(li);
      }

      if (fin < data.total_paginas) {
          const liNext = document.createElement('li');
          liNext.className = 'page-item';
          liNext.innerHTML = `<a class="page-link" href="#">»</a>`;
          liNext.addEventListener('click', e => { e.preventDefault(); pagina = fin + 1; cargarMantenimientos(); });
          pagUl.appendChild(liNext);
      }
    });
}

// Eventos de la tabla
document.querySelector('#tabla-mantenimientos tbody').addEventListener('click', e => {
  // Botón Editar
  const btnEditar = e.target.closest('.btn-editar');
  if (btnEditar) {
    mantenimientoActual = JSON.parse(btnEditar.dataset.mantenimiento);
    abrirModalEditar(mantenimientoActual);
    return;
  }

  // Botón Eliminar
  const btnEliminar = e.target.closest('.btn-eliminar');
  if (btnEliminar) {
    const id = btnEliminar.dataset.id;
    if (confirm('¿Seguro que deseas eliminar este mantenimiento? Esta acción no se puede deshacer.')) {
      fetch('/mantenimientos/eliminar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${encodeURIComponent(id)}`
      })
      .then(res => res.json())
      .then(res => {
        if(res.success){
          alert('Mantenimiento eliminado correctamente');
          cargarMantenimientos();
        } else {
          alert(res.error || 'Error al eliminar');
        }
      });
    }
    return;
  }

  // Botón Descargar reporte
  const btnReporte = e.target.closest('.btn-reporte');
  if (btnReporte) {
    const id = btnReporte.dataset.id;
    const url = btnReporte.dataset.url;
    if(url) window.open(`${url}?id=${id}`, '_blank');
  }
});

// Función para abrir modal de edición
function abrirModalEditar(r) {
  $('.selectpicker').selectpicker();
  
  // Cargar datos básicos
  $('#edit_id').val(r.id);
  $('#edit_titulo').val(r.titulo);
  $('#edit_descripcion').val(r.descripcion);
  $('#edit_fecha').val(r.fecha);
  $('#edit_categoria').val(r.categoria);
  
  // Seleccionar cliente
  const clienteId = r.cliente_id;
  $('#edit_cliente').val(clienteId).selectpicker('refresh');
  
  // Seleccionar operador
  $('#edit_operador').val(r.operador_id);
  
  // Cargar equipos del cliente y pre-seleccionar
  const equiposSeleccionados = [];
  for(let i = 1; i <= 7; i++) {
    const eqId = r['equipo' + i];
    if(eqId) equiposSeleccionados.push(String(eqId));
  }
  
  if(clienteId) {
    $.getJSON('/mantenimientos/equipos_por_cliente.php', { id: clienteId })
      .done(function(data){
        const selectEquipos = $('#edit_equipos');
        selectEquipos.html('');
        
        if(data && data.length > 0) {
          $.each(data, function(_, e){
            const isSelected = equiposSeleccionados.includes(String(e.id_equipo));
            selectEquipos.append(
              $('<option>', {
                value: e.id_equipo,
                text: `${e.Identificador} | ${e.nombre_equipo} | ${e.Categoria} | ${e.Estatus}`,
                selected: isSelected
              })
            );
          });
        } else {
          selectEquipos.append('<option disabled>(Sin equipos)</option>');
        }
        
        selectEquipos.selectpicker('destroy').selectpicker();
      });
  } else {
    $('#edit_equipos').html('<option disabled>Selecciona un cliente</option>')
      .selectpicker('destroy').selectpicker();
  }
  
  new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

// Cambiar cliente en modal de edición
$(document).ready(function(){
  $('#edit_cliente').on('changed.bs.select', function(){
    const clienteId = $(this).val();
    const selectEquipos = $('#edit_equipos');
    
    selectEquipos.html('').selectpicker('destroy').selectpicker();
    
    if(!clienteId) return;
    
    $.getJSON('/mantenimientos/equipos_por_cliente.php', { id: clienteId })
      .done(function(data){
        selectEquipos.html('');
        
        if(data && data.length > 0) {
          $.each(data, function(_, e){
            selectEquipos.append(
              $('<option>', {
                value: e.id_equipo,
                text: `${e.Identificador} | ${e.nombre_equipo} | ${e.Categoria} | ${e.Estatus}`
              })
            );
          });
        } else {
          selectEquipos.append('<option disabled>(Sin equipos)</option>');
        }
        
        selectEquipos.selectpicker('refresh');
      });
  });
});

// Guardar cambios
document.getElementById('guardarEditar').addEventListener('click', () => {
  const formData = new FormData(document.getElementById('formEditar'));
  
  fetch('/mantenimientos/editar_ajax.php', { 
    method: 'POST', 
    body: formData 
  })
  .then(res => res.json())
  .then(res => {
    if(res.success) {
      alert('Mantenimiento actualizado correctamente');
      cargarMantenimientos();
      bootstrap.Modal.getInstance(document.getElementById('modalEditar')).hide();
    } else {
      alert('Error al guardar: ' + (res.message || 'Desconocido'));
    }
  })
  .catch(err => {
    alert('Error de conexión al guardar');
    console.error(err);
  });
});

// Búsqueda en tiempo real
document.getElementById('buscar').addEventListener('input', () => { pagina = 1; cargarMantenimientos(); });

// Cargar tabla inicialmente
cargarMantenimientos();
</script>