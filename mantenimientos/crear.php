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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $cliente_id = $_POST['cliente_id'] ?: null;
    $operador_id = $_POST['operador_id'] ?: null;
    $categoria = $_POST['categoria'] ?? 'REPORTE DE SERVICIO TECNICO';

    // Equipos seleccionados
    $equipos = $_POST['equipos'] ?? [];
    if(count($equipos) > 7) $errors[] = "Solo puedes seleccionar hasta 7 equipos.";
    if ($titulo === '') $errors[] = 'Título es obligatorio.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            INSERT INTO mantenimientos 
            (titulo, descripcion, fecha, cliente_id, operador_id, categoria, equipo1, equipo2, equipo3, equipo4, equipo5, equipo6, equipo7, estado, digitador_id) 
            VALUES (?,?,?,?,?, ?,?,?,?,?,?,?,?,"pendiente",?)
        ');
        $equipos_pad = array_pad($equipos, 7, null);
        $stmt->execute([
            $titulo, $descripcion, $fecha, $cliente_id, $operador_id, $categoria,
            $equipos_pad[0], $equipos_pad[1], $equipos_pad[2],
            $equipos_pad[3], $equipos_pad[4], $equipos_pad[5], $equipos_pad[6],
            $_SESSION['usuario_id']
        ]);
        header('Location: /mantenimientos/listar.php'); 
        exit;
    }
}

// Datos para selects
$clientes = $pdo->query('SELECT id, cliente, direccion, telefono, responsable FROM clientes ORDER BY cliente')->fetchAll();
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
            <div class="input-group">
                <select name="cliente_id" id="cliente_id" 
                    class="form-select selectpicker" 
                    data-live-search="true" 
                    title="Selecciona un cliente...">
                    <option value="">-- Ninguno --</option>
                    <?php foreach($clientes as $c): ?>
                        <option value="<?=$c['id']?>">
                            <?=htmlspecialchars($c['cliente'].' - '.$c['direccion'].' - '.$c['telefono'])?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoCliente">+ Nuevo</button>
            </div>
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

        <!-- Select de Equipos vacío al inicio -->
        <div class="col-12">
            <label class="form-label">Equipos (máx 7)</label>
            <select name="equipos[]" id="equipos" class="form-select selectpicker" multiple 
                    data-live-search="true" data-max-options="7" title="Selecciona equipos...">
                <option disabled>Selecciona un cliente primero</option>
            </select>
        </div>

        <div class="col-12 text-end">
            <button class="btn btn-primary">Crear</button>
        </div>
    </form>
</div>

<!-- Modal Nuevo Cliente -->
<div class="modal fade" id="modalNuevoCliente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formNuevoCliente" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Agregar Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-2">
        <div class="col-12">
            <label class="form-label">Cliente*</label>
            <input type="text" name="cliente" class="form-control" required>
        </div>
        <div class="col-12">
            <label class="form-label">Dirección*</label>
            <input type="text" name="direccion" class="form-control" required>
        </div>
        <div class="col-6">
            <label class="form-label">Teléfono*</label>
            <input type="text" name="telefono" class="form-control" required>
        </div>
        <div class="col-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Responsable</label>
            <input type="text" name="responsable" class="form-control">
        </div>
        <input type="hidden" name="estatus" value="1">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- jQuery y dependencias -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>

<script>
$(document).ready(function(){
    $('.selectpicker').selectpicker();

    // Guardar cliente vía AJAX
    $('#formNuevoCliente').on('submit', function(e){
        e.preventDefault();
        $.post('/mantenimientos/guardar_cliente.php', $(this).serialize(), function(data){
            if(data.success){
                $('#cliente_id')
                    .append($('<option>', { value: data.id, text: data.text }))
                    .val(data.id)
                    .selectpicker('refresh');
                $('#modalNuevoCliente').modal('hide');
            } else {
                alert(data.error || 'Error al guardar cliente');
            }
        }, 'json');
    });

    $('#cliente_id').off('changed.bs.select');

    $('#cliente_id').on('changed.bs.select', function(){
    let idCliente = $(this).val();
    let selectEquipos = $('select[name="equipos[]"]');
    console.log("cliente cambiado ->", idCliente);

    if(!idCliente) {
        selectEquipos.empty().selectpicker('refresh');
        return;
    }

    $.getJSON('/mantenimientos/equipos_por_cliente.php', { id: idCliente })
        .done(function(data){
            console.log("equipos recibidos:", data);
            selectEquipos.empty();

            if (!data || data.length === 0 || data.error) {
                selectEquipos.append('<option disabled>(Sin equipos registrados)</option>');
            } else {
                $.each(data, function(_, e){
                    selectEquipos.append(
                        $('<option>', {
                            value: e.id_equipo,
                            text: e.Identificador + ' | ' + e.nombre_equipo + ' | ' + e.Categoria + ' | ' + e.Estatus
                        })
                    );
                });
            }

            selectEquipos.selectpicker('refresh');
        })
        .fail(function(xhr, status, error){
            console.error("Error AJAX equipos_por_cliente:", status, error);
        });
});











});
</script>
