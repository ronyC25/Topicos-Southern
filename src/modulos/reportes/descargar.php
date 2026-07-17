<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_ADMIN_BD, ROL_ADMIN_TELEMETRIA, ROL_OPERADOR]);

$tipo_reporte = $_GET['tipo'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

$resultados = [];
$columnas = [];
$nombre_archivo = "reporte_{$tipo_reporte}_" . date('Ymd_His') . ".csv";

if ($tipo_reporte === 'turnos') {
    $stmt = $pdo->prepare("
        SELECT t.id_turno, c.nombre as conductor, v.placa as vehiculo, t.hora_inicio, t.hora_fin, t.estado_turno, t.tiempo_descanso_total
        FROM turnos t
        JOIN conductores c ON t.dni_conductor = c.dni
        JOIN vehiculos v ON t.id_camion = v.id_camion
        WHERE DATE(t.hora_inicio) BETWEEN ? AND ?
        ORDER BY t.hora_inicio DESC
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnas = ['ID Turno', 'Conductor', 'Vehículo', 'Hora Inicio', 'Hora Fin', 'Estado', 'Descansos (min)'];
} elseif ($tipo_reporte === 'mantenimientos') {
    $stmt = $pdo->prepare("
        SELECT m.id_mantenimiento, v.placa as vehiculo, m.tipo_servicio, m.fecha_servicio, m.costo, m.estado
        FROM mantenimientos m
        JOIN vehiculos v ON m.id_camion = v.id_camion
        WHERE m.fecha_servicio BETWEEN ? AND ?
        ORDER BY m.fecha_servicio DESC
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnas = ['ID Mantenimiento', 'Vehículo', 'Servicio', 'Fecha', 'Costo (USD)', 'Estado'];
} elseif ($tipo_reporte === 'incidencias') {
    $stmt = $pdo->prepare("
        SELECT i.id_incidencia, t.id_camion, i.nivel_severidad, i.estado_atencion, i.fecha_reporte
        FROM incidencias i
        JOIN turnos t ON i.id_turno = t.id_turno
        WHERE DATE(i.fecha_reporte) BETWEEN ? AND ?
        ORDER BY i.fecha_reporte DESC
    ");
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnas = ['ID Incidencia', 'Camión', 'Severidad', 'Estado', 'Fecha Reporte'];
} else {
    die("Tipo de reporte inválido.");
}

// Configurar encabezados HTTP para forzar descarga CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $nombre_archivo);

$salida = fopen('php://output', 'w');
// Escribir BOM para Excel (utf-8)
fputs($salida, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

fputcsv($salida, $columnas);

foreach ($resultados as $fila) {
    fputcsv($salida, $fila);
}

fclose($salida);
exit;
