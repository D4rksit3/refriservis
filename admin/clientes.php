<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestión de Clientes</title>
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
    }
    .table-responsive {
      margin-top: 20px;
    }
    .modal-lg {
      max-width: 700px;
    }
  </style>
</head>
<body>
<div class="container py-4">
  <h2 class="mb-4 text-center">Gestión de Clientes</h2>

  <!-- Botones de acción -->
  <div class="d-flex flex-wrap gap-2 mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregar">➕ Agregar Cliente</button>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalMasivo">📂 Agregar Masivo</button>
    <button class="btn btn-info text-white" onclick="exportarCSV()">⬇️ Exportar CSV</button>
  </div>

  <!-- Buscador -->
  <input type="text" id="buscar" class="form-control mb-3" placeholder="Buscar cliente...">

  <!-- Tabla de clientes -->
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle" id="tablaClientes">
      <thead class="table-dark">
        <tr>
          <th>Cliente</th>
          <th>Dirección</th>
          <th>Teléfono</th>
          <th>Responsable</th>
          <th>Email</th>
          <th>Última Visita</th>
          <th>Estatus</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="cuerpoTabla"></tbody>
    </table>
  </div>

  <!-- Paginación -->
  <div class="d-flex justify-content-between align-items-center mt-3">
    <div>
      <label>Mostrar
        <select id="registrosPorPagina" class="form-select d-inline-block w-auto">
          <option value="5">5</option>
          <option value="10" selected>10</option>
          <option value="20">20</option>
        </select>
        registros
      </label>
    </div>
    <nav>
      <ul class="pagination mb-0" id="paginacion"></ul>
    </nav>
  </div>
</div>

<!-- Modal Agregar Cliente -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Agregar Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formAgregar">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Cliente</label>
              <input type="text" class="form-control" required name="cliente">
            </div>
            <div class="col-md-6">
              <label class="form-label">Dirección</label>
              <input type="text" class="form-control" required name="direccion">
            </div>
            <div class="col-md-6">
              <label class="form-label">Teléfono</label>
              <input type="text" class="form-control" required name="telefono">
            </div>
            <div class="col-md-6">
              <label class="form-label">Responsable</label>
              <input type="text" class="form-control" required name="responsable">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" required name="email">
            </div>
            <div class="col-md-6">
              <label class="form-label">Última Visita</label>
              <input type="date" class="form-control" required name="ultimaVisita">
            </div>
            <div class="col-md-6">
              <label class="form-label">Estatus</label>
              <select class="form-select" name="estatus" required>
                <option value="Activo">Activo</option>
                <option value="Inactivo">Inactivo</option>
              </select>
            </div>
          </div>
          <div class="mt-3 text-end">
            <button type="submit" class="btn btn-primary">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Editar Cliente -->
<div class="modal fade" id="modalEditar" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">Editar Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formEditar">
          <input type="hidden" name="index" id="editIndex">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Cliente</label>
              <input type="text" class="form-control" id="editCliente" name="cliente" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Dirección</label>
              <input type="text" class="form-control" id="editDireccion" name="direccion" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Teléfono</label>
              <input type="text" class="form-control" id="editTelefono" name="telefono" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Responsable</label>
              <input type="text" class="form-control" id="editResponsable" name="responsable" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" id="editEmail" name="email" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Última Visita</label>
              <input type="date" class="form-control" id="editUltimaVisita" name="ultimaVisita" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Estatus</label>
              <select class="form-select" id="editEstatus" name="estatus" required>
                <option value="Activo">Activo</option>
                <option value="Inactivo">Inactivo</option>
              </select>
            </div>
          </div>
          <div class="mt-3 text-end">
            <button type="submit" class="btn btn-warning">Actualizar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Masivo -->
