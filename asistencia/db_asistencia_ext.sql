-- -----------------------------------------------------
-- Base de datos: db_asistencia_ext
-- -----------------------------------------------------
CREATE DATABASE IF NOT EXISTS db_asistencia_ext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE db_asistencia_ext;

-- -----------------------------------------------------
-- Tabla: usuarios
-- -----------------------------------------------------
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    dni VARCHAR(15) NOT NULL UNIQUE,
    correo VARCHAR(120),
    clave VARCHAR(255) NOT NULL,
    rol ENUM('admin','supervisor','empleado') DEFAULT 'empleado',
    sede VARCHAR(100) DEFAULT NULL,
    horario_id INT DEFAULT NULL,
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: horarios (horarios fijos asignados a usuarios)
-- -----------------------------------------------------
CREATE TABLE horarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_break_inicio TIME DEFAULT NULL,
    hora_break_fin TIME DEFAULT NULL,
    hora_salida TIME NOT NULL,
    tolerancia_minutos INT DEFAULT 10,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: marcaciones
-- -----------------------------------------------------
CREATE TABLE marcaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('entrada','inicio_refrigerio','fin_refrigerio','entrada_campo','salida_campo','salida') NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    latitud DECIMAL(10,7),
    longitud DECIMAL(10,7),
    direccion VARCHAR(255),
    distrito VARCHAR(100),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: incidencias (para retardos, salidas tempranas, ausencias)
-- -----------------------------------------------------
CREATE TABLE incidencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fecha DATE NOT NULL,
    tipo ENUM('tardanza','salida_temprano','inasistencia','marcacion_fuera_zona') NOT NULL,
    detalle VARCHAR(255),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Tabla: zonas_autorizadas (para validar ubicación GPS)
-- -----------------------------------------------------
CREATE TABLE zonas_autorizadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    latitud DECIMAL(10,7) NOT NULL,
    longitud DECIMAL(10,7) NOT NULL,
    radio_metros INT DEFAULT 100,
    sede VARCHAR(100),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Vista: vista_reporte_asistencia
-- -----------------------------------------------------
CREATE OR REPLACE VIEW vista_reporte_asistencia AS
SELECT 
    u.id AS usuario_id,
    u.nombre,
    u.dni,
    u.sede,
    m.fecha,
    MAX(CASE WHEN m.tipo='entrada' THEN m.hora END) AS hora_entrada,
    MAX(CASE WHEN m.tipo='inicio_refrigerio' THEN m.hora END) AS hora_break_inicio,
    MAX(CASE WHEN m.tipo='fin_refrigerio' THEN m.hora END) AS hora_break_fin,
    MAX(CASE WHEN m.tipo='entrada_campo' THEN m.hora END) AS hora_campo_inicio,
    MAX(CASE WHEN m.tipo='salida_campo' THEN m.hora END) AS hora_campo_fin,
    MAX(CASE WHEN m.tipo='salida' THEN m.hora END) AS hora_salida,
    GROUP_CONCAT(CONCAT(m.tipo, ' @', m.distrito, ' (', m.hora, ')') ORDER BY m.hora SEPARATOR ' | ') AS ubicaciones
FROM usuarios u
LEFT JOIN marcaciones m ON u.id = m.usuario_id
GROUP BY u.id, m.fecha
ORDER BY m.fecha DESC;

-- -----------------------------------------------------
-- Inserciones iniciales
-- -----------------------------------------------------
INSERT INTO horarios (nombre, hora_inicio, hora_break_inicio, hora_break_fin, hora_salida, tolerancia_minutos)
VALUES 
('Horario Estándar', '08:00:00', '12:30:00', '13:00:00', '17:00:00', 10);

INSERT INTO usuarios (nombre, dni, correo, clave, rol, sede, horario_id)
VALUES
('Administrador General', '00000000', 'admin@empresa.com', 
 '$2y$10$XJc2U2P7fYkZcRM5Xg1YneM5jR4sBvF/x5jHXfTdz3GmAQ5KxC31G', 'admin', 'Oficina Central', 1);

INSERT INTO zonas_autorizadas (nombre, latitud, longitud, radio_metros, sede)
VALUES 
('Oficina Central', -12.046373, -77.042754, 200, 'Oficina Central');
