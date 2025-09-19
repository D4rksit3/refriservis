<?php
require_once __DIR__.'/../config/db.php';
include __DIR__.'/../includes/header.php';

// Paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $por_pagina;

// Filtro de búsqueda
$busqueda = $_GET['buscar'] ?? '';
$where = '';
if ($busqueda) {
  $where = "WHERE nombre LIKE :busqueda OR descripcion LIKE :busqueda OR identificador LIKE :busqueda";
}

// Total de registros
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM equipos $where");
if ($busqueda) $stmt_count->execute([':busqueda' => "%$busqueda%"]);
else $stmt_count->execute();
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $por_pagina);

// Datos
$sql = "SELECT * FROM equipos $where ORDER BY fecha_validacion DESC LIMIT :offset, :por_pagina";
$stmt = $pdo->prepare($sql);
if ($busqueda) $stmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':por_pagina', $por_pagina, PDO::PARAM_INT);
$stmt->execute();
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card shadow">
  <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0">📦 Lista de Equipos</h5>
    <form class="d-flex" method="get">
      <input type="text" name="buscar" value="<?=htmlspecialchars($busqueda)?>" class="form-control form-control-sm me-2" placeholder="Buscar...">
      <button class="btn btn-light btn-sm">Buscar</button>
    </form>
  </div>
  <div class="card-body table-responsive">
    <table class="table table-hover table-striped align-middle">
      <thead class="table-dark">
        <tr>
          <th>Nombre</th>
          <th>Descripción</th>
          <th>Identificador</th>
          <th>Colaborador</th>
          <th>Cliente</th>
          <th>Categoría</th>
          <th>Equipo Asociado</th>
          <th>Estatus</th>
          <th>Planilla</th>
          <th>Fecha Validación</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($equipos as $eq): ?>
        <tr>
          <td><?=$eq['nombre']?></td>
          <td><?=$eq['descripcion']?></td>
          <td><?=$eq['identificador']?></td>
          <td><?=$eq['colaborador']?></td>
          <td><?=$eq['cliente']?></td>
          <td><?=$eq['categoria']?></td>
          <td><?=$eq['equipo_asociado']?></td>
          <td><?=$eq['estatus']?></td>
          <td><?=$eq['planilla_especificaciones']?></td>
          <td><?=$eq['fecha_validacion']?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer">
    <nav>
      <ul class="pagination pagination-sm mb-0 justify-content-center">
        <?php for($i=1;$i<=$total_paginas;$i++): ?>
          <li class="page-item <?=$i==$pagina?'active':''?>">
            <a class="page-link" href="?pagina=<?=$i?>&buscar=<?=urlencode($busqueda)?>"><?=$i?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
  $(document).ready(function() {
    // Inicializar DataTables con español y 10 filas por página
    $('table').DataTable({
      pageLength: 10,
      lengthChange: false,
      language: {
        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
      }
    });
  });
</script>


<?php include __DIR__.'/../includes/footer.php'; ?>
