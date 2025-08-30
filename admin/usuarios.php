<?php
// admin/usuarios.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') { header('Location: /index.php'); exit; }
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

$action = $_GET['action'] ?? 'list';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Crear o editar
  $nombre = trim($_POST['nombre'] ?? '');
  $usuario = trim($_POST['usuario'] ?? '');
  $rol = $_POST['rol'] ?? 'digitador';
  $pass = $_POST['password'] ?? '';

  if ($nombre === '' || $usuario === '') $errors[] = 'Nombre y usuario son obligatorios.';
  if ($action === 'add' && $pass === '') $errors[] = 'Contraseña es obligatoria al crear usuario.';

  if (empty($errors)) {
    if ($action === 'add') {
      // crear
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, usuario, password, rol) VALUES (?,?,?,?)');
      $stmt->execute([$nombre, $usuario, $hash, $rol]);
      header('Location: /admin/usuarios.php?ok=creado'); exit;
    } elseif ($action === 'edit') {
      $id = (int)$_POST['id'];
      if ($pass !== '') {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE usuarios SET nombre=?, usuario=?, password=?, rol=? WHERE id=?');
        $stmt->execute([$nombre,$usuario,$hash,$rol,$id]);
      } else {
        $stmt = $pdo->prepare('UPDATE usuarios SET nombre=?, usuario=?, rol=? WHERE id=?');
        $stmt->execute([$nombre,$usuario,$rol,$id]);
      }
      header('Location: /admin/usuarios.php?ok=actualizado'); exit;
    }
  }
}

// Delete por GET seguro (confirmar en front)
if ($action === 'delete' && isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  $pdo->prepare('DELETE FROM usuarios WHERE id=?')->execute([$id]);
  header('Location: /admin/usuarios.php?ok=eliminado'); exit;
}

if ($action === 'list') {
  $usuarios = $pdo->query('SELECT id,nombre,usuario,rol,creado_en FROM usuarios ORDER BY id DESC')->fetchAll();
  ?>
  <div class="card p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5>Usuarios</h5>
      <a class="btn btn-sm btn-primary" href="/admin/usuarios.php?action=add">+ Nuevo Usuario</a>
    </div>

    <?php if(!empty($_GET['ok'])): ?>
      <div class="alert alert-success small">Acción realizada correctamente.</div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table table-sm">
        <thead><tr><th>ID</th><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Creado</th><th></th></tr></thead>
        <tbody>
          <?php foreach($usuarios as $u): ?>
            <tr>
              <td><?=$u['id']?></td>
              <td><?=htmlspecialchars($u['nombre'])?></td>
              <td><?=htmlspecialchars($u['usuario'])?></td>
              <td><?=htmlspecialchars($u['rol'])?></td>
              <td><?=$u['creado_en']?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="/admin/usuarios.php?action=edit&id=<?=$u['id']?>">Editar</a>
                <a class="btn btn-sm btn-outline-danger" href="/admin/usuarios.php?action=delete&id=<?=$u['id']?>" onclick="return confirm('Eliminar usuario?')">Eliminar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php
} elseif ($action === 'add' || $action === 'edit') {
  $data = ['id'=>'','nombre'=>'','usuario'=>'','rol'=>'digitador'];
  if ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $data = $pdo->prepare('SELECT * FROM usuarios WHERE id=?')->execute([$id]) ? $pdo->query("SELECT * FROM usuarios WHERE id={$id}")->fetch() : $pdo->query("SELECT * FROM usuarios WHERE id={$id}")->fetch();
    // fetch by prepared:
    $stmt=$pdo->prepare('SELECT * FROM usuarios WHERE id=?'); $stmt->execute([$id]); $data=$stmt->fetch();
    if (!$data) { echo '<div class="alert alert-danger">Usuario no existe</div>'; require_once __DIR__.'/../includes/footer.php'; exit; }
  }
  ?>
  <div class="card p-3">
    <h5><?= $action === 'add' ? 'Nuevo Usuario' : 'Editar Usuario' ?></h5>
    <?php if($errors): foreach($errors as $err) echo "<div class='alert alert-danger small'>$err</div>"; endforeach; ?>
    <form method="post" class="row g-2">
      <input type="hidden" name="id" value="<?=htmlspecialchars($data['id'])?>">
      <div class="col-12"><label class="form-label">Nombre</label><input class="form-control" name="nombre" value="<?=htmlspecialchars($data['nombre'])?>" required></div>
      <div class="col-12"><label class="form-label">Usuario</label><input class="form-control" name="usuario" value="<?=htmlspecialchars($data['usuario'])?>" required></div>
      <div class="col-12"><label class="form-label">Rol</label>
        <select name="rol" class="form-select">
          <option value="admin" <?=($data['rol']==='admin')?'selected':''?>>Administrador</option>
          <option value="digitador" <?=($data['rol']==='digitador')?'selected':''?>>Digitador</option>
          <option value="operador" <?=($data['rol']==='operador')?'selected':''?>>Operador</option>
        </select>
      </div>
      <div class="col-12"><label class="form-label"><?=$action==='add' ? 'Contraseña' : 'Contraseña (dejar en blanco si no cambia)'?></label><input type="password" class="form-control" name="password" <?= $action==='add' ? 'required' : ''?>></div>
      <div class="col-12 text-end"><button class="btn btn-primary"><?= $action==='add' ? 'Crear' : 'Guardar cambios'?></button></div>
    </form>
  </div>
  <?php
}

require_once __DIR__.'/../includes/footer.php';
