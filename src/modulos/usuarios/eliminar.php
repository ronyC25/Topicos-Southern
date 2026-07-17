<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $id = trim($_POST['id'] ?? '');

    if (empty($id)) {
        header('Location: index.php?error=' . urlencode('ID de usuario no proporcionado.'));
        exit;
    }

    try {
        // Verificar que no se esté eliminando a sí mismo
        $stmt = $pdo->prepare("SELECT nombre_usuario FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        
        if ($u && $u['nombre_usuario'] === $_SESSION['nombre_usuario']) {
            header('Location: index.php?error=' . urlencode('No puedes eliminar tu propia cuenta.'));
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        
        header('Location: index.php?msg=' . urlencode("Usuario eliminado exitosamente."));
        exit;
    } catch (PDOException $e) {
        header('Location: index.php?error=' . urlencode("No se puede eliminar el usuario porque tiene registros de auditoría o incidencias asociadas. Considere desactivarlo (estado Inactivo) en su lugar."));
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
