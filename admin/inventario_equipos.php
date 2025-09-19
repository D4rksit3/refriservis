// Consultar equipos
$stmt = $pdo->query("SELECT * FROM equipos ORDER BY id_equipo DESC");
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h2 class="mb-4">📌 Equipos</h2>
<div class="table-responsive">
<table id="tablaEquipos" class="table table-striped table-hover">
  <thead class="table-dark">
    <tr>
      <th>Nombre</th>
      <th>Descripción</th>
      <th>Identificador</th>
      <th>Colaborador</th>
      <th>Cliente</th>
      <th>Categoría</th>
      <th>Equipo asociado</th>
      <th>Estatus</th>
      <th>Planilla de especificaciones</th>
      <th>Fecha de validación</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($equipos as $eq): ?>
    <tr>
      <td><?=htmlspecialchars($eq['nombre'])?></td>
      <td><?=htmlspecialchars($eq['descripcion'])?></td>
      <td><?=htmlspecialchars($eq['identificador'])?></td>
      <td><?=htmlspecialchars($eq['colaborador'])?></td>
      <td><?=htmlspecialchars($eq['cliente'])?></td>
      <td><?=htmlspecialchars($eq['categoria'])?></td>
      <td><?=htmlspecialchars($eq['equipo_asociado'])?></td>
      <td><?=htmlspecialchars($eq['estatus'])?></td>
      <td><?=htmlspecialchars($eq['planilla_especificaciones'])?></td>
      <td><?=htmlspecialchars($eq['fecha_validacion'])?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
  $(document).ready(function() {
    // Inicializar DataTables con español y 10 filas por página
    $('table').DataTable({
      pageLength: 10,
      lengthChange: false,
      language: {
        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
      }
    });
  });
</script>