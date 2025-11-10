asistencia/
│
├── sql/
│   └── db_asistencia_ext.sql
│
├── public/
│   ├── index.php            <-- login / dashboard
│   ├── marcar.php           <-- interfaz de marcación (empleado)
│   ├── map_dia.php          <-- mapa con marcaciones del día por usuario
│   ├── reporte.php         <-- reporte avanzado (filtros por fecha/usuario)
│   └── assets/
│       ├── css/
│       └── js/
│           └── marcar.js
│
├── api/
│   ├── conexion.php
│   ├── funciones.php
│   ├── geolocalizacion.php
│   ├── registrar_marcacion.php
│   ├── auth.php             <-- login / session handling
│   └── usuarios.php         <-- endpoints de administración (crear/listar)
│
└── README.md