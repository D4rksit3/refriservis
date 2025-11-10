<?php
// public/admin_usuarios.php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','supervisor'])) {
    header('Location: /index.php');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Admin - Usuarios</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Gestión de Usuarios</h3>
    <div>
      <a href="/marcar.php" class="btn btn-secondary btn-sm">Ir a Marcación</a>
      <button id="logout" class="btn btn-outline-danger btn-sm">Cerrar sesión</button>
    </div>
  </div>

  <div class="card mb-3 p-3">
    <h5>Crear / Editar usuario</h5>
    <form id="frmUsuario" class="row g-2">
      <input type="hidden" name="action" value="crear" id="actionInput">
      <input type="hidden" name="id" id="userid">
      <div class="col-md-3"><input name="dni" id="dni" class="form-control" placeholder="DNI" required></div>
      <div class="col-md-3"><input name="nombre" id="nombre" class="form-control" placeholder="Nombre" required></div>
      <div class="col-md-3"><input name="email" id="email" class="form-control" placeholder="Email"></div>
      <div class="col-md-2">
        <select name="rol" id="rol" class="form-select">
          <option value="empleado">Empleado</option>
          <option value="supervisor">Supervisor</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="col-md-2"><input name="password" id="password" class="form-control" placeholder="Contraseña (solo si cambia)"></div>

      <div class="col-12">
        <button class="btn btn-primary">Guardar</button>
        <button type="button" id="btnReset" class="btn btn-secondary">Reset</button>
      </div>
    </form>
  </div>

  <h5>Listado</h5>
  <table class="table table-striped" id="tblUsuarios">
    <thead class="table-dark"><tr><th>Nombre</th><th>DNI</th><th>Email</th><th>Rol</th><th>Activo</th><th>Acciones</th></tr></thead>
    <tbody></tbody>
  </table>
</div>

<script>
async function loadUsuarios(){
  const res = await fetch('/api/usuarios.php?action=listar');
  const js = await res.json();
  const tbody = document.querySelector('#tblUsuarios tbody');
  tbody.innerHTML = '';
  if (js.ok) {
    js.rows.forEach(r=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${r.nombre}</td><td>${r.dni}</td><td>${r.email||''}</td><td>${r.rol}</td>
                      <td>${r.activo==1? 'Sí':'No'}</td>
                      <td>
                        <button class="btn btn-sm btn-primary" onclick="editar(${r.id})">Editar</button>
                        <button class="btn btn-sm btn-${r.activo==1?'warning':'success'}" onclick="toggle(${r.id},${r.activo})">${r.activo==1?'Desactivar':'Activar'}</button>
                      </td>`;
      tbody.appendChild(tr);
    });
  } else {
    alert('Error cargando usuarios');
  }
}

async function editar(id){
  // llamar listar y buscar el id (o crear endpoint get)
  const res = await fetch('/api/usuarios.php?action=listar');
  const js = await res.json();
  const user = js.rows.find(x=>x.id==id);
  if(!user) return alert('Usuario no encontrado');
  document.getElementById('actionInput').value = 'editar';
  document.getElementById('userid').value = user.id;
  document.getElementById('dni').value = user.dni;
  document.getElementById('nombre').value = user.nombre;
  document.getElementById('email').value = user.email;
  document.getElementById('rol').value = user.rol;
  document.getElementById('password').value = '';
  window.scrollTo({top:0, behavior:'smooth'});
}

async function toggle(id,activo){
  const action = activo==1 ? 'desactivar' : 'activar';
  const form = new FormData();
  form.append('action', action);
  form.append('id', id);
  const res = await fetch('/api/usuarios.php', { method:'POST', body: form });
  const js = await res.json();
  if(js.ok) loadUsuarios(); else alert(js.msg || 'Error');
}

document.getElementById('frmUsuario').addEventListener('submit', async function(e){
  e.preventDefault();
  const fd = new FormData(this);
  const res = await fetch('/api/usuarios.php', { method:'POST', body: fd });
  const js = await res.json();
  if (js.ok) {
    alert(js.msg || 'Guardado');
    this.reset();
    document.getElementById('actionInput').value = 'crear';
    loadUsuarios();
  } else {
    alert(js.msg || 'Error');
  }
});

document.getElementById('btnReset').addEventListener('click', function(){
  document.getElementById('frmUsuario').reset();
  document.getElementById('actionInput').value = 'crear';
  document.getElementById('userid').value = '';
});

document.getElementById('logout').addEventListener('click', async function(){
  const fd = new FormData();
  fd.append('action','logout');
  const r = await fetch('/api/auth.php', { method: 'POST', body: fd });
  const j = await r.json();
  if (j.ok) window.location.href = '/index.php';
});

loadUsuarios();
</script>
</body>
</html>
