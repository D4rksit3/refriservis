/proyecto_mantenimiento
│── /config
│    ├── db.php               # Conexión a la BD
│    └── auth.php             # Control de sesiones y roles
│
│── /assets
│    ├── /css
│    │    └── estilos.css
│    ├── /js
│    │    └── scripts.js
│    └── /img
│
│── /uploads                  # Archivos subidos (mantenimientos, fotos, reportes)
│
│── /admin                    # Panel del Administrador
│    ├── index.php            # Dashboard
│    ├── usuarios.php         # CRUD de usuarios
│    ├── inventario.php       # CRUD de inventario
│    ├── clientes.php         # CRUD de clientes
│    └── reportes.php         # Reportes generales
│
│── /digitador                # Panel del Digitador
│    ├── index.php            # Dashboard
│    ├── subir_mantenimiento.php # Formulario para registrar mantenimientos
│    └── mis_registros.php    # Ver los registros que subió
│
│── /operador                 # Panel del Operador
│    ├── index.php            # Dashboard
│    ├── mis_mantenimientos.php # Lista de mantenimientos asignados
│    └── actualizar_estado.php  # Cambiar estado de mantenimiento (pendiente, en proceso, finalizado)
│
│── /mantenimientos           # CRUD compartido (solo Admin puede ver todos)
│    ├── crear.php
│    ├── listar.php
│    ├── editar.php
│    └── eliminar.php
│
│── /includes
│    ├── header.php           # Encabezado HTML (menu segun rol)
│    └── footer.php           # Pie de página
│
│── index.php                 # Login principal
│── logout.php                # Cerrar sesión
