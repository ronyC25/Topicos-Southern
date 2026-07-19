<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_CONDUCTOR]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

validar_csrf();

$latitud = trim($_POST['latitud'] ?? '');
$longitud = trim($_POST['longitud'] ?? '');
$velocidad_kmh = (float)($_POST['velocidad_kmh'] ?? 0);

if (!is_numeric($latitud) || !is_numeric($longitud)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Coordenadas inválidas.']);
    exit;
}

try {
    // El conductor solo puede reportar ubicación si tiene un turno activo:
    // así se sabe a qué id_turno / id_camion asociar el punto GPS.
    $stmt = $pdo->prepare("
        SELECT id_turno, id_camion FROM turnos
        WHERE dni_conductor = ? AND estado_turno = 'Activo'
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['dni'] ?? '']);
    $turno = $stmt->fetch();

    if (!$turno) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'No tienes un turno activo.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO telemetria (id_turno, id_camion, latitud, longitud, velocidad_kmh)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$turno['id_turno'], $turno['id_camion'], (float)$latitud, (float)$longitud, $velocidad_kmh]);

    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    error_log("FleetCore BD [turnos/registrar_ubicacion]: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al procesar la solicitud.']);
}
