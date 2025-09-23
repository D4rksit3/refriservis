<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['admin','digitador'])) {
    header('Location: /index.php'); exit;
}

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $cliente_id = $_POST['cliente_id'] ?: null;
    $inventario_id = $_POST['inventario_id'] ?: null;
    $operador_id = $_POST['operador_id'] ?: null;

    if ($titulo === '') $errors[] = 'Título es obligatorio.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            INSERT INTO mantenimientos 
            (titulo, descripcion, fecha, cliente_id, inventario_id, estado, digitador_id, operador_id) 
            VALUES (?,?,?,?,?,"pendiente",?,?)
        ');
        $stmt->execute([$titulo, $descripcion, $fecha, $cliente_id, $inventario_id, $_SESSION['usuario_id'], $operador_id]);
        header('Location: /mantenimientos/listar.php'); 
        exit;
    }
}

// Datos para selects
$clientes = $pdo->query('SELECT id, nombre, direccion, telefono, responsable, email, ultima_visita, estatus FROM clientes ORDER BY nombre')->fetchAll();
$invent = $pdo->query('SELECT id, nombre, marca, modelo, serie, gas, codigo FROM inventario ORDER BY nombre')->fetchAll();
$operadores = $pdo->query('SELECT id, nombre FROM usuarios WHERE rol="operador"')->fetchAll();
?>
<div class="card p-3">
    <h5>Crear mantenimiento</h5>
    <?php foreach($errors as $e) echo "<div class='alert alert-danger small'>$e</div>"; ?>
    <form method="post" class="row g-2">
        <div class="col-12">
            <label class="form-label">Título</label>
            <input class="form-control" name="titulo" required>
        </div>
        <div class="col-12">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion"></textarea>
        </div>
        <div class="col-4">
            <label class="form-label">Fecha</label>
            <input type="date" class="form-control" name="fecha" value="<?=date('Y-m-d')?>">
        </div>
        <div class="col-4">
            <label class="form-label">Cliente</label>
            <select name="cliente_id" class="form-select">
                <option value="">-- Ninguno --</option>
                <?php foreach($clientes as $c): ?>
                    <option value="<?=$c['id']?>">
                        <?=htmlspecialchars($c['nombre'].' - '.$c['direccion'].' - '.$c['telefono'])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-4">
            <label class="form-label">Inventario</label>
            <select name="inventario_id" class="form-select">
                <option value="">-- Ninguno --</option>
                <?php foreach($invent as $i): ?>
                    <option value="<?=$i['id']?>">
                        <?=htmlspecialchars($i['nombre'].' | '.$i['marca'].' '.$i['modelo'].' | Serie: '.$i['serie'])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6">
            <label class="form-label">Asignar Operador</label>
            <select name="operador_id" class="form-select">
                <option value="">-- Ninguno --</option>
                <?php foreach($operadores as $o): ?>
                    <option value="<?=$o['id']?>"><?=htmlspecialchars($o['nombre'])?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 text-end">
            <button class="btn btn-primary">Crear</button>
        </div>
    </form>
</div>
<?php require_once __DIR__.'/../includes/footer.php'; ?>
