<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header('Location: /index.php');
    exit;
}
require_once __DIR__.'/../includes/header.php';
require_once __DIR__.'/../config/db.php';

// Obtener lista de técnicos
$tecnicos = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol='operador'")->fetchAll();
?>

<div class="card p-3 mb-3">
    <h5>Panel Operador</h5>
    <p class="small text-muted">Tareas asignadas y estado</p>
    <a class="btn btn-primary mb-2" href="/operador/mis_mantenimientos.php">Ver mis tareas</a>

    <hr>
    <h6>Filtros</h6>
    <div class="row mb-3">
        <div class="col-md-3">
            <label>Periodo</label>
            <select id="filtro_periodo" class="form-control">
                <option value="dia">Hoy</option>
                <option value="semana" selected>Esta semana</option>
                <option value="mes">Este mes</option>
            </select>
        </div>
        <div class="col-md-3">
            <label>Técnico</label>
            <select id="filtro_tecnico" class="form-control">
                <option value="todos">Todos</option>
                <?php foreach($tecnicos as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <canvas id="grafico_estado"></canvas>
        </div>
        <div class="col-md-6">
            <canvas id="grafico_tecnico"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctxEstado = document.getElementById('grafico_estado').getContext('2d');
const ctxTecnico = document.getElementById('grafico_tecnico').getContext('2d');

let chartEstado, chartTecnico;

function cargarDatos() {
    const periodo = document.getElementById('filtro_periodo').value;
    const tecnico = document.getElementById('filtro_tecnico').value;

    fetch(`/operador/dashboard_data.php?periodo=${periodo}&tecnico=${tecnico}`)
    .then(res => res.json())
    .then(data => {
        // Gráfico de estados
        if(chartEstado) chartEstado.destroy();
        chartEstado = new Chart(ctxEstado, {
            type: 'bar',
            data: {
                labels: ['Pendientes','Finalizados'],
                datasets: [{
                    label: 'Cantidad',
                    data: [data.pendientes, data.finalizados],
                    backgroundColor: ['#ffc107','#28a745']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } }
            }
        });

        // Gráfico de técnicos (torta)
        if(chartTecnico) chartTecnico.destroy();
        chartTecnico = new Chart(ctxTecnico, {
            type: 'pie',
            data: {
                labels: data.tecnicos.map(t => t.nombre),
                datasets: [{
                    data: data.tecnicos.map(t => t.total),
                    backgroundColor: data.tecnicos.map(() => `hsl(${Math.random()*360},70%,60%)`)
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    });
}

// Escuchar cambios de filtros
document.getElementById('filtro_periodo').addEventListener('change', cargarDatos);
document.getElementById('filtro_tecnico').addEventListener('change', cargarDatos);

// Cargar al iniciar
cargarDatos();
</script>

<?php require_once __DIR__.'/../includes/footer.php'; ?>
