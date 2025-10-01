<?php
// admin/clientes.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') { 
    header('Location: /index.php'); 
    exit; 
}

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/header.php';

$action = $_GET['action'] ?? 'list';

// =========================
// PROCESAR FORMULARIO
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente       = trim($_POST['cliente'] ?? '');
    $direccion     = trim($_POST['direccion'] ?? '');
    $telefono      = trim($_POST['telefono'] ?? '');
    $responsable   = trim($_POST['responsable'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $ultima_visita = !empty($_POST['ultima_visita']) ? $_POST['ultima_visita'] : null;
    $estatus       = isset($_POST['estatus']) && $_POST['estatus'] == "1" ? 1 : 0;

    if ($action === 'add') {
        $stmt = $pdo->prepare("
            INSERT INTO clientes (cliente,direccion,telefono,responsable,email,ultima_visita,estatus)
            VALUES (?,?,?,?,?,?,?)
        ");
        $stmt->execute([$cliente,$direccion,$telefono,$responsable,$email,$ultima_visita,$estatus]);
        header('Location: /admin/clientes.php?ok=1'); exit;
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("
            UPDATE clientes 
            SET cliente=?, direccion=?, telefono=?, responsable=?, email=?, ultima_visita=?, estatus=? 
            WHERE id=?
        ");
        $stmt->execute([$cliente,$direccion,$telefono,$responsable,$email,$ultima_visita,$estatus,$id]);
        header('Location: /admin/clientes.php?ok=1'); exit;
    }
}

// =========================
// ELIMINAR CLIENTE
// =========================
if ($action === 'delete' && isset($_GET['id'])) {
    $pdo->prepare('DELETE FROM clientes WHERE id=?')->execute([(int)$_GET['id']]);
    header('Location: /admin/clientes.php'); exit;
}

// =========================
// LISTADO CON PAGINACIÓN
// =========================
if ($action === 'list') {
    $limit = 10; // clientes por página
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    // Total de registros
    $total = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    $totalPages = ceil($total / $limit);

    // Obtener registros
    $stmt = $pdo->prepare("SELECT * FROM clientes ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $lista = $stmt->fetchAll();
    ?>
    <div class="card p-3">
        <div class="d-flex justify-content-between">
            <h5>Clientes</h5>
            <a class="btn btn-primary btn-sm" href="/admin/clientes.php?action=add">+ Nuevo Cliente</a>
        </div>
        <div class="table-responsive mt-3">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th>Responsable</th>
                        <th>Email</th>
                        <th>Última Visita</th>
                        <th>Estatus</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($lista): ?>
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
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-status" type="checkbox" 
                                        data-id="<?=$c['id']?>" <?= $c['estatus'] ? 'checked' : '' ?>>
                                </div>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="/admin/clientes.php?action=edit&id=<?=$c['id']?>">Editar</a>
                                <a class="btn btn-sm btn-outline-danger" href="/admin/clientes.php?action=delete&id=<?=$c['id']?>" onclick="return confirm('Eliminar cliente?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center">No hay clientes registrados</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINACIÓN -->
        <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?=($page <= 1 ? 'disabled' : '')?>">
                    <a class="page-link" href="?action=list&page=<?=($page-1)?>">Anterior</a>
                </li>
                <?php for ($i=1; $i<=$totalPages; $i++): ?>
                    <li class="page-item <?=($i == $page ? 'active' : '')?>">
                        <a class="page-link" href="?action=list&page=<?=$i?>"><?=$i?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?=($page >= $totalPages ? 'disabled' : '')?>">
                    <a class="page-link" href="?action=list&page=<?=($page+1)?>">Siguiente</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <script>
    document.querySelectorAll('.toggle-status').forEach(chk => {
        chk.addEventListener('change', function() {
            fetch('/admin/toggle_cliente.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id='+this.dataset.id+'&estatus='+(this.checked ? 1 : 0)
            });
        });
    });
    </script>
    <?php
}

// =========================
// FORMULARIO ADD / EDIT
// =========================
elseif ($action === 'add' || $action === 'edit') {
    $data = ['id'=>'','cliente'=>'','direccion'=>'','telefono'=>'','responsable'=>'','email'=>'','ultima_visita'=>'','estatus'=>1];
    if ($action === 'edit') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM clientes WHERE id=?'); 
        $stmt->execute([$id]); 
        $data = $stmt->fetch();
    }
    ?>
    <div class="card p-3">
        <h5><?= $action === 'add' ? 'Nuevo Cliente' : 'Editar Cliente' ?></h5>
        <form method="post" class="row g-2">
            <input type="hidden" name="id" value="<?=htmlspecialchars($data['id'])?>">
            <div class="col-12"><label class="form-label">Cliente</label>
                <input class="form-control" name="cliente" value="<?=htmlspecialchars($data['cliente'])?>" required>
            </div>
            <div class="col-12"><label class="form-label">Dirección</label>
                <input class="form-control" name="direccion" value="<?=htmlspecialchars($data['direccion'])?>">
            </div>
            <div class="col-6"><label class="form-label">Teléfono</label>
                <input class="form-control" name="telefono" value="<?=htmlspecialchars($data['telefono'])?>">
            </div>
            <div class="col-6"><label class="form-label">Responsable</label>
                <input class="form-control" name="responsable" value="<?=htmlspecialchars($data['responsable'])?>">
            </div>
            <div class="col-6"><label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" value="<?=htmlspecialchars($data['email'])?>">
            </div>
            <div class="col-6"><label class="form-label">Última visita</label>
                <input type="date" class="form-control" name="ultima_visita" value="<?=htmlspecialchars($data['ultima_visita'])?>">
            </div>
            <div class="col-12">
                <label class="form-label">Estatus</label><br>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="estatus" value="1" <?= $data['estatus'] ? 'checked' : '' ?>>
                </div>
            </div>
            <div class="col-12 text-end">
                <button class="btn btn-primary"><?= $action === 'add' ? 'Crear' : 'Guardar' ?></button>
            </div>
        </form>
    </div>
    <?php
}

require_once __DIR__.'/../includes/footer.php';
