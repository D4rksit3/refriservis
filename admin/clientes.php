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
    $cliente = trim($_POST['cliente'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $responsable = trim($_POST['responsable'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $ultima_visita = $_POST['ultima_visita'] ?? null;
    $estatus = isset($_POST['estatus']) ? 'Activo' : 'Inactivo';

    if ($action === 'add') {
        $stmt = $pdo->prepare('INSERT INTO clientes (cliente,direccion,telefono,responsable,email,ultima_visita,estatus) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$cliente,$direccion,$telefono,$responsable,$email,$ultima_visita,$estatus]);
        header('Location: /admin/clientes.php?ok=1'); exit;
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare('UPDATE clientes SET cliente=?,direccion=?,telefono=?,responsable=?,email=?,ultima_visita=?,estatus=? WHERE id=?');
        $stmt->execute([$cliente,$direccion,$telefono,$responsable,$email,$ultima_visita,$estatus,$id]);
        header('Location: /admin/clientes.php?ok=1'); exit;
    } elseif ($action === 'upload') {
        // 📂 Importar CSV
        if (isset($_FILES['archivo']['tmp_name']) && is_uploaded_file($_FILES['archivo']['tmp_name'])) {
            $file = fopen($_FILES['archivo']['tmp_name'], 'r');
            fgetcsv($file); // saltar cabecera
            while (($row = fgetcsv($file, 1000, ',')) !== false) {
                $cliente = $row[0] ?? '';
                $direccion = $row[1] ?? '';
                $telefono = $row[2] ?? '';
                $responsable = $row[3] ?? '';
                $email = $row[4] ?? '';
                $ultima_visita = $row[5] ?? null;
                $estatus = $row[6] ?? 'Activo';
                if ($cliente !== '') {
                    $pdo->prepare("INSERT INTO clientes (cliente,direccion,telefono,responsable,email,ultima_visita,estatus) VALUES (?,?,?,?,?,?,?)")
                        ->execute([$cliente,$direccion,$telefono,$responsable,$email,$ultima_visita,$estatus]);
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
    echo "ID\tCliente\tDireccion\tTelefono\tResponsable\tEmail\tUltima Visita\tEstatus\n";
    $lista = $pdo->query('SELECT * FROM clientes ORDER BY id DESC')->fetchAll();
    foreach ($lista as $c) {
        echo $c['id']."\t".$c['cliente']."\t".$c['direccion']."\t".$c['telefono']."\t".$c['responsable']."\t".$c['email']."\t".$c['ultima_visita']."\t".$c['estatus']."\n";
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
                <thead>
                  <tr>
                    <th>ID</th><th>Cliente</th><th>Dirección</th><th>Teléfono</th>
                    <th>Responsable</th><th>Email</th><th>Última visita</th><th>Estatus</th><th></th>
                  </tr>
                </thead>
                <tbody>
                    <?php foreach($lista as $c): ?>
                        <tr>
                            <td><?=$c['id']?></td>
                            <td><?=htmlspecialchars($c['cliente'])?></td>
                            <td><?=htmlspecialchars($c['direccion'])?></td>
                            <td><?=htmlspecialchars($c['telefono'])?></td>
                            <td><?=htmlspecialchars($c['responsable'])?></td>
                            <td><?=htmlspecialchars($c['email'])?></td>
                            <td><?=htmlspecialchars($c['ultima_visita'])?></td>
                            <td>
                                <?php if ($c['estatus'] === 'Activo'): ?>
                                    <span style="color:green;font-weight:bold;">Activo</span>
                                <?php else: ?>
                                    <span style="color:red;font-weight:bold;">Inactivo</span>
                                <?php endif; ?>
                            </td>
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
    $data = ['id'=>'','cliente'=>'','direccion'=>'','telefono'=>'','responsable'=>'','email'=>'','ultima_visita'=>'','estatus'=>'Activo'];
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
            <div class="col-6"><label class="form-label">Cliente</label><input class="form-control" name="cliente" value="<?=htmlspecialchars($data['cliente'])?>" required></div>
            <div class="col-6"><label class="form-label">Responsable</label><input class="form-control" name="responsable" value="<?=htmlspecialchars($data['responsable'])?>"></div>
            <div class="col-6"><label class="form-label">Teléfono</label><input class="form-control" name="telefono" value="<?=htmlspecialchars($data['telefono'])?>"></div>
            <div class="col-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?=htmlspecialchars($data['email'])?>"></div>
            <div class="col-12"><label class="form-label">Dirección</label><input class="form-control" name="direccion" value="<?=htmlspecialchars($data['direccion'])?>"></div>
            <div class="col-6"><label class="form-label">Última visita</label><input type="date" class="form-control" name="ultima_visita" value="<?=htmlspecialchars($data['ultima_visita'])?>"></div>
            <div class="col-6"><label class="form-label">Estatus</label><br>
                <label class="switch">
                    <input type="checkbox" name="estatus" <?=($data['estatus']==='Activo'?'checked':'')?>>
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="col-12 text-end"><button class="btn btn-primary"><?= $action === 'add' ? 'Crear' : 'Guardar' ?></button></div>
        </form>
    </div>
    <style>
    .switch {
      position: relative; display: inline-block; width: 50px; height: 24px;
    }
    .switch input {display:none;}
    .slider {
      position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
      background-color: #ccc; transition: .4s; border-radius: 34px;
    }
    .slider:before {
      position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
      background-color: white; transition: .4s; border-radius: 50%;
    }
    input:checked + .slider { background-color: #4CAF50; }
    input:checked + .slider:before { transform: translateX(26px); }
    </style>
    <?php
} elseif ($action === 'upload') {
    ?>
    <div class="card p-3">
        <h5>📂 Carga Masiva de Clientes</h5>
        <p>Sube un archivo CSV con las columnas: <b>cliente,direccion,telefono,responsable,email,ultima_visita,estatus</b></p>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="archivo" accept=".csv" required>
            <button class="btn btn-primary mt-2">Cargar</button>
        </form>
    </div>
    <?php
}

require_once __DIR__.'/../includes/footer.php';
