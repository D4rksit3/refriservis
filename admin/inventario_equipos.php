<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__."/../config/db.php";

// Agregar equipo
if(isset($_POST['accion']) && $_POST['accion'] == 'agregar'){
    $stmt = $pdo->prepare("INSERT INTO equipos 
        (nombre, descripcion, identificador, colaborador, cliente, categoria, equipo_asociado, estatus, planilla, fecha_validacion)
        VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['nombre'], $_POST['descripcion'], $_POST['identificador'], 
        $_POST['colaborador'], $_POST['cliente'], $_POST['categoria'], 
        $_POST['equipo_asociado'], $_POST['estatus'], $_POST['planilla'], 
        $_POST['fecha_validacion']
    ]);
    header("Location: inventario_equipos.php");
    exit;
}

// Editar equipo
if(isset($_POST['accion']) && $_POST['accion'] == 'editar'){
    $stmt = $pdo->prepare("UPDATE equipos SET 
        nombre=?, descripcion=?, identificador=?, colaborador=?, cliente=?, categoria=?, 
        equipo_asociado=?, estatus=?, planilla=?, fecha_validacion=? WHERE id_equipo=?");
    $stmt->execute([
        $_POST['nombre'], $_POST['descripcion'], $_POST['identificador'], 
        $_POST['colaborador'], $_POST['cliente'], $_POST['categoria'], 
        $_POST['equipo_asociado'], $_POST['estatus'], $_POST['planilla'], 
        $_POST['fecha_validacion'], $_POST['id_equipo']
    ]);
    header("Location: inventario_equipos.php");
    exit;
}

// Eliminar equipo
if(isset($_POST['accion']) && $_POST['accion'] == 'eliminar'){
    $stmt = $pdo->prepare("DELETE FROM equipos WHERE id_equipo=?");
    $stmt->execute([$_POST['id_equipo']]);
    header("Location: inventario_equipos.php");
    exit;
}

// Paginación
$limite = 5;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina - 1) * $limite;

// Contar total
$total = $pdo->query("SELECT COUNT(*) FROM equipos")->fetchColumn();
$total_paginas = ceil($total / $limite);

