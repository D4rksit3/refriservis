<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';

// =====================================
// MODO AJAX -> SOLO JSON PARA DATATABLES
// =====================================
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json; charset=utf-8');

    $draw     = $_GET['draw']   ?? 1;
    $start    = $_GET['start']  ?? 0;
    $length   = $_GET['length'] ?? 10;
    $search   = $_GET['search']['value'] ?? '';
    $orderCol = $_GET['order'][0]['column'] ?? 0;
    $orderDir = $_GET['order'][0]['dir']    ?? 'asc';

    $columns  = ['id_equipo','Nombre','Descripcion','Cliente','Categoria','Estatus','Fecha_validad'];
    $orderBy  = $columns[$orderCol] ?? 'id_equipo';

    // Total registros
    $totalQuery    = $pdo->query("SELECT COUNT(*) FROM equipos");
    $totalRecords  = $totalQuery->fetchColumn();

    // Filtrado
    $where  = '';
    $params = [];
    if (!empty($search)) {
        $where = "WHERE Nombre LIKE ? OR Descripcion LIKE ? OR Cliente LIKE ? OR Categoria LIKE ? OR Estatus LIKE ?";
        $params = array_fill(0, 5, "%$search%");
    }

    // Total filtrado
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM equipos $where");
    $stmt->execute($params);
    $filteredRecords = $stmt->fetchColumn();

    // Datos
    $sql = "SELECT * FROM equipos $where ORDER BY $orderBy $orderDir LIMIT ?, ?";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $i => $val) {
        $stmt->bindValue($i+1, $val, PDO::PARAM_STR);
    }
    $stmt->bindValue(count($params)+1, (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(count($params)+2, (int)$length, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // JSON limpio
    echo json_encode([
        "draw"            => intval($draw),
        "recordsTotal"    => $totalRecords,
        "recordsFiltered" => $filteredRecords,
        "data"            => $data
    ]);
    exit;
}

// =====================================
// CRUD (agregar, editar, eliminar)
// =====================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    if ($accion === 'agregar') {
        $sql = "INSERT INTO equipos (Nombre, Descripcion, Cliente, Categoria, Estatus, Fecha_validad) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['Nombre'],
            $_POST['Descripcion'],
            $_POST['Cliente'],
            $_POST['Categoria'],
            $_POST['Estatus'],
            $_POST['Fecha_validad']
        ]);
    }

    if ($accion === 'editar') {
        $sql = "UPDATE equipos SET 
                Nombre=?, Descripcion=?, Cliente=?, Categoria=?, Estatus=?, Fecha_validad=? 
                WHERE id_equipo=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['Nombre'],
            $_POST['Descripcion'],
            $_POST['Cliente'],
            $_POST['Categoria'],
            $_POST['Estatus'],
            $_POST['Fecha_validad'],
            $_POST['id_equipo']
        ]);
    }

    if ($accion === 'eliminar') {
        $sql = "DELETE FROM equipos WHERE id_equipo=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['id_equipo']]);
    }
}

// =====================================
// HTML NORMAL
// =====================================
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="h4">📋 Inventario de Equipos</h2>
  <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregar">➕ Nuevo</button>
</div>

<div class="table-responsive shadow-sm">
  <table id="tablaEquipos" class="table table-striped align-middle">
    <thead class="table-primary">
      <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Descripcion</th>
        <th>Cliente</th>
        <th>Categoria</th>
        <th>Estatus</th>
        <th>Fecha Validación</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- Modal Agregar -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">➕ Nuevo Equipo</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="accion" value="agregar">
          <div class="mb-2"><label>Nombre</label><input type="text" class="form-control" name="Nombre" required></div>
          <div class="mb-2"><label>Descripcion</label><textarea class="form-control" name="Descripcion"></textarea></div>
          <div class="mb-2"><label>Cliente</label><input type="text" class="form-control" name="Cliente"></div>
          <div class="mb-2"><label>Categoria</label><input type="text" class="form-control" name="Categoria"></div>
          <div class="mb-2"><label>Estatus</label>
            <select class="form-select" name="Estatus">
              <option>Activo</option>
              <option>Inactivo</option>
            </select>
          </div>
          <div class="mb-2"><label>Fecha Validación</label><input type="date" class="form-control" name="Fecha_validad"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Agregar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- jQuery + DataTables -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Scripts propios -->
<script src="/../assets/js/scripts.js"></script>
