<?php
// admin/index.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
  header('Location: /index.php'); exit;
}
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

// contar elementos
$cuentas = [
  'usuarios' => $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(),
  'clientes' => $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn(),
  'equipos' => $pdo->query('SELECT COUNT(*) FROM equipos')->fetchColumn(),
  'mantenimientos' => $pdo->query('SELECT COUNT(*) FROM mantenimientos')->fetchColumn(),
];
?>
<div class="row g-3">
  <div class="col-12">
    <div class="card p-3">
      <h5>Panel Administrativo</h5>
      <p class="text-muted small">Bienvenido, <?=htmlspecialchars($_SESSION['nombre'])?></p>
      <div class="row">
        <?php foreach($cuentas as $k=>$v): ?>
          <div class="col-6 col-md-3">
            <div class="p-3 border rounded">
              <div class="small text-muted"><?=ucfirst($k)?></div>
              <div class="h4"><?=$v?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
<?php
// Obtener lista de técnicos
$tecnicos = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol='operador'")->fetchAll();
?>

<div class="container py-3">
    <h4>Panel Operador - Control Total</h4>

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

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card p-3">
                <h6>Estado de Mantenimientos</h6>
                <canvas id="grafico_estado"></canvas>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-3">
                <h6>Distribución por Técnico (Finalizados)</h6>
                <canvas id="grafico_tecnico"></canvas>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card p-3">
                <h6>Evolución de Mantenimientos por Día</h6>
                <canvas id="grafico_linea"></canvas>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-3">
                <h6>Estado por Categoría</h6>
                <canvas id="grafico_categoria"></canvas>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-3">
                <h6>Top Clientes por Mantenimientos</h6>
                <canvas id="grafico_clientes"></canvas>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card p-3">
                <h6>Mantenimientos vs Reportes Generados</h6>
                <canvas id="grafico_reportes"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chartEstado, chartTecnico, chartLinea, chartCategoria, chartClientes, chartReportes;

function cargarDatos() {
    const periodo = document.getElementById('filtro_periodo').value;
    const tecnico = document.getElementById('filtro_tecnico').value;

    fetch(`/admin/dashboard_data.php?periodo=${periodo}&tecnico=${tecnico}`)
        .then(res => res.json())
        .then(data => {
            // 1️⃣ Estado de mantenimientos (barras)
            if(chartEstado) chartEstado.destroy();
            chartEstado = new Chart(document.getElementById('grafico_estado'), {
                type: 'bar',
                data: {
                    labels: ['Pendientes','En proceso','Finalizados'],
                    datasets: [{
                        label: 'Cantidad',
                        data: [data.estado.pendiente, data.estado.en_proceso, data.estado.finalizado],
                        backgroundColor: ['#ffc107','#17a2b8','#28a745']
                    }]
                },
                options: { responsive:true, plugins:{legend:{display:false}} }
            });

            // 2️⃣ Distribución por técnico (torta)
            if(chartTecnico) chartTecnico.destroy();
            chartTecnico = new Chart(document.getElementById('grafico_tecnico'), {
                type: 'pie',
                data: {
                    labels: data.tecnicos.map(t=>t.nombre),
                    datasets: [{
                        data: data.tecnicos.map(t=>t.total),
                        backgroundColor: data.tecnicos.map(()=>`hsl(${Math.random()*360},70%,60%)`)
                    }]
                },
                options:{ responsive:true, plugins:{legend:{position:'bottom'}} }
            });

            // 3️⃣ Evolución por día (línea)
            if(chartLinea) chartLinea.destroy();
            chartLinea = new Chart(document.getElementById('grafico_linea'), {
                type: 'line',
                data: {
                    labels: data.linea.fechas,
                    datasets:[
                        { label:'Creados', data:data.linea.creados, borderColor:'#007bff', fill:false, tension:0.2 },
                        { label:'Finalizados', data:data.linea.finalizados, borderColor:'#28a745', fill:false, tension:0.2 }
                    ]
                },
                options:{ responsive:true, plugins:{legend:{position:'bottom'}} }
            });

            // 4️⃣ Estado por categoría (barras apiladas)
            if(chartCategoria) chartCategoria.destroy();
            chartCategoria = new Chart(document.getElementById('grafico_categoria'), {
                type: 'bar',
                data: {
                    labels: data.categoria.labels,
                    datasets:[
                        { label:'Pendiente', data:data.categoria.pendiente, backgroundColor:'#ffc107' },
                        { label:'En proceso', data:data.categoria.en_proceso, backgroundColor:'#17a2b8' },
                        { label:'Finalizado', data:data.categoria.finalizado, backgroundColor:'#28a745' }
                    ]
                },
                options:{ responsive:true, plugins:{legend:{position:'bottom'}}, scales:{x:{stacked:true},y:{stacked:true}} }
            });

            // 5️⃣ Top clientes
            if(chartClientes) chartClientes.destroy();
            chartClientes = new Chart(document.getElementById('grafico_clientes'), {
                type: 'bar',
                data:{
                    labels:data.clientes.labels,
                    datasets:[{ label:'Mantenimientos', data:data.clientes.valores, backgroundColor:'#17a2b8' }]
                },
                options:{ responsive:true, plugins:{legend:{display:false}} }
            });

            // 6️⃣ Mantenimientos vs Reportes
            if(chartReportes) chartReportes.destroy();
            chartReportes = new Chart(document.getElementById('grafico_reportes'), {
                type: 'bar',
                data:{
                    labels:['Mantenimientos','Reportes generados'],
                    datasets:[{ label:'Cantidad', data:[data.reportes.mantenimientos,data.reportes.generados], backgroundColor:['#007bff','#28a745']}]
                },
                options:{ responsive:true, plugins:{legend:{display:false}} }
            });
        });
}

document.getElementById('filtro_periodo').addEventListener('change', cargarDatos);
document.getElementById('filtro_tecnico').addEventListener('change', cargarDatos);

// Inicializar
cargarDatos();
</script>

<?php require_once __DIR__.'/../includes/footer.php'; ?>

