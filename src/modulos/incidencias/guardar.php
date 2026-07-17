<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_OPERADOR, ROL_CONDUCTOR]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $id_turno = (int)($_POST['id_turno'] ?? 0);
    $nivel_severidad = $_POST['nivel_severidad'] ?? 'Baja';
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (empty($id_turno) || empty($descripcion)) {
        header('Location: index.php?error=' . urlencode('Faltan datos requeridos.'));
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO incidencias (id_turno, reportado_por, descripcion, nivel_severidad, estado_atencion)
            VALUES (?, ?, ?, ?, 'Pendiente')
        ");
        $stmt->execute([$id_turno, $_SESSION['nombre_usuario'], $descripcion, $nivel_severidad]);
        
        header('Location: index.php?msg=' . urlencode("Incidencia reportada exitosamente."));
        exit;
    } catch (PDOException $e) {
        header('Location: index.php?error=' . urlencode("Error de BD: " . $e->getMessage()));
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
