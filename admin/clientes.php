<?php
// admin/clientes.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
  header('Location: /index.php');
  exit;
}
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

$action = $_GET['action'] ?? 'list';

// === Exportar formato vacío ===
if ($action === 'download_format') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment;filename=clientes_formato.csv');
  $campos = [
    "Código","Código externo","Nombre","Número identificación",
    "Dirección","Teléfono corporativo","Email corporativo",
    "Responsable","Última visita","Estatus"
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
  $stmt = $pdo->query("SELECT * FROM clientes");
  $header = array_keys($stmt->fetch(PDO::FETCH_ASSOC));
  fputcsv($out, $header);
  $stmt = $pdo->query("SELECT * FROM clientes");
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
    fgetcsv($file); // saltar encabezado
    $stmt = $pdo->prepare("
      INSERT INTO clientes (codigo,codigo_externo,nombre,identificacion,direccion,telefono_corporativo,email_corporativo,responsable,ultima_visita,estatus)
      VALUES (?,?,?,?,?,?,?,?,?,?)
    ");
    while (($row = fgetcsv($file)) !== false) {
      $stmt->execute($row);
    }
    fclose($file);
    header("Location: /admin/clientes.php?ok=1");
    exit;
  }
}

// === Agregar cliente ===
if ($action === 'add' && $_SERVER['REQUEST_METHOD']==='POST') {
  $stmt = $pdo->prepare("
    INSERT INTO clientes (codigo,codigo_externo,nombre,identificacion,direccion,telefono_corporativo,email_corporativo,responsable,ultima_visita,estatus)
    VALUES (?,?,?,?,?,?,?,?,?,?)
  ");
  $stmt->execute([
    $_POST['codigo'], $_POST['codigo_externo'], $_POST['nombre'], $_POST['identificacion'],
    $_POST['direccion'], $_POST['telefono_corporativo'], $_POST['email_corporativo'],
    $_POST['responsable'], $_POST['ultima_visita'], $_POST['estatus']
  ]);
  header("Location: /admin/clientes.php?ok=1");
  exit;
}

// === Editar cliente ===
if ($action === 'edit' && $_SERVER['REQUEST_METHOD']==='POST') {
  $stmt = $pdo->prepare("
    UPDATE clientes SET codigo=?,codigo_externo=?,nombre=?,identificacion=?,direccion=?,telefono_corporativo=?,email_corporativo=?,responsable=?,ultima_visita=?,estatus=? 
    WHERE id=?
  ");
  $stmt->execute([
    $_POST['codigo'], $_POST['codigo_externo'], $_POST['nombre'], $_POST['identificacion'],
    $_POST['direccion'], $_POST['telefono_corporativo'], $_POST['email_corporativo'],
    $_POST['responsable'], $_POST['ultima_visita'], $_POST['estatus'], $_POST['id']
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
      <table id="tablaClientes" class="table table-striped table-bordered align-middle w-100">
        <thead class="table-light">
          <tr>
            <th>ID</th><th>Cliente</th><th>Dirección</th><th>Teléfono</th><th>Responsable</th>
            <th>Email</th><th>Última visita</th><th>Estatus</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($lista as $c): ?>
            <tr>
              <td><?=$c['id']?></td>
              <td><?=htmlspecialchars($c['nombre'])?></td>
              <td><?=htmlspecialchars($c['direccion'])?></td>
              <td><?=htmlspecialchars($c['telefono_corporativo'])?></td>
              <td><?=htmlspecialchars($c['responsable'])?></td>
              <td><?=htmlspecialchars($c['email_corporativo'])?></td>
              <td><?=htmlspecialchars($c['ultima_visita'])?></td>
              <td><?=htmlspecialchars($c['estatus'])?></td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-primary" 
                  data-bs-toggle="modal" 
                  data-bs-target="#modalEdit<?=$c['id']?>">Editar</button>
                <a class="btn btn-sm btn-outline-danger" href="/admin/clientes.php?action=delete&id=<?=$c['id']?>" onclick="return confirm('Eliminar cliente?')">Eliminar</a>
              </td>
            </tr>

            <!-- Modal Editar Cliente -->
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
                      <div class="col-6"><label class="form-label">Código</label><input class="form-control" name="codigo" value="<?=htmlspecialchars($c['codigo'])?>"></div>
                      <div class="col-6"><label class="form-label">Código Externo</label><input class="form-control" name="codigo_externo" value="<?=htmlspecialchars($c['codigo_externo'])?>"></div>
                      <div class="col-12"><label class="form-label">Nombre</label><input class="form-control" name="nombre" value="<?=htmlspecialchars($c['nombre'])?>"></div>
                      <div class="col-12"><label class="form-label">Identificación</label><input class="form-control" name="identificacion" value="<?=htmlspecialchars($c['identificacion'])?>"></div>
                      <div class="col-12"><label class="form-label">Dirección</label><input class="form-control" name="direccion" value="<?=htmlspecialchars($c['direccion'])?>"></div>
                      <div class="col-6"><label class="form-label">Teléfono</label><input class="form-control" name="telefono_corporativo" value="<?=htmlspecialchars($c['telefono_corporativo'])?>"></div>
                      <div class="col-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email_corporativo" value="<?=htmlspecialchars($c['email_corporativo'])?>"></div>
                      <div class="col-6"><label class="form-label">Responsable</label><input class="form-control" name="responsable" value="<?=htmlspecialchars($c['responsable'])?>"></div>
                      <div class="col-6"><label class="form-label">Última visita</label><input class="form-control" name="ultima_visita" value="<?=htmlspecialchars($c['ultima_visita'])?>"></div>
                      <div class="col-6"><label class="form-label">Estatus</label><input class="form-control" name="estatus" value="<?=htmlspecialchars($c['estatus'])?>"></div>
                    </div>
                    <div class="modal-footer">
                      <button class="btn btn-primary">Guardar cambios</button>
                    </div>
                  </form>
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
            <div class="col-6"><label class="form-label">Código</label><input class="form-control" name="codigo"></div>
            <div class="col-6"><label class="form-label">Código Externo</label><input class="form-control" name="codigo_externo"></div>
            <div class="col-12"><label class="form-label">Nombre</label><input class="form-control" name="nombre" required></div>
            <div class="col-12"><label class="form-label">Identificación</label><input class="form-control" name="identificacion"></div>
            <div class="col-12"><label class="form-label">Dirección</label><input class="form-control" name="direccion"></div>
            <div class="col-6"><label class="form-label">Teléfono</label><input class="form-control" name="telefono_corporativo"></div>
            <div class="col-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email_corporativo"></div>
            <div class="col-6"><label class="form-label">Responsable</label><input class="form-control" name="responsable"></div>
            <div class="col-6"><label class="form-label">Última visita</label><input class="form-control" name="ultima_visita"></div>
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
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      new DataTable('#tablaClientes', {
        pageLength: 10,
        responsive: true,
        language: {
          url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        }
      });
    });
  </script>

  <?php
}

require_once __DIR__.'/../includes/footer.php';
