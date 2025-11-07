CREATE DATABASE IF NOT EXISTS asistencia_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE asistencia_db;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    rol ENUM('admin','supervisor','empleado') DEFAULT 'empleado'
);

CREATE TABLE marcaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    tipo ENUM('entrada','salida','inicio_refrigerio','fin_refrigerio','entrada_tienda','salida_tienda'),
    fecha DATE,
    hora TIME,
    latitud DECIMAL(10,8),
    longitud DECIMAL(11,8),
    direccion TEXT,
    distrito VARCHAR(100),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);
