<?php
// ==========================
// CONFIGURACIÓN Y CONEXIÓN
// ==========================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db.php';

// ==========================
// GUARDAR DATOS
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre = trim($_POST['nombre'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $estatus = isset($_POST['estatus']) ? 'Activo' : 'Inactivo'; // Guardar texto
        $valor_unitario = $_POST['valor_unitario'] ?? 0;

        if ($nombre === '' || $categoria === '') {
            throw new Exception("⚠️ Nombre y Categoría son obligatorios.");
        }

        $sql = "INSERT INTO productos (Nombre, Categoria, Estatus, Valor_unitario) 
                VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $categoria, $estatus, $valor_unitario]);

        // ✅ Redirección después de guardar
        header("Location: https://refriservis.seguricloud.com/admin/clientes.php?ok=1");
        exit;
    } catch (Exception $e) {
        die("❌ Error: " . $e->getMessage());
    } catch (PDOException $e) {
        die("❌ Error SQL: " . $e->getMessage());
    }
}

// ==========================
// LISTAR DATOS
// ==========================
try {
    $stmt = $pdo->query("SELECT * FROM productos ORDER BY productos_id DESC");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("❌ Error al listar productos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Productos</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th, table td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }
        table th {
            background: #f0f0f0;
        }
        .activo { color: green; font-weight: bold; }
        .inactivo { color: red; font-weight: bold; }

        /* Modal */
        #modal {
            display:none;
            position:fixed;
            top:0; left:0;
            width:100%; height:100%;
            background:rgba(0,0,0,0.6);
        }
        #modal .contenido {
            background:#fff;
            padding:20px;
            margin:50px auto;
            width:400px;
            border-radius:10px;
        }
        button {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover { opacity: 0.8; }

        /* Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .switch input {display:none;}
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0;
            right: 0; bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px; width: 18px;
            left: 3px; bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #4CAF50;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
    </style>
</head>
<body>

    <!-- ==========================
         LISTADO PRODUCTOS
    =========================== -->
    <h2>📦 Lista de Productos</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Estatus</th>
                <th>Valor Unitario</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($productos) > 0): ?>
                <?php foreach ($productos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['productos_id']) ?></td>
                    <td><?= htmlspecialchars($p['Nombre']) ?></td>
                    <td><?= htmlspecialchars($p['Categoria']) ?></td>
                    <td class="<?= ($p['Estatus'] === 'Activo') ? 'activo' : 'inactivo' ?>">
                        <?= htmlspecialchars($p['Estatus']) ?>
                    </td>
                    <td><?= htmlspecialchars($p['Valor_unitario']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">⚠️ No hay productos registrados</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- ==========================
         BOTÓN Y MODAL
    =========================== -->
    <br>
    <button onclick="document.getElementById('modal').style.display='block'">➕ Agregar Producto</button>

    <div id="modal">
        <div class="contenido">
            <h3>Agregar Producto</h3>
            <form method="post">
                <label>Nombre:</label><br>
                <input type="text" name="nombre" required><br><br>

                <label>Categoría:</label><br>
                <input type="text" name="categoria" required><br><br>

                <label>Estatus:</label><br>
                <label class="switch">
                    <input type="checkbox" name="estatus" checked>
                    <span class="slider"></span>
                </label>
                <br><br>

                <label>Valor Unitario:</label><br>
                <input type="number" step="0.01" name="valor_unitario" required><br><br>

                <button type="submit" style="background:#4CAF50;color:#fff;">Guardar</button>
                <button type="button" onclick="document.getElementById('modal').style.display='none'" style="background:#aaa;color:#fff;">Cancelar</button>
            </form>
        </div>
    </div>

</body>
</html>
