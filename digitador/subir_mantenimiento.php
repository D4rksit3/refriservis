<?php
// digitador/subir_mantenimiento.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'digitador') header('Location: /index.php');
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

$ok = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) $error = 'Archivo requerido';
  else {
    $tmp = $_FILES['csv']['tmp_name'];
    if (($h = fopen($tmp,'r')) !== false) {
      $header = fgetcsv($h);
      $map = array_map('mb_strtolower', $header);
      $count = 0;
      while (($row = fgetcsv($h)) !== false) {
        // leer por nombres de columna (si faltan, se usan indices)
        $vals = array_combine($map, $row);
        $titulo = $vals['titulo'] ?? ($row[0] ?? null);
        if (!$titulo) continue;
        $descripcion = $vals['descripcion'] ?? ($row[1] ?? '');
        $fecha = $vals['fecha'] ?? date('Y-m-d');
        // intentar mapear cliente por nombre
        $cliente_nombre = $vals['cliente_nombre'] ?? ($row[3] ?? '');
        $cliente_id = null;
        if ($cliente_nombre) {
          $stmt = $pdo->prepare('SELECT id FROM clientes WHERE nombre = ? LIMIT 1'); $stmt->execute([$cliente_nombre]); $c = $stmt->fetch();
          $cliente_id = $c ? $c['id'] : null;
        }
        $invent_nombre = $vals['inventario_nombre'] ?? ($row[4] ?? '');
        $invent_id = null;
        if ($invent_nombre) {
          $stmt = $pdo->prepare('SELECT id FROM inventario WHERE nombre = ? LIMIT 1'); $stmt->execute([$invent_nombre]); $i = $stmt->fetch();
          $invent_id = $i ? $i['id'] : null;
        }
        $operador_usuario = $vals['operador_usuario'] ?? ($row[5] ?? '');
        $operador_id = null;
        if ($operador_usuario) {
          $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ? LIMIT 1'); $stmt->execute([$operador_usuario]); $op = $stmt->fetch();
          $operador_id = $op ? $op['id'] : null;
        }

        $stmt = $pdo->prepare('INSERT INTO mantenimientos (titulo,descripcion,fecha,cliente_id,inventario_id,estado,digitador_id,operador_id) VALUES (?,?,?,?,?,"pendiente",?,?)');
        $stmt->execute([$titulo,$descripcion,$fecha,$cliente_id,$invent_id,$_SESSION['user_id'],$operador_id]);
        $count++;
      }
      fclose($h);
      $ok = "Importadas $count filas.";
    } else $error = 'Error leyendo CSV';
  }
}
?>
<div class="card p-3">
  <h5>Importar CSV — Digitador</h5>
  <a href="/assets/plantillas/plantilla_mantenimientos.csv" class="btn btn-outline-secondary btn-sm">
  Descargar plantilla CSV
</a>

  <?php if($ok) echo "<div class='alert alert-success small'>$ok</div>"; if($error) echo "<div class='alert alert-danger small'>$error</div>"; ?>

  <form method="post" enctype="multipart/form-data" class="row g-2">
    <div class="col-12"><label class="form-label">Archivo CSV (UTF-8)</label><input type="file" name="csv" class="form-control" accept=".csv" required></div>
    <div class="col-12 small text-muted">
      Cabeceras ejemplo: <code>titulo,descripcion,fecha,cliente_nombre,inventario_nombre,operador_usuario</code>
    </div>
    <div class="col-12 text-end"><button class="btn btn-primary">Importar</button></div>
  </form>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
