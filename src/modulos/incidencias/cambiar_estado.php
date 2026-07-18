<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

// Los conductores no pueden cambiar el estado de la incidencia, solo reportar
verificar_rol([ROL_ADMIN_SERVIDOR, ROL_OPERADOR]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $id_incidencia = (int)($_POST['id_incidencia'] ?? 0);
    $estado_atencion = $_POST['estado_atencion'] ?? '';

    if (empty($id_incidencia) || empty($estado_atencion)) {
        header('Location: index.php?error=' . urlencode('Datos inválidos.'));
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE incidencias SET estado_atencion = ? WHERE id_incidencia = ?");
        $stmt->execute([$estado_atencion, $id_incidencia]);
        
        header('Location: index.php?msg=' . urlencode("Estado de la incidencia actualizado."));
        exit;
    } catch (PDOException $e) {
        manejar_error_bd($e, 'incidencias/cambiar_estado');
    }
} else {
    header('Location: index.php');
    exit;
}
