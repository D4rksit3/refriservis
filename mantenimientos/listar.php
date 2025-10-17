<?php
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

    // Filtro por rol
    $where = '1=1';
    $params = [];
    if ($_SESSION['rol'] === 'digitador') {
      $where = 'digitador_id = ?';
      $params[] = $_SESSION['usuario_id'];
    } elseif ($_SESSION['rol'] === 'operador') {
      $where = 'operador_id = ?';
      $params[] = $_SESSION['usuario_id'];
    }

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

        $estado_color = $r['estado']==='finalizado' ? 'success' : 'warning text-dark';
        $cliente = $r['cliente_id'] ? $pdo->query("SELECT cliente FROM clientes WHERE id=".$r['cliente_id'])->fetchColumn() : null;
        $digitador = $r['digitador_id'] ? $pdo->query("SELECT nombre FROM refriservis.usuarios WHERE id=".$r['digitador_id'])->fetchColumn() : null;
        $operador = $r['operador_id'] ? $pdo->query("SELECT nombre FROM usuarios WHERE id=".$r['operador_id'])->fetchColumn() : null;
        $categoria = $r['categoria'] ?? null;
        $urlDescarga = $categoria && isset($mapaDescargas[$categoria]) ? $mapaDescargas[$categoria] : null;

        $result[] = [
            'id'=>$r['id'],
            'titulo'=>htmlspecialchars($r['titulo']),
            'fecha'=>$r['fecha'],
            'cliente'=>htmlspecialchars($cliente),
            'equipos'=>htmlspecialchars(implode(', ', $equipoNombres)),
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
?>

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

<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar Mantenimiento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formEditar" class="row g-2">
          <input type="hidden" name="id" id="edit_id">

          <div class="col-12">
            <label class="form-label">Título</label>
            <input class="form-control" name="titulo" id="edit_titulo" required>
          </div>

          <div class="col-12">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion" id="edit_descripcion"></textarea>
          </div>

          <div class="col-4">
            <label class="form-label">Fecha</label>
            <input type="date" class="form-control" name="fecha" id="edit_fecha">
          </div>

          <div class="col-4">
            <label class="form-label">Cliente</label>
            <div class="input-group">
              <select name="cliente_id" id="edit_cliente"
                      class="form-select selectpicker"
                      data-live-search="true"
                      title="Selecciona un cliente...">
                <option value="">-- Ninguno --</option>
                <?php
                  $clientes = $pdo->query("SELECT id, cliente, direccion, telefono FROM clientes ORDER BY cliente")->fetchAll();
                  foreach($clientes as $c) {
                    echo "<option value='{$c['id']}'>"
                        .htmlspecialchars($c['cliente'].' - '.$c['direccion'].' - '.$c['telefono'])
                        ."</option>";
                  }
                ?>
              </select>
              <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoCliente">+ Nuevo</button>
            </div>
          </div>

          <div class="col-4">
            <label class="form-label">Operador</label>
            <select name="operador_id" id="edit_operador" class="form-select selectpicker" data-live-search="true">
              <option value="">-- Ninguno --</option>
              <?php
                $operadores = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol='operador'")->fetchAll();
                foreach($operadores as $o) {
                  echo "<option value='{$o['id']}'>".htmlspecialchars($o['nombre'])."</option>";
                }
              ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Categoría</label>
            <select name="categoria" id="edit_categoria" class="form-select selectpicker" data-live-search="true">
              <?php
                $categorias = $pdo->query("SELECT nombre FROM categoria ORDER BY nombre")->fetchAll();
                foreach($categorias as $cat) {
                  echo "<option value='".htmlspecialchars($cat['nombre'])."'>".htmlspecialchars($cat['nombre'])."</option>";
                }
              ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Equipos (máx 7)</label>
            <select name="equipos[]" id="edit_equipos" class="form-select selectpicker" multiple data-live-search="true" data-max-options="7" title="Selecciona equipos...">
              <?php
                $equipos = $pdo->query("SELECT id_equipo, Identificador, Nombre, Categoria, Estatus FROM equipos ORDER BY Nombre")->fetchAll();
                foreach($equipos as $e) {
                  echo "<option value='{$e['id_equipo']}'>"
                      .htmlspecialchars($e['Identificador'].' | '.$e['Nombre'].' | '.$e['Categoria'].' | '.$e['Estatus'])
                      ."</option>";
                }
              ?>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" id="guardarEditar">Guardar Cambios</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Nuevo Cliente -->
<div class="modal fade" id="modalNuevoCliente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formNuevoCliente" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Agregar Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-2">
        <div class="col-12">
          <label class="form-label">Cliente*</label>
          <input type="text" name="cliente" class="form-control" required>
        </div>
        <div class="col-12">
          <label class="form-label">Dirección*</label>
          <input type="text" name="direccion" class="form-control" required>
        </div>
        <div class="col-6">
          <label class="form-label">Teléfono*</label>
          <input type="text" name="telefono" class="form-control" required>
        </div>
        <div class="col-6">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control">
        </div>
        <div class="col-12">
          <label class="form-label">Responsable</label>
          <input type="text" name="responsable" class="form-control">
        </div>
        <input type="hidden" name="estatus" value="1">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- jQuery y Bootstrap -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>

<script>
let pagina = 1;
const porPagina = 10;

// ============================
// Cargar Mantenimientos (AJAX)
// ============================
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
                ? `<button class="btn btn-sm btn-outline-primary btn-editar" data-id="${r.id}">Editar</button>` 
                : ''
            }
            ${r.estado === 'finalizado' && r.url_reporte
                ? `<button class="btn btn-sm btn-outline-success btn-reporte" data-id="${r.id}" data-url="${r.url_reporte}">Descargar Reporte</button>` 
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

      // Paginación
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

// ============================
// Eventos
// ============================
$(document).ready(function(){
  $('.selectpicker').selectpicker();
  cargarMantenimientos();

  // Búsqueda
  $('#buscar').on('input', () => { pagina = 1; cargarMantenimientos(); });

  // Editar mantenimiento
  $('#tabla-mantenimientos').on('click', '.btn-editar', function(){
    const id = $(this).data('id');
    $.getJSON('/mantenimientos/obtener.php', { id }, function(data){
      if(!data.success){ alert('No se encontró el mantenimiento'); return; }

      $('#edit_id').val(data.id);
      $('#edit_titulo').val(data.titulo);
      $('#edit_descripcion').val(data.descripcion);
      $('#edit_fecha').val(data.fecha);
      $('#edit_cliente').val(data.cliente_id).selectpicker('refresh');
      $('#edit_operador').val(data.operador_id).selectpicker('refresh');
      $('#edit_categoria').val(data.categoria).selectpicker('refresh');
      $('#edit_equipos').val(data.equipos).selectpicker('refresh');

      new bootstrap.Modal('#modalEditar').show();
    });
  });

  // Guardar edición
  $('#guardarEditar').on('click', function(){
    const formData = new FormData($('#formEditar')[0]);
    $.ajax({
      url: '/mantenimientos/editar_ajax.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function(res){
        if(res.success){
          alert('Cambios guardados correctamente');
          bootstrap.Modal.getInstance(document.getElementById('modalEditar')).hide();
          cargarMantenimientos();
        } else {
          alert(res.error || 'Error al guardar los cambios');
        }
      }
    });
  });

  // Nuevo cliente
  $('#formNuevoCliente').on('submit', function(e){
    e.preventDefault();
    $.post('/mantenimientos/guardar_cliente.php', $(this).serialize(), function(data){
      if(data.success){
        $('#edit_cliente')
          .append($('<option>', { value: data.id, text: data.text }))
          .val(data.id)
          .selectpicker('refresh');
        $('#modalNuevoCliente').modal('hide');
      } else {
        alert(data.error || 'Error al guardar cliente');
      }
    }, 'json');
  });

  // Descargar reporte
  $('#tabla-mantenimientos').on('click', '.btn-reporte', function(){
    const id = $(this).data('id');
    const url = $(this).data('url');
    if(url) window.open(`${url}?id=${id}`, '_blank');
  });
});
</script>
