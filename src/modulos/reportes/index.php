<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_ADMIN_BD, ROL_ADMIN_TELEMETRIA, ROL_OPERADOR]);

$tipo_reporte = $_GET['tipo'] ?? 'turnos';
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

$resultados = [];
$columnas = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['generar'])) {
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
    }
}

$titulo_pagina = 'Centro de Reportes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
    <h1 class="titulo-modulo" style="margin-bottom: 0;">Centro de Análisis y Reportes</h1>
</div>

<div class="tarjeta" style="margin-bottom: 20px;">
    <form method="GET" action="index.php" style="display: flex; gap: 15px; align-items: flex-end;">
        <div class="grupo-form" style="margin-bottom: 0; flex: 1;">
            <label>Tipo de Reporte</label>
            <select name="tipo">
                <option value="turnos" <?= $tipo_reporte === 'turnos' ? 'selected' : '' ?>>Reporte de Turnos</option>
                <option value="mantenimientos" <?= $tipo_reporte === 'mantenimientos' ? 'selected' : '' ?>>Reporte de Mantenimientos</option>
                <option value="incidencias" <?= $tipo_reporte === 'incidencias' ? 'selected' : '' ?>>Reporte de Incidencias</option>
            </select>
        </div>
        <div class="grupo-form" style="margin-bottom: 0; flex: 1;">
            <label>Fecha Inicio</label>
            <input type="date" name="fecha_inicio" value="<?= e($fecha_inicio) ?>" required>
        </div>
        <div class="grupo-form" style="margin-bottom: 0; flex: 1;">
            <label>Fecha Fin</label>
            <input type="date" name="fecha_fin" value="<?= e($fecha_fin) ?>" required>
        </div>
        <div style="flex: 1; display: flex; gap: 10px;">
            <button type="submit" name="generar" value="1" class="boton" style="flex: 1;">Consultar</button>
            <?php if (!empty($resultados)): ?>
                <a href="descargar.php?tipo=<?= $tipo_reporte ?>&fecha_inicio=<?= $fecha_inicio ?>&fecha_fin=<?= $fecha_fin ?>" class="boton boton-secundario" style="flex: 1; text-align: center; text-decoration: none;" target="_blank">Exportar CSV</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if (isset($_GET['generar'])): ?>
    <?php if (empty($resultados)): ?>
        <p>No se encontraron datos para el rango seleccionado.</p>
    <?php else: ?>
        <table class="tabla">
            <thead>
                <tr>
                    <?php foreach ($columnas as $col): ?>
                        <th><?= e($col) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados as $fila): ?>
                    <tr>
                        <?php foreach ($fila as $valor): ?>
                            <td><?= e((string)$valor) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
