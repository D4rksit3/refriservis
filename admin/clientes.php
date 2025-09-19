<?php
// admin/clientes.php
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
  header('Content-Disposition: attachment;filename=clientes_formato.csv');
  $campos = [
    "Cliente","Dirección","Teléfono","Responsable","Email","Última visita","Estatus"
  ];
  $out = fopen("php://output","w");
  fputcsv($out, $campos);
  fclose($out);
  exit;
}

// === Exportar todos los clientes ===
if ($action === 'export_all') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment;filename=clientes_export.csv');
  $out = fopen("php://output","w");
  $stmt = $pdo->query("SELECT cliente,direccion,telefono,responsable,email,ultima_visita,estatus FROM clientes");
  fputcsv($out, ["Cliente","Dirección","Teléfono","Responsable","Email","Última visita","Estatus"]);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, $row);
  }
  fclose($out);
  exit;
}

// === Importar CSV masivo ===
if ($action === 'import' && $_SERVER['REQUEST_METHOD']==='POST') {
  if (is_uploaded_file($_FILES['archivo']['tmp_name'])) {
    $file = fopen($_FILES['archivo']['tmp_name'], 'r');
    $header = fgetcsv($file); // omitir encabezado

    $stmt = $pdo->prepare("
      INSERT INTO clientes (cliente,direccion,telefono,responsable,email,ultima_visita,estatus)
      VALUES (?,?,?,?,?,?,?)
    ");

    while (($row = fgetcsv($file)) !== false) {
      $stmt->execute($row);
    }
    fclose($file);
    header("Location: /admin/clientes.php?ok=1");
    exit;
  }
}

// === Agregar cliente individual ===
if ($action === 'add' && $_SERVER['REQUEST_METHOD']==='POST') {
  $stmt = $pdo->prepare("
    INSERT INTO clientes (cliente,direccion,telefono,responsable,email,ultima_visita,estatus)
    VALUES (?,?,?,?,?,?,?)
  ");
  $stmt->execute([
    $_POST['cliente'], $_POST['direccion'], $_POST['telefono'],
    $_POST['responsable'], $_POST['email'], $_POST['ultima_visita'], $_POST['estatus']
  ]);
  header("Location: /admin/clientes.php?ok=1");
  exit;
}

// === Editar cliente ===
if ($action === 'edit' && $_SERVER['REQUEST_METHOD']==='POST') {
  $stmt = $pdo->prepare("
    UPDATE clientes 
    SET cliente=?, direccion=?, telefono=?, responsable=?, email=?, ultima_visita=?, estatus=?
    WHERE id=?
  ");
  $stmt->execute([
    $_POST['cliente'], $_POST['direccion'], $_POST['telefono'],
    $_POST['responsable'], $_POST['email'], $_POST['ultima_visita'], $_POST['estatus'], $_POST['id']
  ]);
  header("Location: /admin/clientes.php?ok=1");
  exit;
}

// === Eliminar cliente ===
if ($action === 'delete' && isset($_GET['id'])) {
  $pdo->prepare('DELETE FROM clientes WHERE id=?')->execute([(int)$_GET['id']]);
  header('Location: /admin/clientes.php?ok=1'); exit;
}

// === Listado de clientes ===
if ($action === 'list') {
  $lista = $pdo->query('SELECT * FROM clientes ORDER BY id DESC')->fetchAll();
  ?>
  <div class="card p-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
      <h5>Clientes</h5>
      <div class="btn-group mt-2 mt-md-0">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAdd">+ Nuevo Cliente</button>
        <a class="btn btn-success btn-sm" href="/admin/clientes.php?action=download_format">Formato CSV</a>
        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalUpload">Subir Masivo</button>
        <a class="btn btn-outline-dark btn-sm" href="/admin/clientes.php?action=export_all">Exportar Todos</a>
      </div>
    </div>

    <div class="table-responsive mt-3">
      <table id="tablaClientes" class="table table-striped table-sm align-middle">
        <thead class="table-light">
          <tr>
            <th>Cliente</th>
            <th>Dirección</th>
            <th>Teléfono</th>
            <th>Responsable</th>
            <th>Email</th>
            <th>Última visita</th>
            <th>Estatus</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($lista as $c): ?>
            <tr>
              <td><?=htmlspecialchars($c['cliente'])?></td>
              <td><?=htmlspecialchars($c['direccion'])?></td>
              <td><?=htmlspecialchars($c['telefono'])?></td>
              <td><?=htmlspecialchars($c['responsable'])?></td>
              <td><?=htmlspecialchars($c['email'])?></td>
              <td><?=htmlspecialchars($c['ultima_visita'])?></td>
              <td><?=htmlspecialchars($c['estatus'])?></td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-primary" 
                        data-bs-toggle="modal" 
                        data-bs-target="#modalEdit<?=$c['id']?>">Editar</button>
                <button class="btn btn-sm btn-outline-danger" 
                        data-bs-toggle="modal" 
                        data-bs-target="#modalDelete<?=$c['id']?>">Eliminar</button>
              </td>
            </tr>

            <!-- Modal Editar -->
            <div class="modal fade" id="modalEdit<?=$c['id']?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <form method="post" action="/admin/clientes.php?action=edit">
                    <div class="modal-header">
                      <h5 class="modal-title">Editar Cliente</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-2">
                      <input type="hidden" name="id" value="<?=$c['id']?>">
                      <div class="col-12"><label class="form-label">Cliente</label><input class="form-control" name="cliente" value="<?=$c['cliente']?>" required></div>
                      <div class="col-12"><label class="form-label">Dirección</label><input class="form-control" name="direccion" value="<?=$c['direccion']?>"></div>
                      <div class="col-6"><label class="form-label">Teléfono</label><input class="form-control" name="telefono" value="<?=$c['telefono']?>"></div>
                      <div class="col-6"><label class="form-label">Responsable</label><input class="form-control" name="responsable" value="<?=$c['responsable']?>"></div>
                      <div class="col-12"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?=$c['email']?>"></div>
                      <div class="col-6"><label class="form-label">Última visita</label><input class="form-control" type="date" name="ultima_visita" value="<?=$c['ultima_visita']?>"></div>
                      <div class="col-6"><label class="form-label">Estatus</label><input class="form-control" name="estatus" value="<?=$c['estatus']?>"></div>
                    </div>
                    <div class="modal-footer">
                      <button class="btn btn-primary">Guardar cambios</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <!-- Modal Eliminar -->
            <div class="modal fade" id="modalDelete<?=$c['id']?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    ¿Seguro que deseas eliminar al cliente <strong><?=htmlspecialchars($c['cliente'])?></strong>?
                  </div>
                  <div class="modal-footer">
                    <a class="btn btn-danger" href="/admin/clientes.php?action=delete&id=<?=$c['id']?>">Eliminar</a>
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

  <!-- Modal Nuevo Cliente -->
  <div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" action="/admin/clientes.php?action=add">
          <div class="modal-header">
            <h5 class="modal-title">Nuevo Cliente</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body row g-2">
            <div class="col-12"><label class="form-label">Cliente</label><input class="form-control" name="cliente" required></div>
            <div class="col-12"><label class="form-label">Dirección</label><input class="form-control" name="direccion"></div>
            <div class="col-6"><label class="form-label">Teléfono</label><input class="form-control" name="telefono"></div>
            <div class="col-6"><label class="form-label">Responsable</label><input class="form-control" name="responsable"></div>
            <div class="col-12"><label class="form-label">Email</label><input class="form-control" type="email" name="email"></div>
            <div class="col-6"><label class="form-label">Última visita</label><input class="form-control" type="date" name="ultima_visita"></div>
            <div class="col-6"><label class="form-label">Estatus</label><input class="form-control" name="estatus"></div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-primary">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Subir Masivo -->
  <div class="modal fade" id="modalUpload" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" enctype="multipart/form-data" action="/admin/clientes.php?action=import">
          <div class="modal-header">
            <h5 class="modal-title">Subida Masiva</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>Selecciona el archivo CSV con el formato correcto.</p>
            <input type="file" name="archivo" class="form-control" accept=".csv" required>
          </div>
          <div class="modal-footer">
            <button class="btn btn-primary">Importar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- DataTables -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script>
    $(document).ready(function(){
      $('#tablaClientes').DataTable({
        "pageLength": 10,
        "lengthMenu": [10, 20, 100],
        "language": {
          "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
      });
    });
  </script>

  <?php
}

require_once __DIR__.'/../includes/footer.php';
