<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_TELEMETRIA, ROL_OPERADOR]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $id_alerta = (int)($_POST['id_alerta'] ?? 0);
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';

    if (empty($id_alerta) || !in_array($nuevo_estado, ['Resuelta', 'Descartada'])) {
        header('Location: index.php?error=' . urlencode('Datos inválidos.'));
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE alertas 
            SET estado = ?, fecha_resolucion = NOW() 
            WHERE id_alerta = ? AND estado = 'Activa'
        ");
        $stmt->execute([$nuevo_estado, $id_alerta]);
        
        header('Location: index.php?msg=' . urlencode("Alerta marcada como " . $nuevo_estado));
        exit;
    } catch (PDOException $e) {
        header('Location: index.php?error=' . urlencode("Error de BD: " . $e->getMessage()));
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
