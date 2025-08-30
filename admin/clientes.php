<?php
// admin/clientes.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') { header('Location: /index.php'); exit; }
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim($_POST['nombre'] ?? '');
  $telefono = trim($_POST['telefono'] ?? '');
  $direccion = trim($_POST['direccion'] ?? '');
  $correo = trim($_POST['correo'] ?? '');
  if ($action === 'add') {
    $stmt = $pdo->prepare('INSERT INTO clientes (nombre,telefono,direccion,correo) VALUES (?,?,?,?)');
    $stmt->execute([$nombre,$telefono,$direccion,$correo]);
    header('Location: /admin/clientes.php?ok=1'); exit;
  } elseif ($action === 'edit') {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare('UPDATE clientes SET nombre=?,telefono=?,direccion=?,correo=? WHERE id=?');
    $stmt->execute([$nombre,$telefono,$direccion,$correo,$id]);
    header('Location: /admin/clientes.php?ok=1'); exit;
  }
}

if ($action === 'delete' && isset($_GET['id'])) {
  $pdo->prepare('DELETE FROM clientes WHERE id=?')->execute([(int)$_GET['id']]);
  header('Location: /admin/clientes.php?ok=1'); exit;
}

if ($action === 'list') {
  $lista = $pdo->query('SELECT * FROM clientes ORDER BY id DESC')->fetchAll();
  ?>
  <div class="card p-3">
    <div class="d-flex justify-content-between">
      <h5>Clientes</h5>
      <a class="btn btn-primary btn-sm" href="/admin/clientes.php?action=add">+ Nuevo Cliente</a>
    </div>
    <div class="table-responsive mt-3">
      <table class="table table-sm">
        <thead><tr><th>ID</th><th>Nombre</th><th>Teléfono</th><th>Correo</th><th></th></tr></thead>
        <tbody>
          <?php foreach($lista as $c): ?>
            <tr>
              <td><?=$c['id']?></td>
              <td><?=htmlspecialchars($c['nombre'])?></td>
              <td><?=htmlspecialchars($c['telefono'])?></td>
              <td><?=htmlspecialchars($c['correo'])?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="/admin/clientes.php?action=edit&id=<?=$c['id']?>">Editar</a>
                <a class="btn btn-sm btn-outline-danger" href="/admin/clientes.php?action=delete&id=<?=$c['id']?>" onclick="return confirm('Eliminar cliente?')">Eliminar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php
} elseif ($action === 'add' || $action === 'edit') {
  $data = ['id'=>'','nombre'=>'','telefono'=>'','direccion'=>'','correo'=>''];
  if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM clientes WHERE id=?'); $stmt->execute([$id]); $data = $stmt->fetch();
  }
  ?>
  <div class="card p-3">
    <h5><?= $action === 'add' ? 'Nuevo Cliente' : 'Editar Cliente' ?></h5>
    <form method="post" class="row g-2">
      <input type="hidden" name="id" value="<?=htmlspecialchars($data['id'])?>">
      <div class="col-12"><label class="form-label">Nombre</label><input class="form-control" name="nombre" value="<?=htmlspecialchars($data['nombre'])?>" required></div>
      <div class="col-6"><label class="form-label">Teléfono</label><input class="form-control" name="telefono" value="<?=htmlspecialchars($data['telefono'])?>"></div>
      <div class="col-6"><label class="form-label">Correo</label><input class="form-control" name="correo" value="<?=htmlspecialchars($data['correo'])?>"></div>
      <div class="col-12"><label class="form-label">Dirección</label><input class="form-control" name="direccion" value="<?=htmlspecialchars($data['direccion'])?>"></div>
      <div class="col-12 text-end"><button class="btn btn-primary"><?= $action === 'add' ? 'Crear' : 'Guardar' ?></button></div>
    </form>
  </div>
  <?php
}

require_once __DIR__.'/../includes/footer.php';
