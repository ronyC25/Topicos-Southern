<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_OPERADOR, ROL_CONDUCTOR]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $id_turno = (int)($_POST['id_turno'] ?? 0);
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';

    if (empty($id_turno) || !in_array($nuevo_estado, ['Activo', 'Pausado', 'Finalizado', 'Cancelado'])) {
        header('Location: index.php?error=' . urlencode('Datos inválidos.'));
        exit;
    }

    try {
        $sql = "UPDATE turnos SET estado_turno = ?";
        $params = [$nuevo_estado];

        if ($nuevo_estado === 'Finalizado' || $nuevo_estado === 'Cancelado') {
            $sql .= ", hora_fin = NOW()";
        }

        $sql .= " WHERE id_turno = ?";
        $params[] = $id_turno;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        header('Location: index.php?msg=' . urlencode("Estado del turno actualizado a " . $nuevo_estado));
        exit;
    } catch (PDOException $e) {
        header('Location: index.php?error=' . urlencode("Error de BD: " . $e->getMessage()));
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