// Traer equipos
$stmt = $pdo->prepare("SELECT * FROM equipos ORDER BY id_equipo DESC LIMIT :inicio,:limite");
$stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->execute();
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario de Equipos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h2 class="mb-4">📦 Inventario de Equipos</h2>

    <!-- Botón agregar -->
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#modalAgregar">
        ➕ Agregar Equipo
    </button>

    <!-- Tabla -->
    <div class="card shadow">
        <div class="card-body">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Identificador</th>
                        <th>Colaborador</th>
                        <th>Cliente</th>
                        <th>Categoría</th>
                        <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($equipos as $eq): ?>
                    <tr>
                        <td><?= htmlspecialchars($eq['nombre']) ?></td>
                        <td><?= htmlspecialchars($eq['descripcion']) ?></td>
                        <td><?= htmlspecialchars($eq['identificador']) ?></td>
                        <td><?= htmlspecialchars($eq['colaborador']) ?></td>
                        <td><?= htmlspecialchars($eq['cliente']) ?></td>
                        <td><?= htmlspecialchars($eq['categoria']) ?></td>
                        <td><?= htmlspecialchars($eq['estatus']) ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalEditar<?= $eq['id_equipo'] ?>">✏️</button>
                            <button class="btn btn-danger btn-sm" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalEliminar<?= $eq['id_equipo'] ?>">🗑️</button>
                        </td>
                    </tr>

                    <!-- Modal Editar -->
                    <div class="modal fade" id="modalEditar<?= $eq['id_equipo'] ?>" tabindex="-1">
                      <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                          <form method="POST">
                            <div class="modal-header bg-primary text-white">
                              <h5 class="modal-title">Editar Equipo</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                              <input type="hidden" name="accion" value="editar">
                              <input type="hidden" name="id_equipo" value="<?= $eq['id_equipo'] ?>">
                              <div class="row g-2">
                                <div class="col-md-6">
                                  <label class="form-label">Nombre</label>
                                  <input type="text" class="form-control" name="nombre" value="<?= $eq['nombre'] ?>" required>
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label">Identificador</label>
                                  <input type="text" class="form-control" name="identificador" value="<?= $eq['identificador'] ?>">
                                </div>
                                <div class="col-12">
                                  <label class="form-label">Descripción</label>
                                  <textarea class="form-control" name="descripcion"><?= $eq['descripcion'] ?></textarea>
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label">Colaborador</label>
                                  <input type="text" class="form-control" name="colaborador" value="<?= $eq['colaborador'] ?>">
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label">Cliente</label>
                                  <input type="text" class="form-control" name="cliente" value="<?= $eq['cliente'] ?>">
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label">Categoría</label>
                                  <input type="text" class="form-control" name="categoria" value="<?= $eq['categoria'] ?>">
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label">Estatus</label>
                                  <select name="estatus" class="form-select">
                                    <option <?= $eq['estatus']=="Activo"?"selected":"" ?>>Activo</option>
                                    <option <?= $eq['estatus']=="Inactivo"?"selected":"" ?>>Inactivo</option>
                                    <option <?= $eq['estatus']=="En Mantenimiento"?"selected":"" ?>>En Mantenimiento</option>
                                  </select>
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label">Planilla</label>
                                  <input type="text" class="form-control" name="planilla" value="<?= $eq['planilla'] ?>">
                                </div>
                                <div class="col-md-6">
                                  <label class="form-label">Fecha Validación</label>
                                  <input type="date" class="form-control" name="fecha_validacion" value="<?= $eq['fecha_validacion'] ?>">
                                </div>
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="submit" class="btn btn-primary">💾 Guardar</button>
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Cancelar</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>

                    <!-- Modal Eliminar -->
                    <div class="modal fade" id="modalEliminar<?= $eq['id_equipo'] ?>" tabindex="-1">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <form method="POST">
                            <div class="modal-header bg-danger text-white">
                              <h5 class="modal-title">Eliminar Equipo</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                              ¿Seguro que deseas eliminar <b><?= $eq['nombre'] ?></b>?
                              <input type="hidden" name="accion" value="eliminar">
                              <input type="hidden" name="id_equipo" value="<?= $eq['id_equipo'] ?>">
                            </div>
                            <div class="modal-footer">
                              <button type="submit" class="btn btn-danger">🗑️ Eliminar</button>
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Cancelar</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    <nav class="mt-3">
      <ul class="pagination">
        <?php for($i=1;$i<=$total_paginas;$i++): ?>
          <li class="page-item <?= ($i==$pagina)?'active':'' ?>">
            <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
</div>

<!-- Modal Agregar -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">Agregar Equipo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="accion" value="agregar">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Nombre</label>
              <input type="text" class="form-control" name="nombre" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Identificador</label>
              <input type="text" class="form-control" name="identificador">
            </div>
            <div class="col-12">
              <label class="form-label">Descripción</label>
              <textarea class="form-control" name="descripcion"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Colaborador</label>
              <input type="text" class="form-control" name="colaborador">
            </div>
            <div class="col-md-6">
              <label class="form-label">Cliente</label>
              <input type="text" class="form-control" name="cliente">
            </div>
            <div class="col-md-6">
              <label class="form-label">Categoría</label>
              <input type="text" class="form-control" name="categoria">
            </div>
            <div class="col-md-6">
              <label class="form-label">Estatus</label>
              <select name="estatus" class="form-select">
                <option>Activo</option>
                <option>Inactivo</option>
                <option>En Mantenimiento</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Planilla</label>
              <input type="text" class="form-control" name="planilla">
            </div>
            <div class="col-md-6">
              <label class="form-label">Fecha Validación</label>
              <input type="date" class="form-control" name="fecha_validacion">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">💾 Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
