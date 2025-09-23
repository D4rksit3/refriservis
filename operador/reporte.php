<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__.'/../config/db.php';

$mantenimiento_id = $_GET['id'] ?? null;

if(!$mantenimiento_id){
    die("⚠️ ID de mantenimiento no proporcionado");
}

// Traer mantenimiento + cliente + inventario principal
$stmt = $pdo->prepare("
    SELECT m.id, m.titulo, m.fecha,
           c.nombre AS cliente, c.direccion,
           i.nombre AS equipo, i.marca, i.modelo, i.serie, i.gas, i.codigo
    FROM mantenimientos m
    LEFT JOIN clientes c ON m.cliente_id = c.id
    LEFT JOIN inventario i ON m.inventario_id = i.id
    WHERE m.id = ?
");
$stmt->execute([$mantenimiento_id]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$datos) {
    die("❌ Mantenimiento con ID=$mantenimiento_id no encontrado en la base de datos.");
}

// --- Equipos relacionados (si tienes tabla intermedia mantenimientos_inventario) ---
$equipos = [];
try {
    $stmt2 = $pdo->prepare("
        SELECT nombre, marca, modelo, serie, gas, codigo
        FROM inventario
        WHERE id IN (
            SELECT inventario_id FROM mantenimientos_inventario WHERE mantenimiento_id = ?
        )
        LIMIT 7
    ");
    $stmt2->execute([$mantenimiento_id]);
    $equipos = $stmt2->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Si no existe la tabla intermedia, usamos solo el inventario principal
    if ($datos['equipo']) {
        $equipos[] = [
            'nombre' => $datos['equipo'],
            'marca' => $datos['marca'],
            'modelo' => $datos['modelo'],
            'serie' => $datos['serie'],
            'gas' => $datos['gas'],
            'codigo' => $datos['codigo']
        ];
    }
}

// Completar hasta 7 filas vacías
for($i=count($equipos); $i<7; $i++){
    $equipos[] = ['nombre'=>'','marca'=>'','modelo'=>'','serie'=>'','gas'=>'','codigo'=>''];
}

// Parámetros de funcionamiento
$parametros = [
    "Corriente eléctrica nominal (Amperios)" => ['L1','L2','L3'],
    "Tensión eléctrica nominal (Voltios)"    => ['V1','V2','V3'],
    "Presión de descarga (PSI)"              => ['P1','P2','P3'],
    "Presión de succión (PSI)"               => ['S1','S2','S3']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte de Servicio Técnico</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
canvas { border:1px solid #ccc; width:100%; height:200px; }
</style>
</head>
<body class="bg-light">
<div class="container py-4">
<div class="card shadow-sm">
<div class="card-body">
<h3 class="mb-3">Reporte de Servicio Técnico</h3>

<form action="guardar_informe.php" method="post" enctype="multipart/form-data">
<input type="hidden" name="mantenimiento_id" value="<?= $datos['id'] ?>">

<!-- Datos del cliente -->
<div class="mb-3">
<strong>Cliente:</strong> <?= htmlspecialchars($datos['cliente'] ?? '') ?><br>
<strong>Dirección:</strong> <?= htmlspecialchars($datos['direccion'] ?? '') ?><br>
<strong>Fecha:</strong> <?= $datos['fecha'] ?>
</div>

<!-- Equipos -->
<h5>Datos de Identificación de los Equipos a Intervenir</h5>
<div class="table-responsive">
<table class="table table-bordered text-center">
<thead class="table-light">
<tr>
<th>#</th><th>Tipo de Equipo</th><th>Marca</th><th>Modelo</th>
<th>Ubicación/Serie</th><th>Tipo de Gas</th><th>Código</th>
</tr>
</thead>
<tbody>
<?php foreach($equipos as $k=>$eq): ?>
<tr>
<td><?= $k+1 ?></td>
<td><input type="text" name="eq_nombre[]" class="form-control" value="<?= htmlspecialchars($eq['nombre']) ?>"></td>
<td><input type="text" name="eq_marca[]" class="form-control" value="<?= htmlspecialchars($eq['marca']) ?>"></td>
<td><input type="text" name="eq_modelo[]" class="form-control" value="<?= htmlspecialchars($eq['modelo']) ?>"></td>
<td><input type="text" name="eq_serie[]" class="form-control" value="<?= htmlspecialchars($eq['serie']) ?>"></td>
<td><input type="text" name="eq_gas[]" class="form-control" value="<?= htmlspecialchars($eq['gas']) ?>"></td>
<td><input type="text" name="eq_codigo[]" class="form-control" value="<?= htmlspecialchars($eq['codigo']) ?>"></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Trabajos -->
<div class="mb-3">
<label class="form-label">Trabajos Realizados</label>
<textarea name="trabajos" class="form-control" rows="3" required></textarea>
</div>

<!-- Observaciones -->
<div class="mb-3">
<label class="form-label">Observaciones</label>
<textarea name="observaciones" class="form-control" rows="3"></textarea>
</div>

<!-- Parámetros de Funcionamiento -->
<h5>Parámetros de Funcionamiento</h5>
<div class="table-responsive">
<table class="table table-bordered text-center">
<thead class="table-light">
<tr>
<th>Medida</th>
<th>Antes L1</th><th>Después L1</th>
<th>Antes L2</th><th>Después L2</th>
<th>Antes L3</th><th>Después L3</th>
</tr>
</thead>
<tbody>
<?php foreach($parametros as $nombre => $letras): ?>
<tr>
<td><?= $nombre ?></td>
<?php foreach($letras as $letra): ?>
<td><input type="text" name="antes_<?= $letra ?>[]" class="form-control"></td>
<td><input type="text" name="despues_<?= $letra ?>[]" class="form-control"></td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Fotos -->
<div class="mb-3">
<label class="form-label">Subir Fotos</label>
<input type="file" name="fotos[]" multiple accept="image/*" class="form-control">
</div>

<!-- Firmas -->
<h5>Firmas</h5>
<div class="row">
<?php
$firmaCampos = ['Cliente'=>'firmaCliente','Técnico'=>'firmaTecnico','Supervisor'=>'firmaSupervisor'];
foreach($firmaCampos as $label=>$id): ?>
<div class="col-md-4 mb-3">
<label class="form-label">Firma <?= $label ?></label>
<canvas id="<?= $id ?>"></canvas>
<input type="hidden" name="<?= strtolower($label) ?>_firma" id="<?= $id ?>Input">
<button type="button" class="btn btn-sm btn-secondary mt-1" onclick="limpiarFirma('<?= $id ?>')">Limpiar</button>
</div>
<?php endforeach; ?>
</div>

<button type="submit" class="btn btn-success w-100">Guardar Informe</button>
</form>
</div></div></div>

<script>
function initFirma(canvasId, inputId) {
    const canvas = document.getElementById(canvasId);
    const input = document.getElementById(inputId);
    const ctx = canvas.getContext("2d");

    // Escalar canvas para pantallas móviles/retina
    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        ctx.scale(ratio, ratio);
    }
    resizeCanvas();
    window.addEventListener("resize", resizeCanvas);

    let dibujando = false;

    function startDraw(x, y) {
        dibujando = true;
        ctx.beginPath();
        ctx.moveTo(x, y);
    }
    function draw(x, y) {
        if (!dibujando) return;
        ctx.lineWidth = 2;
        ctx.lineCap = "round";
        ctx.strokeStyle = "black";
        ctx.lineTo(x, y);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(x, y);
    }
    function endDraw() {
        if (dibujando) {
            dibujando = false;
            input.value = canvas.toDataURL("image/png");
        }
    }

    // Eventos para mouse
    canvas.addEventListener("mousedown", e => startDraw(e.offsetX, e.offsetY));
    canvas.addEventListener("mousemove", e => draw(e.offsetX, e.offsetY));
    canvas.addEventListener("mouseup", endDraw);
    canvas.addEventListener("mouseout", endDraw);

    // Eventos para touch
    canvas.addEventListener("touchstart", e => {
        const rect = canvas.getBoundingClientRect();
        const t = e.touches[0];
        startDraw(t.clientX - rect.left, t.clientY - rect.top);
    });
    canvas.addEventListener("touchmove", e => {
        const rect = canvas.getBoundingClientRect();
        const t = e.touches[0];
        draw(t.clientX - rect.left, t.clientY - rect.top);
        e.preventDefault();
    });
    canvas.addEventListener("touchend", endDraw);
}

function limpiarFirma(canvasId) {
    const canvas = document.getElementById(canvasId);
    const ctx = canvas.getContext("2d");
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

<?php foreach($firmaCampos as $label=>$id): ?>
initFirma("<?= $id ?>","<?= $id ?>Input");
<?php endforeach; ?>
</script>

</body>
</html>
