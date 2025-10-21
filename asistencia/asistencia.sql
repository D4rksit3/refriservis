CREATE DATABASE asistencia_db CHARACTER SET utf8mb4;
USE asistencia_db;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  documento VARCHAR(50) UNIQUE NOT NULL
);

CREATE TABLE asistencias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  tipo ENUM('entrada', 'salida', 'visita') NOT NULL,
  fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
  latitud VARCHAR(50),
  longitud VARCHAR(50),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