<div class="modal fade" id="modalMasivo" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Agregar Clientes Masivos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Puedes subir un archivo CSV con los clientes.</p>
        <input type="file" class="form-control">
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  let clientes = [
    {cliente: "Empresa A", direccion: "Av. Siempre Viva 123", telefono: "999111222", responsable: "Juan Pérez", email: "jperez@empresa.com", ultimaVisita: "2025-09-01", estatus: "Activo"},
    {cliente: "Empresa B", direccion: "Jr. Las Flores 456", telefono: "988777444", responsable: "Ana Torres", email: "atorres@empresa.com", ultimaVisita: "2025-08-15", estatus: "Inactivo"},
    {cliente: "Empresa C", direccion: "Calle Sol 789", telefono: "977555333", responsable: "Luis Gómez", email: "lgomez@empresa.com", ultimaVisita: "2025-07-20", estatus: "Activo"}
  ];
  let paginaActual = 1;

  function mostrarTabla() {
    let registrosPorPagina = parseInt(document.getElementById("registrosPorPagina").value);
    let filtro = document.getElementById("buscar").value.toLowerCase();
    let cuerpo = document.getElementById("cuerpoTabla");
    cuerpo.innerHTML = "";

    let filtrados = clientes.filter(c =>
      Object.values(c).some(val => val.toString().toLowerCase().includes(filtro))
    );

    let totalPaginas = Math.ceil(filtrados.length / registrosPorPagina);
    if (paginaActual > totalPaginas) paginaActual = 1;

    let inicio = (paginaActual - 1) * registrosPorPagina;
    let fin = inicio + registrosPorPagina;

    filtrados.slice(inicio, fin).forEach((c, i) => {
      let index = inicio + i;
      cuerpo.innerHTML += `
        <tr>
          <td>${c.cliente}</td>
          <td>${c.direccion}</td>
          <td>${c.telefono}</td>
          <td>${c.responsable}</td>
          <td>${c.email}</td>
          <td>${c.ultimaVisita}</td>
          <td>${c.estatus}</td>
          <td>
            <button class="btn btn-sm btn-warning" onclick="editarCliente(${index})">✏️ Editar</button>
          </td>
        </tr>`;
    });

    let paginacion = document.getElementById("paginacion");
    paginacion.innerHTML = "";
    for (let i = 1; i <= totalPaginas; i++) {
      paginacion.innerHTML += `
        <li class="page-item ${i === paginaActual ? 'active' : ''}">
          <button class="page-link" onclick="cambiarPagina(${i})">${i}</button>
        </li>`;
    }
  }

  function cambiarPagina(num) {
    paginaActual = num;
    mostrarTabla();
  }

  document.getElementById("registrosPorPagina").addEventListener("change", mostrarTabla);
  document.getElementById("buscar").addEventListener("input", mostrarTabla);

  document.getElementById("formAgregar").addEventListener("submit", e => {
    e.preventDefault();
    let data = Object.fromEntries(new FormData(e.target));
    clientes.push(data);
    mostrarTabla();
    e.target.reset();
    bootstrap.Modal.getInstance(document.getElementById("modalAgregar")).hide();
  });

  function editarCliente(index) {
    let c = clientes[index];
    document.getElementById("editIndex").value = index;
    document.getElementById("editCliente").value = c.cliente;
    document.getElementById("editDireccion").value = c.direccion;
    document.getElementById("editTelefono").value = c.telefono;
    document.getElementById("editResponsable").value = c.responsable;
    document.getElementById("editEmail").value = c.email;
    document.getElementById("editUltimaVisita").value = c.ultimaVisita;
    document.getElementById("editEstatus").value = c.estatus;
    new bootstrap.Modal(document.getElementById("modalEditar")).show();
  }

  document.getElementById("formEditar").addEventListener("submit", e => {
    e.preventDefault();
    let index = document.getElementById("editIndex").value;
    clientes[index] = Object.fromEntries(new FormData(e.target));
    mostrarTabla();
    bootstrap.Modal.getInstance(document.getElementById("modalEditar")).hide();
  });

  function exportarCSV() {
    let csv = "Cliente,Dirección,Teléfono,Responsable,Email,Última Visita,Estatus\n";
    clientes.forEach(c => {
      csv += `${c.cliente},${c.direccion},${c.telefono},${c.responsable},${c.email},${c.ultimaVisita},${c.estatus}\n`;
    });
    let blob = new Blob([csv], { type: "text/csv" });
    let link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "clientes.csv";
    link.click();
  }

  mostrarTabla();
</script>
</body>
</html>
