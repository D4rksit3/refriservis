<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['admin','digitador'])) {
    header('Location: /index.php'); 
    exit;
}

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $cliente_id = $_POST['cliente_id'] ?: null;
    $operador_id = $_POST['operador_id'] ?: null;
    $equipos = $_POST['equipos'] ?? []; // array de equipos seleccionados

    if ($titulo === '') $errors[] = 'Título es obligatorio.';
    if (count($equipos) > 7) $errors[] = 'Solo puede seleccionar hasta 7 equipos.';

    if (empty($errors)) {
        // Prepara query con los 7 equipos
        $stmt = $pdo->prepare('
            INSERT INTO mantenimientos 
            (titulo, descripcion, fecha, cliente_id, operador_id, equipo1, equipo2, equipo3, equipo4, equipo5, equipo6, equipo7, estado, digitador_id) 
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,"pendiente",?)
        ');

        // Rellena los equipos vacíos con null si seleccionó menos de 7
        $equipos_insert = array_pad($equipos, 7, null);

        $stmt->execute([
            $titulo,
            $descripcion,
            $fecha,
            $cliente_id,
            $operador_id,
            $equipos_insert[0],
            $equipos_insert[1],
            $equipos_insert[2],
            $equipos_insert[3],
            $equipos_insert[4],
            $equipos_insert[5],
            $equipos_insert[6],
            $_SESSION['usuario_id']
        ]);

        header('Location: /mantenimientos/listar.php'); 
        exit;
    }
}

// Datos para selects
$clientes = $pdo->query('SELECT id, cliente, direccion, telefono, responsable, email, ultima_visita, estatus FROM clientes ORDER BY cliente')->fetchAll();
$equipos = $pdo->query('SELECT id_equipo, Nombre, Categoria, Estatus FROM equipos ORDER BY Nombre')->fetchAll();
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
                        <?=htmlspecialchars($c['cliente'].' - '.$c['direccion'].' - '.$c['telefono'])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-4">
            <label class="form-label">Asignar Operador</label>
            <select name="operador_id" class="form-select">
                <option value="">-- Ninguno --</option>
                <?php foreach($operadores as $o): ?>
                    <option value="<?=$o['id']?>"><?=htmlspecialchars($o['nombre'])?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Select múltiple de equipos con Select2 -->
        <div class="col-12">
            <label class="form-label">Seleccionar hasta 7 equipos</label>
            <select name="equipos[]" class="form-select" multiple="multiple" id="select-equipos">
                <?php foreach($equipos as $eq): ?>
                    <option value="<?=$eq['id_equipo']?>">
                        <?=htmlspecialchars($eq['Nombre'].' | '.$eq['Categoria'].' | '.$eq['Estatus'])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 text-end">
            <button class="btn btn-primary">Crear</button>
        </div>
    </form>
</div>

<!-- Select2 CSS y JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#select-equipos').select2({
        placeholder: "-- Seleccione equipos --",
        maximumSelectionLength: 7,
        width: '100%'
    });
});
</script>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
