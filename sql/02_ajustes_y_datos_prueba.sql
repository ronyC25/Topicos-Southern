-- ============================================================
-- FleetCore — Ajustes post-v3 y datos de prueba para desarrollo
-- Se ejecuta automáticamente después del script principal (orden alfabético)
-- ============================================================
SET NAMES utf8mb4;

USE db_dispatch;

-- Columnas de trazabilidad de sesión
ALTER TABLE usuarios
ADD COLUMN token_sesion VARCHAR(64) NULL,
ADD COLUMN token_expira DATETIME NULL;

-- ------------------------------------------------------------
-- USUARIOS LOCALES DE PRUEBA (solo para desarrollo)
-- Contraseña de todos: Prueba_2026*
-- Hash generado con password_hash('Prueba_2026*', PASSWORD_BCRYPT)
-- ------------------------------------------------------------
INSERT IGNORE INTO usuarios (nombre_usuario, contrasena_hash, tipo_autenticacion, rol, nombre_completo) VALUES
('operador.prueba',  '$2y$10$jLRdja7dPA0zB2UtXuvi3.N8Rg50bb.Qj1I8Qe4FXMu3Ml9XvMIpW', 'local', 'Operador',  'Operador de Prueba'),
('conductor.prueba', '$2y$10$jLRdja7dPA0zB2UtXuvi3.N8Rg50bb.Qj1I8Qe4FXMu3Ml9XvMIpW', 'local', 'Conductor', 'Conductor de Prueba');

-- ------------------------------------------------------------
-- DATOS DE PRUEBA MÍNIMOS (para que el dashboard no salga vacío)
-- ------------------------------------------------------------
INSERT IGNORE INTO vehiculos (id_camion, modelo, marca, placa, anio, estado_operativo, capacidad_carga, tipo_combustible) VALUES
('CAM-001', '797F', 'Caterpillar', 'ABC-101', 2022, 'Operativo', 363, 'Diesel'),
('CAM-002', '930E-5', 'Komatsu',   'ABC-102', 2021, 'Operativo', 290, 'Diesel'),
('CAM-003', 'T284',   'Liebherr',  'ABC-103', 2020, 'Mantenimiento', 363, 'Diesel');

INSERT IGNORE INTO conductores (dni, nombre, licencia, telefono, estado) VALUES
('40000001', 'Juan Pérez Quispe',   'A-IIIC-001', '951000001', 'Activo'),
('40000002', 'María Flores Mamani', 'A-IIIC-002', '951000002', 'Activo');

INSERT IGNORE INTO turnos (dni_conductor, id_camion, hora_inicio, estado_turno) VALUES
('40000001', 'CAM-001', NOW(), 'Activo');

INSERT IGNORE INTO alertas (id_camion, tipo_alerta, descripcion, nivel, estado) VALUES
('CAM-001', 'Velocidad', 'Exceso de velocidad en rampa norte: 52 km/h', 'Alta', 'Activa'),
('CAM-003', 'Mantenimiento', 'Kilometraje superó el límite programado', 'Media', 'Activa');
