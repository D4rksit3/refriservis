<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
$usuario = $_SESSION['usuario'] ?? null;
$rol = $_SESSION['rol'] ?? null;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>RefriServis - Sistema de Mantenimiento</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/estilos.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="/admin/index.php">
      <img src="/assets/img/logo.svg" alt="RefriServis" style="height:38px;margin-right:10px">
      <span>RefriServis</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if($usuario): ?>
          <?php if($rol==='admin'): ?>
            <li class="nav-item"><a class="nav-link" href="/admin/index.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/admin/usuarios.php">Usuarios</a></li>
            <li class="nav-item"><a class="nav-link" href="/admin/clientes.php">Clientes</a></li>
            <li class="nav-item"><a class="nav-link" href="/admin/inventario.php">Inventario</a></li>
            <li class="nav-item"><a class="nav-link" href="/mantenimientos/listar.php">Mantenimientos</a></li>
          <?php elseif($rol==='digitador'): ?>
            <li class="nav-item"><a class="nav-link" href="/digitador/index.php">Mi Panel</a></li>
            <li class="nav-item"><a class="nav-link" href="/digitador/subir_mantenimiento.php">Subir Mantenimiento (CSV)</a></li>
            <li class="nav-item"><a class="nav-link" href="/digitador/mis_registros.php">Mis Registros</a></li>
          <?php elseif($rol==='operador'): ?>
            <li class="nav-item"><a class="nav-link" href="/operador/index.php">Mi Panel</a></li>
            <li class="nav-item"><a class="nav-link" href="/operador/mis_mantenimientos.php">Tareas</a></li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>

      <div class="d-flex align-items-center">
        <?php if($usuario): ?>
          <div class="me-3 text-white small">👤 <?=htmlspecialchars($usuario)?> • <em><?=htmlspecialchars($rol)?></em></div>
          <a class="btn btn-outline-light btn-sm" href="/logout.php">Salir</a>
        <?php else: ?>
          <a class="btn btn-outline-light btn-sm" href="/index.php">Ingresar</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<main class="container my-4">
