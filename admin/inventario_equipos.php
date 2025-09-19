<?php
require_once __DIR__.'/../config/db.php';
include __DIR__.'/../includes/header.php';

// ---- CREAR ----
if (isset($_POST['crear'])) {
    $sql = "INSERT INTO equipos 
        (nombre, descripcion, identificador, colaborador, cliente, categoria, equipo_asociado, estatus, planilla_especificaciones, fecha_validacion) 
        VALUES (:nombre,:descripcion,:identificador,:colaborador,:cliente,:categoria,:equipo_asociado,:estatus,:planilla_especificaciones,:fecha_validacion)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($_POST);
    header("Location: equipos.php");
    exit;
}

// ---- EDITAR ----
if (isset($_POST['editar'])) {
    $sql = "UPDATE equipos SET 
        nombre=:nombre, descripcion=:descripcion, identificador=:identificador, colaborador=:colaborador, cliente=:cliente, categoria=:categoria, 
        equipo_asociado=:equipo_asociado, estatus=:estatus, planilla_especificaciones=:planilla_especificaciones, fecha_validacion=:fecha_validacion 
        WHERE id_equipo=:id_equipo";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($_POST);
    header("Location: equipos.php");
    exit;
}

// ---- ELIMINAR ----
if (isset($_GET['eliminar'])) {
    $stmt = $pdo->prepare("DELETE FROM equipos WHERE id_equipo=?");
    $stmt->execute([$_GET['eliminar']]);
    header("Location: equipos.php");
    exit;
}

// ---- LISTAR ----
$por_pagina = 10;
$pagina = $_GET['pagina'] ?? 1;
$offset = ($pagina-1)*$por_pagina;
$busqueda = $_GET['buscar'] ?? '';

$where = $busqueda ? "WHERE nombre LIKE :busqueda OR descripcion LIKE :busqueda" : "";

$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM equipos $where");
if ($busqueda) $stmt_count->execute([':busqueda'=>"%$busqueda%"]); else $stmt_count->execute();
$total = $stmt_count->fetchColumn();
$total_paginas = ceil($total/$por_pagina);

$sql = "SELECT * FROM equipos $where ORDER BY id_equipo DESC LIMIT :offset,:pp";
$stmt = $pdo->prepare($sql);
if ($busqueda) $stmt->bindValue(':busqueda',"%$busqueda%",PDO::PARAM_STR);
$stmt->bindValue(':offset',$offset,PDO::PARAM_INT);
$stmt->bindValue(':pp',$por_pagina,PDO::PARAM_INT);
$stmt->execute();
$equipos=$stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card shadow">
  <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0">🖥️ Inventario de Equipos</h5>
    <div>
      <form class="d-inline-flex" method="get">
        <input type="text" name="buscar" value="<?=htmlspecialchars($busqueda)?>" class="form-control form-control-sm me-2" placeholder="Buscar...">
        <button class="btn btn-light btn-sm">Buscar</button>
      </form>
      <button class="btn btn-success btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#modalCrear">➕ Agregar</button>
    </div>
  </div>

  <div class="card-body table-responsive">
    <table id="tablaEquipos" class="table table-hover table-striped align-middle">
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
          <th>Acciones</th>
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
          <td>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditar<?=$eq['id_equipo']?>">✏️</button>
            <a href="?eliminar=<?=$eq['id_equipo']?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar equipo?')">🗑️</a>
          </td>
        </tr>

        <!-- Modal Editar -->
        <div class="modal fade" id="modalEditar<?=$eq['id_equipo']?>" tabindex="-1">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <form method="post">
                <div class="modal-header bg-primary text-white">
                  <h5 class="modal-title">Editar Equipo</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                  <input type="hidden" name="id_equipo" value="<?=$eq['id_equipo']?>">
                  <input type="hidden" name="editar" value="1">
                  <?php foreach($eq as $campo=>$valor): if($campo!='id_equipo'): ?>
                    <div class="col-md-6">
                      <label class="form-label"><?=ucfirst(str_replace("_"," ",$campo))?></label>
                      <input type="text" name="<?=$campo?>" value="<?=htmlspecialchars($valor)?>" class="form-control">
                    </div>
                  <?php endif; endforeach; ?>
                </div>
                <div class="modal-footer">
                  <button class="btn btn-primary">Guardar</button>
                  <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
              </form>
            </div>
          </div>
        </div>

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

<!-- Modal Crear -->
<div class="modal fade" id="modalCrear" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Agregar Equipo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3">
          <input type="hidden" name="crear" value="1">
          <?php 
          $campos=['nombre','descripcion','identificador','colaborador','cliente','categoria','equipo_asociado','estatus','planilla_especificaciones','fecha_validacion'];
          foreach($campos as $c): ?>
            <div class="col-md-6">
              <label class="form-label"><?=ucfirst(str_replace("_"," ",$c))?></label>
              <?php if($c=='estatus'): ?>
                <select name="estatus" class="form-select">
                  <option>Activo</option>
                  <option>Inactivo</option>
                </select>
              <?php elseif($c=='fecha_validacion'): ?>
                <input type="date" name="<?=$c?>" class="form-control">
              <?php else: ?>
                <input type="text" name="<?=$c?>" class="form-control" required>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="modal-footer">
          <button class="btn btn-success">Guardar</button>
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#tablaEquipos').DataTable({
        pageLength: 10,
        lengthChange: false,
        language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
    });
});
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>
