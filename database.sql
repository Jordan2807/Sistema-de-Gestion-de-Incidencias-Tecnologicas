
-- =========================================================
-- SISTEMA DE GESTION DE INCIDENCIAS DE LABORATORIOS
-- UISIL - Facultad de Ingenieria de Sistemas
-- =========================================================

CREATE DATABASE IF NOT EXISTS sistema_incidencias
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sistema_incidencias;

-- =========================================================
-- TABLA: administradores
-- Las contrasenas se almacenan como hashes generados por PHP.
-- No se crea ninguna cuenta predeterminada.
-- =========================================================

CREATE TABLE IF NOT EXISTS administradores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- TABLA: incidencias
-- =========================================================

CREATE TABLE IF NOT EXISTS incidencias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reportante VARCHAR(100) NOT NULL,
    laboratorio VARCHAR(100) NOT NULL,
    equipo VARCHAR(150) NOT NULL,
    descripcion TEXT NOT NULL,

    prioridad ENUM('Baja', 'Media', 'Alta', 'Urgente')
        NOT NULL DEFAULT 'Media',

    estado ENUM('Pendiente', 'En proceso', 'Resuelta')
        NOT NULL DEFAULT 'Pendiente',

    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_laboratorio (laboratorio),
    INDEX idx_estado (estado),
    INDEX idx_prioridad (prioridad),
    INDEX idx_fecha_registro (fecha_registro)
) ENGINE=InnoDB;

-- =========================================================
-- FIN DEL SCRIPT
-- =========================================================
