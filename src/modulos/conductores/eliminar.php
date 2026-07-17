<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_OPERADOR]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $dni = trim($_POST['dni'] ?? '');

    if (empty($dni)) {
        header('Location: index.php?error=' . urlencode('DNI de conductor no proporcionado.'));
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM conductores WHERE dni = ?");
        $stmt->execute([$dni]);
        
        header('Location: index.php?msg=' . urlencode("Conductor eliminado exitosamente."));
        exit;
    } catch (PDOException $e) {
        header('Location: index.php?error=' . urlencode("No se puede eliminar el conductor porque tiene registros asociados (turnos, incidencias)."));
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
