<?php
// ===============================
// CONFIGURACIÓN INICIAL
// ===============================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
// Simulación de sesión activa para pruebas
// Elimínalo cuando ya tengas login real
if (!isset($_SESSION['usuario'])) {
    $_SESSION['usuario'] = "admin_demo";
    $_SESSION['rol'] = "admin";
}

// Validación de roles
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'], ['admin','digitador','operador'])) {
    header('Location: /index.php');
    exit;
}

// ===============================
// CONEXIÓN A BASE DE DATOS
// ===============================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "refriservis";  // cambia según tu base
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    die("Error al conectar a la BD: " . $e->getMessage());
}

// ===============================
// MAPA DE REPORTES
// ===============================
$mapaDescargas = [
    'VRV'      => '/operador/reportes/guardar_reporte_bombas.php',
    'VEX'      => '/operador/reportes/guardar_reporte_ventilacion.php',
    'UMA'      => '/operador/reportes/guardar_reporte_uma.php',
    'Split'    => '/operador/reportes/guardar_reporte_split.php',
    'Chiller'  => '/operador/reportes/guardar_reporte_chiller.php',
    'Default'  => '/operador/reportes/guardar_reporte_servicio.php'
];

// ===============================
// MODO AJAX: devolver JSON
// ===============================
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $stmt = $pdo->query("SELECT m.*, 
                                c.nombre AS cliente, 
                                u1.usuario AS digitador, 
                                u2.usuario AS operador
                           FROM mantenimientos m
                      LEFT JOIN clientes c ON m.cliente_id=c.id
                      LEFT JOIN usuarios u1 ON m.digitador_id=u1.id
                      LEFT JOIN usuarios u2 ON m.operador_id=u2.id
                       ORDER BY m.fecha DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($rows as $r) {
        // Equipos relacionados
        $stmtEq = $pdo->prepare("SELECT e.nombre 
                                   FROM mantenimiento_equipo me
                                   JOIN equipos e ON me.equipo_id = e.id
                                  WHERE me.mantenimiento_id = ?");
        $stmtEq->execute([$r['id']]);
        $equipos = $stmtEq->fetchAll(PDO::FETCH_COLUMN);

        // Color según estado
        $estado_color = match($r['estado']) {
            'pendiente'   => 'secondary',
            'en_proceso'  => 'warning',
            'finalizado'  => 'success',
            default       => 'secondary'
        };

        // Seleccionar reporte según categoría
        $categoria = $r['categoria'] ?? 'Default';
        $urlDescarga = $mapaDescargas[$categoria] ?? $mapaDescargas['Default'];

        $result[] = [
            'id'          => $r['id'],
            'titulo'      => htmlspecialchars($r['titulo']),
            'fecha'       => $r['fecha'],
            'cliente'     => htmlspecialchars($r['cliente'] ?? ''),
            'equipos'     => htmlspecialchars(implode(', ', $equipos)),
            'estado'      => htmlspecialchars($r['estado']),
            'estado_color'=> $estado_color,
            'digitador'   => htmlspecialchars($r['digitador'] ?? ''),
            'operador'    => htmlspecialchars($r['operador'] ?? ''),
            'url_reporte' => $urlDescarga
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?>

<!-- ===============================
     FRONTEND HTML + BOOTSTRAP
=============================== -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantenimientos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <h2 class="mb-3 text-center">📋 Lista de Mantenimientos</h2>

    <div class="card shadow">
        <div class="card-body">
            <table class="table table-bordered table-striped align-middle text-center" id="tabla-mantenimientos">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Equipos</th>
                        <th>Estado</th>
                        <th>Digitador</th>
                        <th>Operador</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="9" class="text-muted">Cargando datos...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// ===============================
// FUNCIONES JS
// ===============================
async function cargarMantenimientos() {
    const resp = await fetch('?ajax=1');
    const data = await resp.json();
    const tbody = document.querySelector('#tabla-mantenimientos tbody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9" class="text-muted">No hay mantenimientos registrados</td></tr>`;
        return;
    }

    data.forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${r.id}</td>
            <td>${r.titulo}</td>
            <td>${r.fecha}</td>
            <td>${r.cliente}</td>
            <td>${r.equipos}</td>
            <td><span class="badge bg-${r.estado_color}">${r.estado}</span></td>
            <td>${r.digitador}</td>
            <td>${r.operador}</td>
            <td>
                ${r.estado === 'finalizado'
                    ? `<button class="btn btn-sm btn-outline-success btn-reporte" 
                               data-id="${r.id}" data-url="${r.url_reporte}">
                           Descargar Reporte
                       </button>`
                    : '<span class="text-muted">No disponible</span>'
                }
            </td>
        `;
        tbody.appendChild(tr);
    });

    // Delegación de evento para botones
    document.querySelectorAll('.btn-reporte').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const url = btn.dataset.url;
            window.open(`${url}?id=${id}`, '_blank');
        });
    });
}

// Inicializar carga
cargarMantenimientos();
</script>
</body>
</html>
