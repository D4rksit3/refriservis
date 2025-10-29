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
<style>

  .btn-editar i,
  .btn-eliminar i,
  .btn-reporte i {
    pointer-events: none;
  }
</style>
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
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar Mantenimiento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formEditar">
          <input type="hidden" name="id" id="edit_id">
          <div class="mb-3">
            <label>Título</label>
            <input type="text" class="form-control" name="titulo" id="edit_titulo">
          </div>
          <div class="mb-3">
            <label>Fecha</label>
            <input type="date" class="form-control" name="fecha" id="edit_fecha">
          </div>
          <div class="mb-3">
            <label>Cliente</label>
            <select class="form-control" name="cliente_id" id="edit_cliente">
              <?php
                $clientes = $pdo->query("SELECT id, cliente FROM clientes")->fetchAll(PDO::FETCH_ASSOC);
                foreach($clientes as $c) {
                  echo "<option value='{$c['id']}'>{$c['cliente']}</option>";
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<script>
let pagina = 1;
const porPagina = 10;

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
                <button class="btn btn-sm btn-outline-primary btn-editar" title="Editar" data-id="${r.id}">
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


      $('#tabla-mantenimientos').on('click', '.btn-eliminar, .btn-eliminar i', function(e){
      e.preventDefault();
      const button = $(this).closest('.btn-eliminar');
      const id = button.data('id');
      
      if(confirm('¿Seguro que deseas eliminar este mantenimiento? Esta acción no se puede deshacer.')) {
        $.ajax({
          url: '/mantenimientos/eliminar.php',
          type: 'POST',
          data: { id },
          dataType: 'json',
          success: function(res){
            if(res.success){
              alert('Mantenimiento eliminado correctamente');
              cargarMantenimientos();
            } else {
              alert(res.error || 'Error al eliminar el mantenimiento');
            }
          }
        });
      }
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





// Abrir modal editar
document.querySelector('#tabla-mantenimientos').addEventListener('click', e => {
  if(e.target.classList.contains('btn-editar')) {
    const id = e.target.dataset.id;
    const fila = e.target.closest('tr');
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_titulo').value = fila.children[1].innerText;
    document.getElementById('edit_fecha').value = fila.children[2].innerText;
    const cliente = fila.children[3].innerText;
    const selectCliente = document.getElementById('edit_cliente');
    for(let opt of selectCliente.options) opt.selected = (opt.text === cliente);
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
  }

  // Descargar reporte
  if(e.target.classList.contains('btn-reporte')) {
    const id = e.target.dataset.id;
    const url = e.target.dataset.url;
    if(url) window.open(`${url}?id=${id}`, '_blank');
  }



});

// Guardar cambios
document.getElementById('guardarEditar').addEventListener('click', () => {
  const formData = new FormData(document.getElementById('formEditar'));
  fetch('/mantenimientos/editar_ajax.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
      if(res.success) {
        alert('Guardado correctamente');
        cargarMantenimientos();
        bootstrap.Modal.getInstance(document.getElementById('modalEditar')).hide();
      } else {
        alert('Error al guardar');
      }
    });







});

// Búsqueda en tiempo real
document.getElementById('buscar').addEventListener('input', () => { pagina = 1; cargarMantenimientos(); });

// Cargar tabla inicialmente
cargarMantenimientos();



</script>
