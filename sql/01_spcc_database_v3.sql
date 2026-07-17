-- ============================================================
-- BASE DE DATOS: SISTEMA DE TELEMETRÍA Y DESPACHO - SPCC
-- Versión: 3.0
-- Cambios respecto a v2:
--   - Todos los nombres de tablas y columnas traducidos al español
--   - users        → usuarios
--   - vehicles     → vehiculos
--   - drivers      → conductores
--   - shifts       → turnos
--   - breaks       → descansos
--   - telemetry    → telemetria
--   - alerts       → alertas
--   - username     → nombre_usuario
--   - password_hash→ contrasena_hash
--   - tipo_auth    → tipo_autenticacion
--   - email        → correo (en usuarios y conductores)
-- ============================================================

-- ============================================================
-- TABLA: USUARIOS
-- Incluye tanto usuarios locales (conductores, operarios)
-- como los 3 admins que autentican vía Active Directory.
-- contrasena_hash es NULL para cuentas AD;
-- tipo_autenticacion indica quién valida la contraseña.
-- ============================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
    id                    INT PRIMARY KEY AUTO_INCREMENT,
    nombre_usuario        VARCHAR(50) UNIQUE NOT NULL,
    dni                   VARCHAR(15) NULL UNIQUE,
    contrasena_hash       VARCHAR(255) NULL,                       -- NULL si tipo_autenticacion = 'active_directory'
    tipo_autenticacion    ENUM('local','active_directory')
                          NOT NULL DEFAULT 'local',
    rol                   ENUM('Admin_Servidor',
                               'Admin_BD',
                               'Admin_Telemetria',
                               'Operador',
                               'Conductor') NOT NULL,
    nombre_completo       VARCHAR(100) NOT NULL,
    correo                VARCHAR(100),
    telefono              VARCHAR(20),
    activo                TINYINT(1) DEFAULT 1,
    ultimo_acceso         DATETIME,
    fecha_creacion        DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre_usuario   (nombre_usuario),
    INDEX idx_rol              (rol),
    INDEX idx_activo           (activo)
) ENGINE=InnoDB;

-- Registros iniciales: los 3 admins del servidor (sin contrasena_hash, autentican por AD)
-- Ejecutar solo una vez al inicializar la base de datos.
INSERT IGNORE INTO usuarios (nombre_usuario, contrasena_hash, tipo_autenticacion, rol, nombre_completo) VALUES
('admin.servidor',   NULL, 'active_directory', 'Admin_Servidor',  'Administrador del Servidor'),
('admin.bd',         NULL, 'active_directory', 'Admin_BD',        'Administrador de Base de Datos'),
('admin.telemetria', NULL, 'active_directory', 'Admin_Telemetria','Administrador de Telemetría');

