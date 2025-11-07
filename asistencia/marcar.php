<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Marcación de Asistencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<div class="container text-center">
    <h3 class="mb-4">Sistema de Marcación</h3>

    <select id="tipo" class="form-select mb-3" style="max-width:400px; margin:auto;">
        <option value="entrada">Entrada</option>
        <option value="salida">Salida</option>
        <option value="inicio_refrigerio">Inicio de Refrigerio</option>
        <option value="fin_refrigerio">Fin de Refrigerio</option>
        <option value="entrada_tienda">Entrada de Tienda</option>
        <option value="salida_tienda">Salida de Tienda</option>
    </select>

    <button id="btnMarcar" class="btn btn-primary">Marcar Asistencia</button>

    <div id="resultado" class="mt-4 text-success fw-bold"></div>
</div>

<script>
document.getElementById("btnMarcar").addEventListener("click", function() {
    let tipo = document.getElementById("tipo").value;

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(success, error);
    } else {
        alert("Tu navegador no soporta geolocalización");
    }

    function success(position) {
        let lat = position.coords.latitude;
        let lon = position.coords.longitude;

        fetch("registrar_marcacion.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `lat=${lat}&lon=${lon}&tipo=${tipo}`
        })
        .then(res => res.text())
        .then(data => document.getElementById("resultado").innerHTML = data);
    }

    function error(err) {
        alert("Error al obtener ubicación: " + err.message);
    }
});
</script>
</body>
</html>
