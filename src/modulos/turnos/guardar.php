<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_OPERADOR]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $dni_conductor = trim($_POST['dni_conductor'] ?? '');
    $id_camion = trim($_POST['id_camion'] ?? '');

    if (empty($dni_conductor) || empty($id_camion)) {
        header('Location: index.php?error=' . urlencode('Faltan datos requeridos.'));
        exit;
    }

    try {
        // Verificar que el conductor no tenga un turno activo
        $stmt = $pdo->prepare("SELECT id_turno FROM turnos WHERE dni_conductor = ? AND estado_turno IN ('Activo', 'Pausado')");
        $stmt->execute([$dni_conductor]);
        if ($stmt->fetch()) {
            header('Location: index.php?error=' . urlencode('El conductor ya tiene un turno en curso.'));
            exit;
        }

        // Verificar que el camión no esté en otro turno activo
        $stmt = $pdo->prepare("SELECT id_turno FROM turnos WHERE id_camion = ? AND estado_turno IN ('Activo', 'Pausado')");
        $stmt->execute([$id_camion]);
        if ($stmt->fetch()) {
            header('Location: index.php?error=' . urlencode('El vehículo ya está asignado a un turno en curso.'));
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO turnos (dni_conductor, id_camion, hora_inicio, estado_turno)
            VALUES (?, ?, NOW(), 'Activo')
        ");
        $stmt->execute([$dni_conductor, $id_camion]);
        
        header('Location: index.php?msg=' . urlencode("Turno iniciado exitosamente."));
        exit;
    } catch (PDOException $e) {
        header('Location: index.php?error=' . urlencode("Error de BD: " . $e->getMessage()));
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
