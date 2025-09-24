<?php
// mantenimientos/listar.php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: /index.php'); exit; }
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

// ============================
// Manejo de AJAX
// ============================
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
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

    // Filtro búsqueda
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

    // Preparar respuesta
    $result = [];
    foreach ($rows as $r) {
        $equipoNombres = [];
        for ($i=1; $i<=7; $i++) {
            $eqId = $r['equipo'.$i];
            if ($eqId && isset($equiposAll[$eqId])) $equipoNombres[] = $equiposAll[$eqId];
        }

        // Estado color
        $estado_color = $r['estado']==='finalizado' ? 'success' : ($r['estado']==='en proceso' ? 'warning text-dark' : 'secondary');

        // Cliente, digitador, operador
        $cliente = $r['cliente_id'] ? $pdo->query("SELECT cliente FROM clientes WHERE id=".$r['cliente_id'])->fetchColumn() : null;
        $digitador = $r['digitador_id'] ? $pdo->query("SELECT nombre FROM usuarios WHERE id=".$r['digitador_id'])->fetchColumn() : null;
        $operador = $r['operador_id'] ? $pdo->query("SELECT nombre FROM usuarios WHERE id=".$r['operador_id'])->fetchColumn() : null;

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
        ];
    }

    echo json_encode([
        'rows'=>$result,
        'total_paginas'=>$total_paginas
    ]);
    exit;
}
?>

<div class="card p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5>Mantenimientos</h5>
    <?php if($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'digitador'): ?>
      <a class="btn btn-primary btn-sm" href="/mantenimientos/crear.php">+ Nuevo</a>
    <?php endif; ?>
  </div>

  <input type="text" id="buscar" class="form-control mb-3" placeholder="Buscar por título...">

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
            <a class="btn btn-sm btn-outline-primary" href="/mantenimientos/editar.php?id=${r.id}">Ver / Editar</a>
          </td>
        `;
        tbody.appendChild(tr);
      });

      // Paginación
      const pagUl = document.getElementById('paginacion');
      pagUl.innerHTML = '';
      for (let p = 1; p <= data.total_paginas; p++) {
        const li = document.createElement('li');
        li.className = `page-item ${p === pagina ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#">${p}</a>`;
        li.addEventListener('click', (e) => { e.preventDefault(); pagina = p; cargarMantenimientos(); });
        pagUl.appendChild(li);
      }
    });
}

// Búsqueda en tiempo real
document.getElementById('buscar').addEventListener('input', () => { pagina = 1; cargarMantenimientos(); });

// Cargar inicialmente
cargarMantenimientos();
</script>
