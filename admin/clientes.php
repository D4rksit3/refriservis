<?php
// admin/clientes.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header('Location: /index.php'); exit;
}

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

$action = $_GET['action'] ?? 'list';

// ========================
// 📌 Procesar formularios
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $correo = trim($_POST['correo'] ?? '');

    if ($action === 'add') {
        $stmt = $pdo->prepare('INSERT INTO clientes (nombre,telefono,direccion,correo) VALUES (?,?,?,?)');
        $stmt->execute([$nombre,$telefono,$direccion,$correo]);
        header('Location: /admin/clientes.php?ok=1'); exit;
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare('UPDATE clientes SET nombre=?,telefono=?,direccion=?,correo=? WHERE id=?');
        $stmt->execute([$nombre,$telefono,$direccion,$correo,$id]);
        header('Location: /admin/clientes.php?ok=1'); exit;
    } elseif ($action === 'upload') {
        // 📂 Importar CSV de clientes
        if (isset($_FILES['archivo']['tmp_name']) && is_uploaded_file($_FILES['archivo']['tmp_name'])) {
            $file = fopen($_FILES['archivo']['tmp_name'], 'r');
            fgetcsv($file); // saltar cabecera
            while (($row = fgetcsv($file, 1000, ',')) !== false) {
                $nombre = $row[0] ?? '';
                $telefono = $row[1] ?? '';
                $direccion = $row[2] ?? '';
                $correo = $row[3] ?? '';
                if ($nombre !== '') {
                    $pdo->prepare("INSERT INTO clientes (nombre,telefono,direccion,correo) VALUES (?,?,?,?)")
                        ->execute([$nombre,$telefono,$direccion,$correo]);
                }
            }
            fclose($file);
        }
        header('Location: /admin/clientes.php?ok=1'); exit;
    }
}

// ========================
// 📌 Eliminar cliente
// ========================
if ($action === 'delete' && isset($_GET['id'])) {
    $pdo->prepare('DELETE FROM clientes WHERE id=?')->execute([(int)$_GET['id']]);
    header('Location: /admin/clientes.php?ok=1'); exit;
}

// ========================
// 📌 Descargar en Excel
// ========================
if ($action === 'download') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=clientes.xls");
    echo "ID\tNombre\tTelefono\tDireccion\tCorreo\n";
    $lista = $pdo->query('SELECT * FROM clientes ORDER BY id DESC')->fetchAll();
    foreach ($lista as $c) {
        echo $c['id']."\t".$c['nombre']."\t".$c['telefono']."\t".$c['direccion']."\t".$c['correo']."\n";
    }
    exit;
}

// ========================
// 📌 Vistas
// ========================
if ($action === 'list') {
    $lista = $pdo->query('SELECT * FROM clientes ORDER BY id DESC')->fetchAll();
    ?>
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5>👥 Clientes</h5>
            <div>
                <a class="btn btn-success btn-sm" href="/admin/clientes.php?action=download">⬇ Descargar Excel</a>
                <a class="btn btn-secondary btn-sm" href="/admin/clientes.php?action=upload">⬆ Carga Masiva</a>
                <a class="btn btn-primary btn-sm" href="/admin/clientes.php?action=add">+ Nuevo Cliente</a>
            </div>
        </div>
        <div class="table-responsive mt-3">
            <table class="table table-sm table-striped">
                <thead><tr><th>ID</th><th>Nombre</th><th>Teléfono</th><th>Correo</th><th>Dirección</th><th></th></tr></thead>
                <tbody>
                    <?php foreach($lista as $c): ?>
                        <tr>
                            <td><?=$c['id']?></td>
                            <td><?=htmlspecialchars($c['nombre'])?></td>
                            <td><?=htmlspecialchars($c['telefono'])?></td>
                            <td><?=htmlspecialchars($c['correo'])?></td>
                            <td><?=htmlspecialchars($c['direccion'])?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="/admin/clientes.php?action=edit&id=<?=$c['id']?>">✏ Editar</a>
                                <a class="btn btn-sm btn-outline-danger" href="/admin/clientes.php?action=delete&id=<?=$c['id']?>" onclick="return confirm('Eliminar cliente?')">🗑 Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
} elseif ($action === 'add' || $action === 'edit') {
    $data = ['id'=>'','nombre'=>'','telefono'=>'','direccion'=>'','correo'=>''];
    if ($action === 'edit') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM clientes WHERE id=?');
        $stmt->execute([$id]);
        $data = $stmt->fetch();
    }
    ?>
    <div class="card p-3">
        <h5><?= $action === 'add' ? '➕ Nuevo Cliente' : '✏ Editar Cliente' ?></h5>
        <form method="post" class="row g-2">
            <input type="hidden" name="id" value="<?=htmlspecialchars($data['id'])?>">
            <div class="col-12"><label class="form-label">Nombre</label><input class="form-control" name="nombre" value="<?=htmlspecialchars($data['nombre'])?>" required></div>
            <div class="col-6"><label class="form-label">Teléfono</label><input class="form-control" name="telefono" value="<?=htmlspecialchars($data['telefono'])?>"></div>
            <div class="col-6"><label class="form-label">Correo</label><input class="form-control" name="correo" value="<?=htmlspecialchars($data['correo'])?>"></div>
            <div class="col-12"><label class="form-label">Dirección</label><input class="form-control" name="direccion" value="<?=htmlspecialchars($data['direccion'])?>"></div>
            <div class="col-12 text-end"><button class="btn btn-primary"><?= $action === 'add' ? 'Crear' : 'Guardar' ?></button></div>
        </form>
    </div>
    <?php
} elseif ($action === 'upload') {
    ?>
    <div class="card p-3">
        <h5>📂 Carga Masiva de Clientes</h5>
        <p>Sube un archivo CSV con las columnas: <b>nombre, telefono, direccion, correo</b></p>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="archivo" accept=".csv" required>
            <button class="btn btn-primary mt-2">Cargar</button>
        </form>
    </div>
    <?php
}

require_once __DIR__.'/../includes/footer.php';
