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
    ROL_ADMIN_SERVIDOR   => ['dashboard','flota','conductores','turnos','incidencias','mantenimiento','reportes','usuarios','perfil'],
    ROL_ADMIN_BD         => ['dashboard','mantenimiento','reportes','perfil'],
    ROL_ADMIN_TELEMETRIA => ['dashboard','telemetria','alertas','reportes','perfil'],
    ROL_OPERADOR         => ['dashboard','flota','turnos','alertas','incidencias','reportes','perfil'],
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

// ------ Icono y clase visual por rol (usado por includes/header.php) ------
// Iconos de línea monocromos (SVG inline, stroke="currentColor") — no es una
// librería externa, son marcado de confianza generado por la propia app, por
// eso se imprimen sin pasar por e() en header.php.
const ICONOS_ROL = [
    ROL_ADMIN_SERVIDOR   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="6" rx="1"/><rect x="3" y="14" width="18" height="6" rx="1"/><circle cx="7" cy="7" r="0.8" fill="currentColor" stroke="none"/><circle cx="7" cy="17" r="0.8" fill="currentColor" stroke="none"/></svg>',
    ROL_ADMIN_BD         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.66 3.58 3 8 3s8-1.34 8-3V5"/><path d="M4 11v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6"/></svg>',
    ROL_ADMIN_TELEMETRIA => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="20" r="1" fill="currentColor" stroke="none"/><path d="M8.5 16.8a5 5 0 0 1 7 0"/><path d="M5.5 13.5a9 9 0 0 1 13 0"/></svg>',
    ROL_OPERADOR         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="1.5"/><rect x="9" y="2.3" width="6" height="3" rx="1"/><path d="M9 11h6"/><path d="M9 15h6"/></svg>',
    ROL_CONDUCTOR        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="8" width="12" height="8" rx="1"/><path d="M14 11h4l3 3v2h-7"/><circle cx="6.5" cy="18" r="1.6"/><circle cx="16.5" cy="18" r="1.6"/></svg>',
];

// Icono por defecto (rol desconocido) y de cierre de sesión — mismo estilo de línea
const ICONO_USUARIO_DEFECTO = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.2"/><path d="M5 20c0-3.6 3.1-6.4 7-6.4s7 2.8 7 6.4"/></svg>';
const ICONO_SALIR = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>';

// ------ Icono por módulo (usado por includes/sidebar.php, escritorio y tab bar móvil) ------
const ICONOS_MODULO = [
    'dashboard'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/><rect x="13" y="13" width="8" height="8" rx="1"/></svg>',
    'flota'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="8" width="12" height="8" rx="1"/><path d="M14 11h4l3 3v2h-7"/><circle cx="6.5" cy="18" r="1.6"/><circle cx="16.5" cy="18" r="1.6"/></svg>',
    'conductores'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M6 16c0-2 1.5-3 3-3s3 1 3 3"/><path d="M14 9h5"/><path d="M14 13h5"/></svg>',
    'turnos'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>',
    'telemetria'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.1-7-11a7 7 0 0 1 14 0c0 4.9-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>',
    'alertas'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 10a6 6 0 0 1 12 0c0 4 1.5 5.5 1.5 5.5H4.5S6 14 6 10z"/><path d="M10 18a2 2 0 0 0 4 0"/></svg>',
    'incidencias'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3.5 21 19H3z"/><path d="M12 10v4"/><path d="M12 16.5v.01"/></svg>',
    'mantenimiento' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2-2z"/></svg>',
    'reportes'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10"/><path d="M11 20V4"/><path d="M18 20v-7"/></svg>',
    'usuarios'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/><circle cx="17.5" cy="9" r="2.3"/><path d="M15.8 14.8c2.4.4 4.2 2.3 4.2 5.2"/></svg>',
    'perfil'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.2"/><path d="M5 20c0-3.6 3.1-6.4 7-6.4s7 2.8 7 6.4"/></svg>',
];

const CLASES_ROL = [
    ROL_ADMIN_SERVIDOR   => 'rol-admin-servidor',
    ROL_ADMIN_BD         => 'rol-admin-bd',
    ROL_ADMIN_TELEMETRIA => 'rol-admin-telemetria',
    ROL_OPERADOR         => 'rol-operador',
    ROL_CONDUCTOR        => 'rol-conductor',
];
