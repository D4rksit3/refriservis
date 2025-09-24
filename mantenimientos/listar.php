<?php
// mantenimientos/listar.php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: /index.php'); exit; }
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

// ============================
// Parámetros de búsqueda
// ============================
$busqueda = $_GET['buscar'] ?? '';
$where = '1=1';
$params = [];

// Filtro por rol
if ($_SESSION['rol'] === 'digitador') {
  $where = 'digitador_id = ?';
  $params[] = $_SESSION['usuario_id'];
} elseif ($_SESSION['rol'] === 'operador') {
  $where = 'operador_id = ?';
  $params[] = $_SESSION['usuario_id'];
}

// Filtro por búsqueda en título
if ($busqueda) {
    $where .= " AND titulo LIKE ?";
    $params[] = "%$busqueda%";
}

// ============================
// Paginación
// ============================
$por_pagina = 10;
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$inicio = ($pagina - 1) * $por_pagina;

// Contar total de registros
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM mantenimientos WHERE $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$total_paginas = ceil($total / $por_pagina);

// Traemos mantenimientos con límite
$stmt = $pdo->prepare("SELECT * FROM mantenimientos WHERE $where ORDER BY creado_en DESC LIMIT $inicio, $por_pagina");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Traer todos los equipos para mapear por id
$equiposAll = $pdo->query("SELECT id_equipo, Nombre FROM equipos")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="card p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5>Mantenimientos</h5>
    <?php if($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'digitador'): ?>
      <a class="btn btn-primary btn-sm" href="/mantenimientos/crear.php">+ Nuevo</a>
    <?php endif; ?>
  </div>

  <!-- Formulario de búsqueda -->
  <form method="get" class="mb-3">
    <div class="input-group">
      <input type="text" name="buscar" class="form-control" placeholder="Buscar por título..." value="<?=htmlspecialchars($busqueda)?>">
      <button class="btn btn-outline-secondary" type="submit">Buscar</button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-sm">
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
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?=$r['id']?></td>
            <td><?=htmlspecialchars($r['titulo'])?></td>
            <td><?=$r['fecha']?></td>
            <td>
              <?php
                if ($r['cliente_id']) {
                    $c = $pdo->query("SELECT cliente FROM clientes WHERE id=".$r['cliente_id'])->fetchColumn();
                    echo htmlspecialchars($c);
                } else { echo '-'; }
              ?>
            </td>
            <td>
              <?php
                $equipoNombres = [];
                for ($i=1;$i<=7;$i++) {
                    $eqId = $r['equipo'.$i];
                    if ($eqId && isset($equiposAll[$eqId])) {
                        $equipoNombres[] = htmlspecialchars($equiposAll[$eqId]);
                    }
                }
                echo $equipoNombres ? implode(', ', $equipoNombres) : '-';
              ?>
            </td>
            <td>
              <span class="badge bg-<?= $r['estado']==='finalizado' ? 'success' : ($r['estado']==='en proceso' ? 'warning text-dark' : 'secondary') ?>">
                <?=htmlspecialchars($r['estado'])?>
              </span>
            </td>
            <td>
              <?php
                if ($r['digitador_id']) {
                    $d = $pdo->query("SELECT nombre FROM usuarios WHERE id=".$r['digitador_id'])->fetchColumn();
                    echo htmlspecialchars($d);
                } else { echo '-'; }
              ?>
            </td>
            <td>
              <?php
                if ($r['operador_id']) {
                    $o = $pdo->query("SELECT nombre FROM usuarios WHERE id=".$r['operador_id'])->fetchColumn();
                    echo htmlspecialchars($o);
                } else { echo '-'; }
              ?>
            </td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="/mantenimientos/editar.php?id=<?=$r['id']?>">Ver / Editar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginación -->
  <nav>
    <ul class="pagination justify-content-center">
      <?php for($p=1;$p<=$total_paginas;$p++): ?>
        <li class="page-item <?= $p==$pagina ? 'active' : '' ?>">
          <a class="page-link" href="?pagina=<?=$p?>&buscar=<?=urlencode($busqueda)?>"><?=$p?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>
