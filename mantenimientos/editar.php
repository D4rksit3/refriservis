<?php
// mantenimientos/editar.php
session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['admin','digitador','operador'])) {
    header('Location: /index.php'); exit;
}
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Mantenimiento no encontrado.");

// Traer mantenimiento
$stmt = $pdo->prepare("SELECT * FROM mantenimientos WHERE id = ?");
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m) die("Mantenimiento no encontrado.");

// Traer datos para selects
$clientes = $pdo->query("SELECT id, cliente FROM clientes ORDER BY cliente")->fetchAll();
$equipos = $pdo->query("SELECT id_equipo, Nombre FROM equipos ORDER BY Nombre")->fetchAll();
$operadores = $pdo->query('SELECT id, nombre FROM usuarios WHERE rol="operador"')->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $cliente_id = $_POST['cliente_id'] ?: null;
    $operador_id = $_POST['operador_id'] ?: null;
    $equiposSel = $_POST['equipos'] ?? [];

    if ($titulo === '') $errors[] = 'Título es obligatorio.';

    if (empty($errors)) {
        // Guardar hasta 7 equipos
        $equipCols = [];
        for ($i=0;$i<7;$i++) $equipCols[$i] = $equiposSel[$i] ?? null;

        $stmt = $pdo->prepare("
            UPDATE mantenimientos SET 
            titulo=?, descripcion=?, fecha=?, cliente_id=?, operador_id=?, 
            equipo1=?, equipo2=?, equipo3=?, equipo4=?, equipo5=?, equipo6=?, equipo7=?
            WHERE id=?
        ");
        $stmt->execute([
            $titulo, $descripcion, $fecha, $cliente_id, $operador_id,
            $equipCols[0], $equipCols[1], $equipCols[2], $equipCols[3], $equipCols[4], $equipCols[5], $equipCols[6],
            $id
        ]);
        echo "<div class='alert alert-success'>Mantenimiento actualizado.</div>";
        $stmt = $pdo->prepare("SELECT * FROM mantenimientos WHERE id = ?");
        $stmt->execute([$id]);
        $m = $stmt->fetch();
    }
}
?>

<div class="card p-3">
    <h5>Editar Mantenimiento #<?=$m['id']?></h5>
    <?php foreach($errors as $e) echo "<div class='alert alert-danger small'>$e</div>"; ?>
    <form method="post" class="row g-2">
        <div class="col-12">
            <label class="form-label">Título</label>
            <input class="form-control" name="titulo" value="<?=htmlspecialchars($m['titulo'])?>" required>
        </div>
        <div class="col-12">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion"><?=htmlspecialchars($m['descripcion'])?></textarea>
        </div>
        <div class="col-4">
            <label class="form-label">Fecha</label>
            <input type="date" class="form-control" name="fecha" value="<?=$m['fecha']?>">
        </div>
        <div class="col-4">
            <label class="form-label">Cliente</label>
            <select name="cliente_id" class="form-select">
                <option value="">-- Ninguno --</option>
                <?php foreach($clientes as $c): ?>
                    <option value="<?=$c['id']?>" <?=$m['cliente_id']==$c['id']?'selected':''?>><?=htmlspecialchars($c['cliente'])?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-4">
            <label class="form-label">Asignar Operador</label>
            <select name="operador_id" class="form-select">
                <option value="">-- Ninguno --</option>
                <?php foreach($operadores as $o): ?>
                    <option value="<?=$o['id']?>" <?=$m['operador_id']==$o['id']?'selected':''?>><?=htmlspecialchars($o['nombre'])?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Equipos (máx. 7)</label>
            <select name="equipos[]" class="form-select" multiple>
                <?php 
                for ($i=1;$i<=7;$i++) $selectedEquip[$i-1] = $m['equipo'.$i];
                foreach($equipos as $e): ?>
                    <option value="<?=$e['id_equipo']?>" <?=in_array($e['id_equipo'],$selectedEquip)?'selected':''?>><?=htmlspecialchars($e['Nombre'])?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 text-end">
            <button class="btn btn-primary">Guardar</button>
        </div>
    </form>
</div>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
