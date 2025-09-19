<?php
require_once __DIR__.'/../config/db.php';
include __DIR__.'/../includes/header.php';

// ---- CREAR ----
if (isset($_POST['crear'])) {
  $sql = "INSERT INTO productos (nombre, descripcion, categoria, equipo, estatus, stock_actual, stock_minimo, valor_unitario, entrada_stock, planilla_especificaciones, costo_unitario) 
          VALUES (:nombre,:descripcion,:categoria,:equipo,:estatus,:stock_actual,:stock_minimo,:valor_unitario,:entrada_stock,:planilla_especificaciones,:costo_unitario)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($_POST);
  header("Location: productos.php");
  exit;
}

// ---- EDITAR ----
if (isset($_POST['editar'])) {
  $sql = "UPDATE productos SET nombre=:nombre, descripcion=:descripcion, categoria=:categoria, equipo=:equipo, estatus=:estatus, 
          stock_actual=:stock_actual, stock_minimo=:stock_minimo, valor_unitario=:valor_unitario, entrada_stock=:entrada_stock, 
          planilla_especificaciones=:planilla_especificaciones, costo_unitario=:costo_unitario WHERE id=:id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($_POST);
  header("Location: productos.php");
  exit;
}

// ---- ELIMINAR ----
if (isset($_GET['eliminar'])) {
  $stmt = $pdo->prepare("DELETE FROM productos WHERE id=?");
  $stmt->execute([$_GET['eliminar']]);
  header("Location: productos.php");
  exit;
}

// ---- LISTAR ----
$por_pagina = 10;
$pagina = $_GET['pagina'] ?? 1;
$offset = ($pagina-1)*$por_pagina;
$busqueda = $_GET['buscar'] ?? '';

$where = $busqueda ? "WHERE nombre LIKE :busqueda OR descripcion LIKE :busqueda" : "";

$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM productos $where");
if ($busqueda) $stmt_count->execute([':busqueda'=>"%$busqueda%"]); else $stmt_count->execute();
$total = $stmt_count->fetchColumn();
$total_paginas = ceil($total/$por_pagina);

$sql = "SELECT * FROM productos $where ORDER BY id DESC LIMIT :offset,:pp";
$stmt = $pdo->prepare($sql);
if ($busqueda) $stmt->bindValue(':busqueda',"%$busqueda%",PDO::PARAM_STR);
$stmt->bindValue(':offset',$offset,PDO::PARAM_INT);
$stmt->bindValue(':pp',$por_pagina,PDO::PARAM_INT);
$stmt->execute();
$productos=$stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card shadow">
  <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0">📦 Inventario de Productos</h5>
    <div>
      <form class="d-inline-flex" method="get">
        <input type="text" name="buscar" value="<?=htmlspecialchars($busqueda)?>" class="form-control form-control-sm me-2" placeholder="Buscar...">
        <button class="btn btn-light btn-sm">Buscar</button>
      </form>
      <button class="btn btn-warning btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#modalCrear">➕ Agregar</button>
    </div>
  </div>
  <div class="card-body table-responsive">
    <table class="table table-hover table-striped align-middle">
      <thead class="table-dark">
        <tr>
          <th>Nombre</th>
          <th>Descripción</th>
          <th>Categoría</th>
          <th>Equipo</th>
          <th>Estatus</th>
          <th>Stock actual</th>
          <th>Stock mínimo</th>
          <th>Valor unitario</th>
          <th>Entrada Stock</th>
          <th>Planilla</th>
          <th>Costo unitario</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($productos as $p): ?>
        <tr>
          <td><?=$p['nombre']?></td>
          <td><?=$p['descripcion']?></td>
          <td><?=$p['categoria']?></td>
          <td><?=$p['equipo']?></td>
          <td><?=$p['estatus']?></td>
          <td><?=$p['stock_actual']?></td>
          <td><?=$p['stock_minimo']?></td>
          <td><?=$p['valor_unitario']?></td>
          <td><?=$p['entrada_stock']?></td>
          <td><?=$p['planilla_especificaciones']?></td>
          <td><?=$p['costo_unitario']?></td>
          <td>
            <button class="btn btn-primary btn-sm" 
              data-bs-toggle="modal" data-bs-target="#modalEditar<?=$p['id']?>">✏️</button>
            <a href="?eliminar=<?=$p['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar producto?')">🗑️</a>
          </td>
        </tr>

        <!-- Modal Editar -->
        <div class="modal fade" id="modalEditar<?=$p['id']?>" tabindex="-1">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <form method="post">
                <div class="modal-header bg-primary text-white">
                  <h5 class="modal-title">Editar Producto</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                  <input type="hidden" name="id" value="<?=$p['id']?>">
                  <input type="hidden" name="editar" value="1">
                  <?php foreach($p as $campo=>$valor): if($campo!='id'): ?>
                    <div class="col-md-6">
                      <label class="form-label"><?=ucfirst(str_replace("_"," ",$campo))?></label>
                      <input type="text" name="<?=$campo?>" value="<?=htmlspecialchars($valor)?>" class="form-control">
                    </div>
                  <?php endif; endforeach; ?>
                </div>
                <div class="modal-footer">
                  <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  <button class="btn btn-primary">Guardar</button>
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
          <h5 class="modal-title">Agregar Producto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3">
          <input type="hidden" name="crear" value="1">
          <?php 
          $campos=['nombre','descripcion','categoria','equipo','estatus','stock_actual','stock_minimo','valor_unitario','entrada_stock','planilla_especificaciones','costo_unitario'];
          foreach($campos as $c): ?>
            <div class="col-md-6">
              <label class="form-label"><?=ucfirst(str_replace("_"," ",$c))?></label>
              <input type="text" name="<?=$c?>" class="form-control" required>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-success">Guardar</button>
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
