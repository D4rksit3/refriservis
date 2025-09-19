<?php
// admin/equipos.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') { 
  header('Location: /index.php'); exit; 
}
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

$action = $_GET['action'] ?? 'list';

// === Exportar formato vacío ===
if ($action === 'download_format') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment;filename=equipos_formato.csv');
  $campos = ["Nombre","Marca","Modelo","Serie","Ubicación","Estatus"];
  $out = fopen("php://output","w");
  fputcsv($out, $campos);
  fclose($out);
  exit;
}

// === Exportar todos ===
if ($action === 'export_all') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment;filename=equipos_export.csv');
  $out = fopen("php://output","w");
  $stmt = $pdo->query("SELECT nombre,marca,modelo,serie,ubicacion,estatus FROM equipos");
  fputcsv($out, ["Nombre","Marca","Modelo","Serie","Ubicación","Estatus"]);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { fputcsv($out, $row); }
  fclose($out);
  exit;
}

// === Importar masivo ===
if ($action === 'import' && $_SERVER['REQUEST_METHOD']==='POST') {
  if (is_uploaded_file($_FILES['archivo']['tmp_name'])) {
    $file = fopen($_FILES['archivo']['tmp_name'], 'r');
    fgetcsv($file); // encabezado
    $stmt = $pdo->prepare("INSERT INTO equipos (nombre,marca,modelo,serie,ubicacion,estatus) VALUES (?,?,?,?,?,?)");
    while (($row = fgetcsv($file)) !== false) { $stmt->execute($row); }
    fclose($file);
    header("Location: /admin/equipos.php?ok=1"); exit;
  }
}

// === Agregar ===
if ($action === 'add' && $_SERVER['REQUEST_METHOD']==='POST') {
  $stmt = $pdo->prepare("INSERT INTO equipos (nombre,marca,modelo,serie,ubicacion,estatus) VALUES (?,?,?,?,?,?)");
  $stmt->execute([$_POST['nombre'],$_POST['marca'],$_POST['modelo'],$_POST['serie'],$_POST['ubicacion'],$_POST['estatus']]);
  header("Location: /admin/equipos.php?ok=1"); exit;
}

// === Editar ===
if ($action === 'edit' && $_SERVER['REQUEST_METHOD']==='POST') {
  $stmt = $pdo->prepare("UPDATE equipos SET nombre=?,marca=?,modelo=?,serie=?,ubicacion=?,estatus=? WHERE id=?");
  $stmt->execute([$_POST['nombre'],$_POST['marca'],$_POST['modelo'],$_POST['serie'],$_POST['ubicacion'],$_POST['estatus'],$_POST['id']]);
  header("Location: /admin/equipos.php?ok=1"); exit;
}

// === Eliminar ===
if ($action === 'delete' && isset($_GET['id'])) {
  $pdo->prepare('DELETE FROM equipos WHERE id=?')->execute([(int)$_GET['id']]);
  header('Location: /admin/equipos.php?ok=1'); exit;
}

