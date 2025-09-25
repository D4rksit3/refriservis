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
    $operador_id = $_POST['operador_id'] ?: null;
    $categoria = $_POST['categoria'] ?? 'REPORTE DE SERVICIO TECNICO';

    // Recoger equipos seleccionados (hasta 7)
    $equipos = $_POST['equipos'] ?? [];
    if(count($equipos) > 7) $errors[] = "Solo puedes seleccionar hasta 7 equipos.";

    if ($titulo === '') $errors[] = 'Título es obligatorio.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            INSERT INTO mantenimientos 
            (titulo, descripcion, fecha, cliente_id, operador_id, categoria, equipo1, equipo2, equipo3, equipo4, equipo5, equipo6, equipo7, estado, digitador_id) 
            VALUES (?,?,?,?,?, ?,?,?,?,?,?,?,?,"pendiente",?)
        ');

        $equipos_pad = array_pad($equipos, 7, null); // rellenar hasta 7
        $stmt->execute([
            $titulo,
            $descripcion,
            $fecha,
            $cliente_id,
            $operador_id,
            $categoria,
            $equipos_pad[0],
            $equipos_pad[1],
            $equipos_pad[2],
            $equipos_pad[3],
            $equipos_pad[4],
            $equipos_pad[5],
            $equipos_pad[6],
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
$categorias = $pdo->query('SELECT nombre FROM categoria ORDER BY nombre')->fetchAll();
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
            <label class="form-label">Operador</label>
            <select name="operador_id" class="form-select">
                <option value="">-- Ninguno --</option>
                <?php foreach($operadores as $o): ?>
                    <option value="<?=$o['id']?>"><?=htmlspecialchars($o['nombre'])?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Categoría</label>
            <select name="categoria" class="form-select">
                <?php foreach($categorias as $cat): ?>
                    <option value="<?=htmlspecialchars($cat['nombre'])?>" <?=($cat['nombre']=='REPORTE DE SERVICIO TECNICO')?'selected':''?>>
                        <?=htmlspecialchars($cat['nombre'])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Equipos (máx 7)</label>
            <select name="equipos[]" class="form-select selectpicker" multiple data-live-search="true" data-max-options="7" title="Selecciona equipos...">
                <?php foreach($equipos as $e): ?>
                    <option value="<?=$e['id_equipo']?>">
                        <?=htmlspecialchars($e['Nombre'].' | '.$e['Categoria'].' | '.$e['Estatus'])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 text-end">
            <button class="btn btn-primary">Crear</button>
        </div>
    </form>
</div>

<!-- jQuery primero -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<!-- Bootstrap CSS y JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Bootstrap Select CSS y JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>

<script>
$(document).ready(function(){
    $('.selectpicker').selectpicker();
});
</script>
