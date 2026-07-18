<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_OPERADOR, ROL_CONDUCTOR]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $id_turno = (int)($_POST['id_turno'] ?? 0);
    $tipo = $_POST['tipo'] ?? '';
    $duracion_minutos = (int)($_POST['duracion_minutos'] ?? 0);
    $ubicacion = trim($_POST['ubicacion'] ?? '') ?: null;

    if (empty($id_turno) || empty($tipo) || $duracion_minutos <= 0) {
        header('Location: index.php?error=' . urlencode('Faltan datos requeridos para el descanso.'));
        exit;
    }

    try {
        // En una implementación real, hora_fin se calcularía o se registraría al volver del descanso.
        // Aquí lo simplificamos asumiendo que el descanso se registra de golpe sumando los minutos a hora_inicio.
        $stmt = $pdo->prepare("
            INSERT INTO descansos (id_turno, tipo, hora_inicio, hora_fin, duracion_minutos, ubicacion)
            VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? MINUTE), ?, ?)
        ");
        $stmt->execute([$id_turno, $tipo, $duracion_minutos, $duracion_minutos, $ubicacion]);
        
        // Sumar al total del turno
        $stmt2 = $pdo->prepare("UPDATE turnos SET tiempo_descanso_total = tiempo_descanso_total + ? WHERE id_turno = ?");
        $stmt2->execute([$duracion_minutos, $id_turno]);

        header('Location: index.php?msg=' . urlencode("Descanso registrado exitosamente."));
        exit;
    } catch (PDOException $e) {
        manejar_error_bd($e, 'turnos/descansos');
    }
} else {
    header('Location: index.php');
    exit;
}