-- ============================================================
-- TABLA: VEHICULOS
-- ============================================================
CREATE TABLE IF NOT EXISTS vehiculos (
    id_camion             VARCHAR(20) PRIMARY KEY,
    modelo                VARCHAR(50) NOT NULL,
    kilometraje_total     DOUBLE DEFAULT 0,
    limite_mantenimiento  DOUBLE DEFAULT 10000,
    estado_operativo      ENUM('Operativo','Mantenimiento','Fuera_Servicio') DEFAULT 'Operativo',
    ultima_ubicacion      TEXT,
    ultima_actualizacion  DATETIME,
    capacidad_carga       DOUBLE,
    tipo_combustible      VARCHAR(50),
    placa                 VARCHAR(20),
    marca                 VARCHAR(50),
    anio                  INT,
    fecha_creacion        DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_estado (estado_operativo),
    INDEX idx_placa  (placa)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: CONDUCTORES
-- ============================================================
CREATE TABLE IF NOT EXISTS conductores (
    dni                 VARCHAR(15) PRIMARY KEY,
    nombre              VARCHAR(100) NOT NULL,
    licencia            VARCHAR(20) NOT NULL,
    telefono            VARCHAR(20),
    correo              VARCHAR(100),
    direccion           TEXT,
    fecha_nacimiento    DATE,
    fecha_ingreso       DATE,
    estado              ENUM('Activo','Inactivo','Suspendido') DEFAULT 'Activo',
    evaluacion_promedio DOUBLE DEFAULT 0.0,
    fecha_creacion      DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dni    (dni),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: TURNOS — TABLA PADRE / CENTRAL
-- Es el núcleo transaccional del sistema: une conductor,
-- vehículo y tiempo operativo. De aquí cuelgan telemetria,
-- descansos, incidencias y alertas.
-- ============================================================
CREATE TABLE IF NOT EXISTS turnos (
    id_turno              INT PRIMARY KEY AUTO_INCREMENT,
    dni_conductor         VARCHAR(15) NOT NULL,
    id_camion             VARCHAR(20) NOT NULL,
    hora_inicio           DATETIME NOT NULL,
    hora_fin              DATETIME,
    tiempo_manejo_total   INT DEFAULT 0,
    tiempo_descanso_total INT DEFAULT 0,
    distancia_recorrida   DOUBLE DEFAULT 0,
    velocidad_promedio    DOUBLE DEFAULT 0,
    velocidad_maxima      DOUBLE DEFAULT 0,
    estado_turno          ENUM('Activo','Pausado','Finalizado','Cancelado') DEFAULT 'Activo',
    fecha_creacion        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dni_conductor) REFERENCES conductores(dni)      ON DELETE CASCADE,
    FOREIGN KEY (id_camion)     REFERENCES vehiculos(id_camion)  ON DELETE CASCADE,
    INDEX idx_conductor    (dni_conductor),
    INDEX idx_camion       (id_camion),
    INDEX idx_estado_turno (estado_turno),
    INDEX idx_fecha_inicio (hora_inicio)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: DESCANSOS
-- Depende de turnos.
-- ============================================================
CREATE TABLE IF NOT EXISTS descansos (
    id_descanso      INT PRIMARY KEY AUTO_INCREMENT,
    id_turno         INT NOT NULL,
    tipo             ENUM('Descanso_Reglamentario','Almuerzo','Descanso_Intermedio') NOT NULL,
    hora_inicio      DATETIME NOT NULL,
    hora_fin         DATETIME,
    duracion_minutos INT,
    ubicacion        TEXT,
    FOREIGN KEY (id_turno) REFERENCES turnos(id_turno) ON DELETE CASCADE,
    INDEX idx_turno (id_turno),
    INDEX idx_tipo  (tipo)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: TELEMETRIA
-- Depende de turnos.
-- ============================================================
CREATE TABLE IF NOT EXISTS telemetria (
    id_registro    INT PRIMARY KEY AUTO_INCREMENT,
    id_turno       INT NOT NULL,
    id_camion      VARCHAR(20) NOT NULL,
    latitud        DOUBLE NOT NULL,
    longitud       DOUBLE NOT NULL,
    velocidad_kmh  DOUBLE DEFAULT 0,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_turno)  REFERENCES turnos(id_turno)         ON DELETE CASCADE,
    FOREIGN KEY (id_camion) REFERENCES vehiculos(id_camion)     ON DELETE CASCADE,
    INDEX idx_turno          (id_turno),
    INDEX idx_camion         (id_camion),
    INDEX idx_fecha_registro (fecha_registro)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: ALERTAS
-- id_turno permite saber qué conductor estaba activo
-- cuando se generó la alerta.
-- ============================================================
CREATE TABLE IF NOT EXISTS alertas (
    id_alerta        INT PRIMARY KEY AUTO_INCREMENT,
    id_camion        VARCHAR(20) NOT NULL,
    id_turno         INT NULL,
    tipo_alerta      ENUM('Velocidad','Descanso','Mantenimiento',
                          'Geocercas','Combustible','GPS') NOT NULL,
    descripcion      TEXT,
    nivel            ENUM('Baja','Media','Alta','Critica') NOT NULL,
    estado           ENUM('Activa','Resuelta','Descartada') DEFAULT 'Activa',
    fecha_generacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion DATETIME,
    FOREIGN KEY (id_camion) REFERENCES vehiculos(id_camion)     ON DELETE CASCADE,
    FOREIGN KEY (id_turno)  REFERENCES turnos(id_turno)         ON DELETE SET NULL,
    INDEX idx_camion          (id_camion),
    INDEX idx_turno           (id_turno),
    INDEX idx_estado          (estado),
    INDEX idx_nivel           (nivel),
    INDEX idx_fecha_generacion(fecha_generacion)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: INCIDENCIAS
-- reportado_por registra el usuario que creó la incidencia.
-- ============================================================
CREATE TABLE IF NOT EXISTS incidencias (
    id_incidencia    INT PRIMARY KEY AUTO_INCREMENT,
    id_turno         INT NOT NULL,
    reportado_por    VARCHAR(50) NULL,
    descripcion      TEXT NOT NULL,
    nivel_severidad  ENUM('Baja','Media','Alta','Critica') NOT NULL,
    estado_atencion  ENUM('Pendiente','En_Revision','Resuelta','Cerrada') DEFAULT 'Pendiente',
    fecha_reporte    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_turno)      REFERENCES turnos(id_turno)              ON DELETE CASCADE,
    FOREIGN KEY (reportado_por) REFERENCES usuarios(nombre_usuario)      ON DELETE SET NULL,
    INDEX idx_turno          (id_turno),
    INDEX idx_severidad      (nivel_severidad),
    INDEX idx_estado_atencion(estado_atencion)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: LIMITES_OPERACION
-- ============================================================
CREATE TABLE IF NOT EXISTS limites_operacion (
    id_limite   INT PRIMARY KEY AUTO_INCREMENT,
    id_camion   VARCHAR(20),
    tipo_limite ENUM('Velocidad_Max','Tiempo_Manejo_Max','Tiempo_Descanso_Min') NOT NULL,
    valor       DOUBLE NOT NULL,
    unidad      VARCHAR(20) NOT NULL,
    activo      TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_camion) REFERENCES vehiculos(id_camion) ON DELETE CASCADE,
    INDEX idx_camion      (id_camion),
    INDEX idx_tipo_limite (tipo_limite)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: MANTENIMIENTOS
-- ============================================================
CREATE TABLE IF NOT EXISTS mantenimientos (
    id_mantenimiento      INT PRIMARY KEY AUTO_INCREMENT,
    id_camion             VARCHAR(20) NOT NULL,
    tipo_servicio         ENUM('Preventivo','Correctivo','Programado') NOT NULL,
    fecha_servicio        DATE NOT NULL,
    kilometraje_realizado DOUBLE,
    tecnico_responsable   VARCHAR(100),
    descripcion           TEXT,
    costo                 DOUBLE,
    estado                ENUM('Pendiente','En_Proceso','Completado','Cancelado') DEFAULT 'Pendiente',
    fecha_creacion        DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_camion) REFERENCES vehiculos(id_camion) ON DELETE CASCADE,
    INDEX idx_camion        (id_camion),
    INDEX idx_estado        (estado),
    INDEX idx_fecha_servicio(fecha_servicio)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: TICKETS_ATENCION
-- ============================================================
CREATE TABLE IF NOT EXISTS tickets_atencion (
    id_ticket         INT PRIMARY KEY AUTO_INCREMENT,
    id_camion         VARCHAR(20) NOT NULL,
    id_mantenimiento  INT,
    tipo              ENUM('Mantenimiento','Reparacion','Inspeccion','Otro') NOT NULL,
    prioridad         ENUM('Baja','Media','Alta','Critica') NOT NULL,
    estado            ENUM('Abierto','En_Proceso','Resuelto','Cerrado') DEFAULT 'Abierto',
    descripcion       TEXT NOT NULL,
    usuario_creacion  VARCHAR(50) NOT NULL,
    fecha_creacion    DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre      DATETIME,
    FOREIGN KEY (id_camion)        REFERENCES vehiculos(id_camion)              ON DELETE CASCADE,
    FOREIGN KEY (id_mantenimiento) REFERENCES mantenimientos(id_mantenimiento)  ON DELETE SET NULL,
    INDEX idx_camion        (id_camion),
    INDEX idx_estado        (estado),
    INDEX idx_prioridad     (prioridad),
    INDEX idx_fecha_creacion(fecha_creacion)
) ENGINE=InnoDB;

-- ============================================================
-- TABLA: AUDITORIA
-- ============================================================
CREATE TABLE IF NOT EXISTS auditoria (
    id_registro      INT PRIMARY KEY AUTO_INCREMENT,
    nombre_usuario   VARCHAR(50) NOT NULL,
    tabla_afectada   VARCHAR(50) NOT NULL,
    accion           ENUM('INSERT','UPDATE','DELETE') NOT NULL,
    valor_anterior   TEXT,
    valor_nuevo      TEXT,
    fecha_registro   DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_origen        VARCHAR(45),
    INDEX idx_nombre_usuario (nombre_usuario),
    INDEX idx_tabla          (tabla_afectada),
    INDEX idx_fecha_registro (fecha_registro)
) ENGINE=InnoDB;
