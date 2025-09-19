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
    "Código","Código externo","Nombre","Número de identificación personal/empresarial",
    "Dirección","Complemento de la dirección","Teléfono corporativo","Email corporativo",
    "Hablar con","Usuario responsable","Grupos","Segmento","Observación",
    "Latitud","Longitud","Estatus","Anotación","Grupo de colaboradores responsables",
    "Última visita","Fecha de Registro","Usuario registro","Email de Cobranza",
    "Código Postal de Cobranza","Dirección de Cobranza","Número de la dirección de cobranza",
    "Complemento de la dirección de cobranza","Barrio de la dirección de cobranza",
    "Ciudad de la dirección de Cobranza","Estado/Provincia/Departamento de Cobranza"
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
    $header = fgetcsv($file); // omitir encabezado

    $stmt = $pdo->prepare("
      INSERT INTO clientes 
      (codigo,codigo_externo,nombre,identificacion,direccion,complemento_direccion,telefono_corporativo,email_corporativo,
      hablar_con,usuario_responsable,grupos,segmento,observacion,latitud,longitud,estatus,anotacion,grupo_colaboradores,
      ultima_visita,fecha_registro,usuario_registro,email_cobranza,codigo_postal_cobranza,direccion_cobranza,
      numero_direccion_cobranza,complemento_direccion_cobranza,barrio_cobranza,ciudad_cobranza,estado_cobranza)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
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
    INSERT INTO clientes (codigo,codigo_externo,nombre,identificacion,direccion,telefono_corporativo,email_corporativo)
    VALUES (?,?,?,?,?,?,?)
  ");
  $stmt->execute([
    $_POST['codigo'], $_POST['codigo_externo'], $_POST['nombre'], $_POST['identificacion'],
    $_POST['direccion'], $_POST['telefono_corporativo'], $_POST['email_corporativo']
  ]);
  header("Location: /admin/clientes.php?ok=1");
  exit;
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
      <table class="table table-striped table-sm align-middle">
        <thead class="table-light">
          <tr>
            <th>ID</th><th>Código</th><th>Nombre</th><th>Teléfono</th><th>Email</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($lista as $c): ?>
            <tr>
              <td><?=$c['id']?></td>
              <td><?=htmlspecialchars($c['codigo'])?></td>
              <td><?=htmlspecialchars($c['nombre'])?></td>
              <td><?=htmlspecialchars($c['telefono_corporativo'])?></td>
              <td><?=htmlspecialchars($c['email_corporativo'])?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-danger" href="/admin/clientes.php?action=delete&id=<?=$c['id']?>" onclick="return confirm('Eliminar cliente?')">Eliminar</a>
              </td>
            </tr>
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
  <?php
}

// === Eliminar cliente ===
if ($action === 'delete' && isset($_GET['id'])) {
  $pdo->prepare('DELETE FROM clientes WHERE id=?')->execute([(int)$_GET['id']]);
  header('Location: /admin/clientes.php?ok=1'); exit;
}

require_once __DIR__.'/../includes/footer.php';
