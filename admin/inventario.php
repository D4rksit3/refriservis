<?php
// admin/inventario.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') { header('Location: /index.php'); exit; }
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim($_POST['nombre'] ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');
  $cantidad = (int)($_POST['cantidad'] ?? 0);
  $ubicacion = trim($_POST['ubicacion'] ?? '');
  if ($action === 'add') {
    $stmt = $pdo->prepare('INSERT INTO inventario (nombre,descripcion,cantidad,ubicacion) VALUES (?,?,?,?)');
    $stmt->execute([$nombre,$descripcion,$cantidad,$ubicacion]);
    header('Location: /admin/inventario.php?ok=1'); exit;
  } elseif ($action === 'edit') {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare('UPDATE inventario SET nombre=?,descripcion=?,cantidad=?,ubicacion=? WHERE id=?');
    $stmt->execute([$nombre,$descripcion,$cantidad,$ubicacion,$id]);
    header('Location: /admin/inventario.php?ok=1'); exit;
  }
}

if ($action === 'delete' && isset($_GET['id'])) {
  $pdo->prepare('DELETE FROM inventario WHERE id=?')->execute([(int)$_GET['id']]);
  header('Location: /admin/inventario.php?ok=1'); exit;
}

if ($action === 'list') {
  $lista = $pdo->query('SELECT * FROM inventario ORDER BY id DESC')->fetchAll();
  ?>
  <div class="card p-3">
    <div class="d-flex justify-content-between">
      <h5>Inventario</h5>
      <a class="btn btn-primary btn-sm" href="/admin/inventario.php?action=add">+ Agregar Item</a>
    </div>
    <div class="table-responsive mt-3">
      <table class="table table-sm">
        <thead><tr><th>ID</th><th>Nombre</th><th>Cantidad</th><th>Ubicación</th><th></th></tr></thead>
        <tbody>
          <?php foreach($lista as $it): ?>
            <tr>
              <td><?=$it['id']?></td>
              <td><?=htmlspecialchars($it['nombre'])?></td>
              <td><?=$it['cantidad']?></td>
              <td><?=htmlspecialchars($it['ubicacion'])?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="/admin/inventario.php?action=edit&id=<?=$it['id']?>">Editar</a>
                <a class="btn btn-sm btn-outline-danger" href="/admin/inventario.php?action=delete&id=<?=$it['id']?>" onclick="return confirm('Eliminar item?')">Eliminar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php
} elseif ($action === 'add' || $action === 'edit') {
  $data = ['id'=>'','nombre'=>'','descripcion'=>'','cantidad'=>1,'ubicacion'=>''];
  if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM inventario WHERE id=?'); $stmt->execute([$id]); $data = $stmt->fetch();
  }
  ?>
  <div class="card p-3">
    <h5><?= $action === 'add' ? 'Agregar Item' : 'Editar Item' ?></h5>
    <form method="post" class="row g-2">
      <input type="hidden" name="id" value="<?=htmlspecialchars($data['id'])?>">
      <div class="col-12"><label class="form-label">Nombre</label><input class="form-control" name="nombre" value="<?=htmlspecialchars($data['nombre'])?>" required></div>
      <div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion"><?=htmlspecialchars($data['descripcion'])?></textarea></div>
      <div class="col-4"><label class="form-label">Cantidad</label><input type="number" class="form-control" name="cantidad" value="<?=htmlspecialchars($data['cantidad'])?>"></div>
      <div class="col-8"><label class="form-label">Ubicación</label><input class="form-control" name="ubicacion" value="<?=htmlspecialchars($data['ubicacion'])?>"></div>
      <div class="col-12 text-end"><button class="btn btn-primary"><?= $action === 'add' ? 'Agregar' : 'Guardar' ?></button></div>
    </form>
  </div>
  <?php
}

require_once __DIR__.'/../includes/footer.php';
