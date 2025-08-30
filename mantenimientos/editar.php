<?php
// mantenimientos/editar.php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: /index.php'); exit; }
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt=$pdo->prepare('SELECT * FROM mantenimientos WHERE id=?'); $stmt->execute([$id]); $m = $stmt->fetch();
if (!$m) { echo '<div class="alert alert-danger">No existe mantenimiento.</div>'; require_once __DIR__.'/../includes/footer.php'; exit; }

$errors=[];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $estado = $_POST['estado'] ?? $m['estado'];
  $operador_id = $_POST['operador_id'] ?: null;
  $observ = $_POST['observ'] ?? null;

  // archivo opcional
  $archivoPath = $m['archivo'];
  if (!empty($_FILES['archivo']['name']) && $_FILES['archivo']['error']===UPLOAD_ERR_OK) {
    $destDir = __DIR__.'/../uploads';
    @mkdir($destDir,0777,true);
    $dest = $destDir.'/m_'.$id.'_'.time().'_'.basename($_FILES['archivo']['name']);
    move_uploaded_file($_FILES['archivo']['tmp_name'],$dest);
    $archivoPath = str_replace(__DIR__.'/../','/',$dest);
  }

  $stmt = $pdo->prepare('UPDATE mantenimientos SET estado=?, operador_id=?, archivo=?, descripcion=COALESCE(?,descripcion) WHERE id=?');
  $stmt->execute([$estado,$operador_id,$archivoPath,$_POST['descripcion'],$id]);

  header('Location: /mantenimientos/listar.php'); exit;
}

// traer operadores para asignar
$operadores = $pdo->query('SELECT id,nombre FROM usuarios WHERE rol="operador"')->fetchAll();
?>
<div class="card p-3">
  <h5>Mantenimiento #<?=$m['id']?> — <?=htmlspecialchars($m['titulo'])?></h5>
  <form method="post" enctype="multipart/form-data" class="row g-2">
    <div class="col-12"><label class="form-label">Descripción</label><textarea name="descripcion" class="form-control"><?=$m['descripcion']?></textarea></div>
    <div class="col-4"><label class="form-label">Fecha</label><input class="form-control" value="<?=$m['fecha']?>" readonly></div>
    <div class="col-4"><label class="form-label">Estado</label>
      <select name="estado" class="form-select">
        <option value="pendiente" <?=($m['estado']==='pendiente')?'selected':''?>>Pendiente</option>
        <option value="en proceso" <?=($m['estado']==='en proceso')?'selected':''?>>En proceso</option>
        <option value="finalizado" <?=($m['estado']==='finalizado')?'selected':''?>>Finalizado</option>
      </select>
    </div>
    <div class="col-4"><label class="form-label">Asignar Operador</label>
      <select name="operador_id" class="form-select"><option value="">-- Ninguno --</option><?php foreach($operadores as $o): ?><option value="<?=$o['id']?>" <?=($m['operador_id']==$o['id'])?'selected':''?>><?=htmlspecialchars($o['nombre'])?></option><?php endforeach; ?></select>
    </div>

    <div class="col-12"><label class="form-label">Adjuntar archivo (informe)</label><input type="file" name="archivo" class="form-control"></div>
    <?php if(!empty($m['archivo'])): ?>
      <div class="col-12"><a class="btn btn-outline-secondary btn-sm" href="<?=$m['archivo']?>" target="_blank">Ver archivo adjunto</a></div>
    <?php endif; ?>

    <div class="col-12 text-end"><button class="btn btn-primary">Guardar cambios</button></div>
  </form>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