// === Listado ===
if ($action === 'list') {
  $lista = $pdo->query('SELECT * FROM equipos ORDER BY id DESC')->fetchAll();
  ?>
  <div class="card p-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
      <h5>Equipos</h5>
      <div class="btn-group mt-2 mt-md-0">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAdd">+ Nuevo Equipo</button>
        <a class="btn btn-success btn-sm" href="/admin/equipos.php?action=download_format">Formato CSV</a>
        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalUpload">Subir Masivo</button>
        <a class="btn btn-outline-dark btn-sm" href="/admin/equipos.php?action=export_all">Exportar Todos</a>
      </div>
    </div>

    <div class="table-responsive mt-3">
      <table id="tablaEquipos" class="table table-striped table-sm align-middle">
        <thead class="table-light">
          <tr>
            <th>Nombre</th><th>Marca</th><th>Modelo</th><th>Serie</th><th>Ubicación</th><th>Estatus</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($lista as $e): ?>
            <tr>
              <td><?=htmlspecialchars($e['nombre'])?></td>
              <td><?=htmlspecialchars($e['marca'])?></td>
              <td><?=htmlspecialchars($e['modelo'])?></td>
              <td><?=htmlspecialchars($e['serie'])?></td>
              <td><?=htmlspecialchars($e['ubicacion'])?></td>
              <td><?=htmlspecialchars($e['estatus'])?></td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit<?=$e['id']?>">Editar</button>
                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalDelete<?=$e['id']?>">Eliminar</button>
              </td>
            </tr>

            <!-- Modal Editar -->
            <div class="modal fade" id="modalEdit<?=$e['id']?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <form method="post" action="/admin/equipos.php?action=edit">
                    <div class="modal-header"><h5 class="modal-title">Editar Equipo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body row g-2">
                      <input type="hidden" name="id" value="<?=$e['id']?>">
                      <div class="col-6"><label class="form-label">Nombre</label><input class="form-control" name="nombre" value="<?=$e['nombre']?>" required></div>
                      <div class="col-6"><label class="form-label">Marca</label><input class="form-control" name="marca" value="<?=$e['marca']?>"></div>
                      <div class="col-6"><label class="form-label">Modelo</label><input class="form-control" name="modelo" value="<?=$e['modelo']?>"></div>
                      <div class="col-6"><label class="form-label">Serie</label><input class="form-control" name="serie" value="<?=$e['serie']?>"></div>
                      <div class="col-6"><label class="form-label">Ubicación</label><input class="form-control" name="ubicacion" value="<?=$e['ubicacion']?>"></div>
                      <div class="col-6"><label class="form-label">Estatus</label><input class="form-control" name="estatus" value="<?=$e['estatus']?>"></div>
                    </div>
                    <div class="modal-footer"><button class="btn btn-primary">Guardar cambios</button></div>
                  </form>
                </div>
              </div>
            </div>

            <!-- Modal Eliminar -->
            <div class="modal fade" id="modalDelete<?=$e['id']?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header"><h5 class="modal-title">Confirmar eliminación</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                  <div class="modal-body">¿Seguro que deseas eliminar el equipo <strong><?=htmlspecialchars($e['nombre'])?></strong>?</div>
                  <div class="modal-footer">
                    <a class="btn btn-danger" href="/admin/equipos.php?action=delete&id=<?=$e['id']?>">Eliminar</a>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  </div>
                </div>
              </div>
            </div>

          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal Nuevo Equipo -->
  <div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
      <form method="post" action="/admin/equipos.php?action=add">
        <div class="modal-header"><h5 class="modal-title">Nuevo Equipo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-2">
          <div class="col-6"><label class="form-label">Nombre</label><input class="form-control" name="nombre" required></div>
          <div class="col-6"><label class="form-label">Marca</label><input class="form-control" name="marca"></div>
          <div class="col-6"><label class="form-label">Modelo</label><input class="form-control" name="modelo"></div>
          <div class="col-6"><label class="form-label">Serie</label><input class="form-control" name="serie"></div>
          <div class="col-6"><label class="form-label">Ubicación</label><input class="form-control" name="ubicacion"></div>
          <div class="col-6"><label class="form-label">Estatus</label><input class="form-control" name="estatus"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Guardar</button></div>
      </form>
    </div></div>
  </div>

  <!-- Modal Subir Masivo -->
  <div class="modal fade" id="modalUpload" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
      <form method="post" enctype="multipart/form-data" action="/admin/equipos.php?action=import">
        <div class="modal-header"><h5 class="modal-title">Subida Masiva</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <p>Selecciona el archivo CSV con el formato correcto.</p>
          <input type="file" name="archivo" class="form-control" accept=".csv" required>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Importar</button></div>
      </form>
    </div></div>
  </div>

  <!-- DataTables -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script>
    $(document).ready(function(){
      $('#tablaEquipos').DataTable({
        "pageLength": 10,
        "lengthMenu": [10, 20, 100],
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
      });
    });
  </script>
  <?php
}

require_once __DIR__.'/../includes/footer.php';
