<?php
/**
 * FleetCore — Control de sesión y acceso por rol
 *
 * TODO módulo debe incluir este archivo en su primera línea y llamar
 * a verificar_rol() con los roles permitidos. Ejemplo:
 *
 *   require_once __DIR__ . '/../../auth/sesion.php';
 *   verificar_rol([ROL_ADMIN_TELEMETRIA, ROL_OPERADOR]);
 */

require_once __DIR__ . '/../config/constantes.php';

// Configuración segura de la cookie de sesión (antes de session_start)
ini_set('session.cookie_httponly', 1);   // No accesible desde JavaScript
ini_set('session.use_strict_mode', 1);   // Rechaza IDs de sesión no generados por el servidor

session_start();

// ---- Timeout por inactividad ----
if (isset($_SESSION['ultimo_acceso'])
    && (time() - $_SESSION['ultimo_acceso'] > SESION_TIMEOUT_SEGUNDOS)) {
    session_unset();
    session_destroy();
    header("Location: " . base_url() . "/index.php?expirado=1");
    exit();
}
$_SESSION['ultimo_acceso'] = time();

/**
 * Devuelve la URL base del sistema (funciona en Docker y en el servidor real).
 */
function base_url(): string {
    // En Docker la app vive en la raíz (/); en el XAMPP real en /fleetcore
    $base = dirname($_SERVER['SCRIPT_NAME']);
    // Subir hasta la raíz del proyecto si estamos dentro de /modulos/xxx
    if (strpos($base, '/modulos/') !== false) {
        $base = substr($base, 0, strpos($base, '/modulos/'));
    } elseif (basename($base) === 'auth') {
        $base = dirname($base);
    }
    return rtrim($base, '/');
}

/**
 * Verifica que exista sesión activa y que el rol esté permitido.
 * Redirige al login o a acceso_denegado según el caso.
 */
function verificar_rol(array $roles_permitidos): void {
    if (!isset($_SESSION['nombre_usuario'])) {
        header("Location: " . base_url() . "/index.php");
        exit();
    }
    if (!in_array($_SESSION['rol'], $roles_permitidos, true)) {
        header("Location: " . base_url() . "/acceso_denegado.php");
        exit();
    }
}

/**
 * Genera (si no existe) y devuelve el token CSRF de la sesión.
 * Usar en todo formulario que modifique datos.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida el token CSRF recibido por POST. Detiene la ejecución si no coincide.
 */
function validar_csrf(): void {
    if (!isset($_POST['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        http_response_code(403);
        die("Solicitud inválida (CSRF).");
    }
}

/**
 * Atajo para escapar salidas HTML (previene XSS).
 */
function e(?string $texto): string {
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}
