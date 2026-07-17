<?php
/**
 * FleetCore — Procesamiento del login (autenticación dual)
 *
 *   tipo_autenticacion = 'local'             → password_verify() contra MySQL
 *   tipo_autenticacion = 'active_directory'  → ldap_bind() contra spcc.local
 *
 * MODO DESARROLLO (variable de entorno MODO_DESARROLLO=true en Docker):
 *   los usuarios AD se validan con la contraseña de prueba "Desarrollo_2026*"
 *   porque en local no existe el Active Directory. En el servidor real esa
 *   variable no existe y se usa LDAP automáticamente.
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/ldap.php';

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

$nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
$contrasena     = $_POST['contrasena'] ?? '';

if ($nombre_usuario === '' || $contrasena === '') {
    header("Location: ../index.php?error=vacio");
    exit();
}

// 1. Buscar el usuario activo en la BD
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre_usuario = ? AND activo = 1");
$stmt->execute([$nombre_usuario]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header("Location: ../index.php?error=credenciales");
    exit();
}

// 2. Validar la contraseña según el tipo de autenticación
$validado = false;

if ($usuario['tipo_autenticacion'] === AUTH_AD) {

    $modo_desarrollo = getenv('MODO_DESARROLLO') === 'true';

    if ($modo_desarrollo) {
        // Contraseña de prueba para admins AD durante el desarrollo local
        $validado = ($contrasena === 'Desarrollo_2026*');
    } else {
        // Servidor real: validar contra Active Directory
        $validado = validar_ldap($nombre_usuario, $contrasena);
    }

} else {
    // Usuario local: validar contra el hash bcrypt en MySQL
    $validado = ($usuario['contrasena_hash'] !== null)
        && password_verify($contrasena, $usuario['contrasena_hash']);
}

if (!$validado) {
    header("Location: ../index.php?error=credenciales");
    exit();
}

// 3. Autenticación exitosa — crear la sesión
session_regenerate_id(true);   // Previene session fixation

$_SESSION['id_usuario']      = $usuario['id'];
$_SESSION['nombre_usuario']  = $usuario['nombre_usuario'];
$_SESSION['dni']             = $usuario['dni'];
$_SESSION['nombre_completo'] = $usuario['nombre_completo'];
$_SESSION['rol']             = $usuario['rol'];
$_SESSION['ultimo_acceso']   = time();

// Registrar el acceso
$stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
$stmt->execute([$usuario['id']]);

// 4. Redirigir al primer módulo permitido para su rol
$modulos = MENU_POR_ROL[$usuario['rol']] ?? ['perfil'];
header("Location: ../modulos/" . $modulos[0] . "/index.php");
exit();
