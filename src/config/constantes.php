<?php
/**
 * FleetCore — Constantes del sistema
 * Los valores deben coincidir EXACTAMENTE con los ENUM de la BD v3.
 */

// ------ Roles (tabla usuarios, columna rol) ------
const ROL_ADMIN_SERVIDOR   = 'Admin_Servidor';
const ROL_ADMIN_BD         = 'Admin_BD';
const ROL_ADMIN_TELEMETRIA = 'Admin_Telemetria';
const ROL_OPERADOR         = 'Operador';
const ROL_CONDUCTOR        = 'Conductor';

// ------ Tipos de autenticación (tabla usuarios) ------
const AUTH_LOCAL = 'local';
const AUTH_AD    = 'active_directory';

// ------ Estados de turno ------
const TURNO_ACTIVO     = 'Activo';
const TURNO_PAUSADO    = 'Pausado';
const TURNO_FINALIZADO = 'Finalizado';
const TURNO_CANCELADO  = 'Cancelado';

// ------ Estados de vehículo ------
const VEHICULO_OPERATIVO     = 'Operativo';
const VEHICULO_MANTENIMIENTO = 'Mantenimiento';
const VEHICULO_FUERA         = 'Fuera_Servicio';

// ------ Niveles de alerta / severidad ------
const NIVEL_BAJA    = 'Baja';
const NIVEL_MEDIA   = 'Media';
const NIVEL_ALTA    = 'Alta';
const NIVEL_CRITICA = 'Critica';

// ------ Sesión ------
const SESION_TIMEOUT_SEGUNDOS = 1800;   // 30 minutos de inactividad

// ------ LDAP / Active Directory (etapa final, servidor real) ------
const LDAP_SERVIDOR = 'ldap://192.168.10.10';
const LDAP_DOMINIO  = 'spcc.local';

// ------ Módulos visibles por rol (usado por includes/sidebar.php) ------
const MENU_POR_ROL = [
    ROL_ADMIN_SERVIDOR   => ['dashboard','flota','conductores','mantenimiento','reportes','usuarios','perfil'],
    ROL_ADMIN_BD         => ['dashboard','mantenimiento','reportes','perfil'],
    ROL_ADMIN_TELEMETRIA => ['dashboard','telemetria','alertas','reportes','perfil'],
    ROL_OPERADOR         => ['dashboard','flota','turnos','alertas','incidencias','perfil'],
    ROL_CONDUCTOR        => ['turnos','incidencias','perfil'],
];

// ------ Nombres legibles de los módulos (para el menú) ------
const NOMBRES_MODULOS = [
    'dashboard'     => 'Dashboard',
    'flota'         => 'Flota',
    'conductores'   => 'Conductores',
    'turnos'        => 'Turnos',
    'telemetria'    => 'Telemetría',
    'alertas'       => 'Alertas',
    'incidencias'   => 'Incidencias',
    'mantenimiento' => 'Mantenimiento',
    'reportes'      => 'Reportes',
    'usuarios'      => 'Usuarios',
    'perfil'        => 'Mi Perfil',
];
