<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_OPERADOR]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $id_camion = trim($_POST['id_camion'] ?? '');

    if (empty($id_camion)) {
        header('Location: index.php?error=' . urlencode('ID de vehículo no proporcionado.'));
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM vehiculos WHERE id_camion = ?");
        $stmt->execute([$id_camion]);
        
        header('Location: index.php?msg=' . urlencode("Vehículo eliminado exitosamente."));
        exit;
    } catch (PDOException $e) {
        // En caso de que el vehículo tenga dependencias (turnos, telemetría, etc)
        // La restricción de clave foránea podría prevenir la eliminación.
        header('Location: index.php?error=' . urlencode("No se puede eliminar el vehículo porque tiene registros asociados (turnos, incidencias, etc)."));
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
