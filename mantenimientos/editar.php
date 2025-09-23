<?php
// mantenimientos/editar.php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'operador') {
    header('Location: /index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("<div class='alert alert-danger'>ID inválido</div>");
}

// Obtener mantenimiento actual
$stmt = $pdo->prepare("
    SELECT m.*, c.nombre AS cliente, i.nombre AS inventario,
           u.usuario AS digitador, op.usuario AS operador,
           mod.usuario AS modificado_por_usuario
    FROM mantenimientos m
    LEFT JOIN clientes c ON c.id = m.cliente_id
    LEFT JOIN inventario i ON i.id = m.inventario_id
    LEFT JOIN usuarios u ON u.id = m.digitador_id
    LEFT JOIN usuarios op ON op.id = m.operador_id
    LEFT JOIN usuarios mod ON mod.id = m.modificado_por
    WHERE m.id = ?
");
$stmt->execute([$id]);
$mantenimiento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mantenimiento) {
    die("<div class='alert alert-danger'>Mantenimiento no encontrado</div>");
}

// Procesar formulario
$ok = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descripcion = $_POST['descripcion'] ?? '';
    $estado = $_POST['estado'] ?? '';

    try {
        $upd = $pdo->prepare("
            UPDATE mantenimientos
            SET descripcion = ?, estado = ?, 
                modificado_por = ?, modificado_en = NOW()
            WHERE id = ?
        ");
        $upd->execute([$descripcion, $estado, $_SESSION['usuario_id'], $id]);

        $ok = "Mantenimiento actualizado correctamente.";
        // refrescar datos
        $stmt->execute([$id]);
        $mantenimiento = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Error al actualizar: " . $e->getMessage();
    }
}
?>

<div class="card p-3">
  <h5>Editar Mantenimiento</h5>

  <?php if($ok): ?>
    <div class="alert alert-success small"><?= $ok ?></div>
  <?php endif; ?>
  <?php if($error): ?>
    <div class="alert alert-danger small"><?= $error ?></div>
  <?php endif; ?>

  <form method="post" class="row g-3">
    <div class="col-12">
      <label class="form-label">Cliente</label>
      <input type="text" class="form-control" value="<?= htmlspecialchars($mantenimiento['cliente']) ?>" disabled>
    </div>
    <div class="col-12">
      <label class="form-label">Inventario</label>
      <input type="text" class="form-control" value="<?= htmlspecialchars($mantenimiento['inventario']) ?>" disabled>
    </div>
    <div class="col-12">
      <label class="form-label">Descripción</label>
      <textarea name="descripcion" class="form-control" rows="4"><?= htmlspecialchars($mantenimiento['descripcion']) ?></textarea>
    </div>
    <div class="col-12">
      <label class="form-label">Estado</label>
      <select name="estado" class="form-select">
        <option value="pendiente" <?= $mantenimiento['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
        <option value="en_proceso" <?= $mantenimiento['estado'] === 'en_proceso' ? 'selected' : '' ?>>En Proceso</option>
        <option value="finalizado" <?= $mantenimiento['estado'] === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
      </select>
    </div>
    <div class="col-12 text-end">
      <button type="submit" class="btn btn-primary">💾 Guardar cambios</button>
      <a href="/operador/mis_mantenimientos.php" class="btn btn-secondary">⬅ Volver</a>
    </div>
  </form>

  <hr>
  <h6>Historial</h6>
  <ul class="list-unstyled small">
    <li><strong>Digitador:</strong> <?= htmlspecialchars($mantenimiento['digitador']) ?></li>
    <li><strong>Operador:</strong> <?= htmlspecialchars($mantenimiento['operador']) ?></li>
    <?php if ($mantenimiento['modificado_por_usuario']): ?>
      <li><strong>Última modificación por:</strong> <?= htmlspecialchars($mantenimiento['modificado_por_usuario']) ?> el <?= $mantenimiento['modificado_en'] ?></li>
    <?php else: ?>
      <li><em>Aún no ha sido modificado.</em></li>
    <?php endif; ?>
  </ul>
</div>


