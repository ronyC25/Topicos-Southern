<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_ADMIN_BD, ROL_ADMIN_TELEMETRIA, ROL_OPERADOR, ROL_CONDUCTOR]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmacion = $_POST['password_confirmacion'] ?? '';

    if (empty($password_actual) || empty($password_nueva) || empty($password_confirmacion)) {
        header('Location: index.php?error=' . urlencode('Todos los campos son obligatorios.'));
        exit;
    }

    if ($password_nueva !== $password_confirmacion) {
        header('Location: index.php?error=' . urlencode('La nueva contraseña y la confirmación no coinciden.'));
        exit;
    }

    try {
        $id_usuario = $_SESSION['id_usuario'];
        
        // Obtener datos del usuario
        $stmt = $pdo->prepare("SELECT tipo_autenticacion, contrasena_hash FROM usuarios WHERE id = ?");
        $stmt->execute([$id_usuario]);
        $usuario = $stmt->fetch();

        if (!$usuario || $usuario['tipo_autenticacion'] !== AUTH_LOCAL) {
            header('Location: index.php?error=' . urlencode('No puedes cambiar la contraseña de esta cuenta.'));
            exit;
        }

        if (!password_verify($password_actual, $usuario['contrasena_hash'])) {
            header('Location: index.php?error=' . urlencode('La contraseña actual es incorrecta.'));
            exit;
        }

        // Actualizar contraseña
        $hash_nuevo = password_hash($password_nueva, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET contrasena_hash = ? WHERE id = ?");
        $stmt->execute([$hash_nuevo, $id_usuario]);

        header('Location: index.php?msg=' . urlencode("Contraseña actualizada exitosamente."));
        exit;
        
    } catch (PDOException $e) {
        header('Location: index.php?error=' . urlencode("Error de base de datos."));
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
