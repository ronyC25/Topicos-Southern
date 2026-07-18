<?php
/**
 * FleetCore — Módulo Dashboard
 * Este módulo sirve de EJEMPLO del patrón que todo módulo debe seguir:
 *   1. Incluir sesion.php y verificar el rol
 *   2. Incluir conexion.php
 *   3. Lógica del módulo (consultas SIEMPRE preparadas)
 *   4. header.php → contenido HTML → footer.php
 *   5. Toda salida de datos con e() para prevenir XSS
 */
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_ADMIN_BD, ROL_ADMIN_TELEMETRIA, ROL_OPERADOR]);

function formatear_duracion(int $minutos): string {
    $horas = intdiv($minutos, 60);
    $mins  = $minutos % 60;
    return $horas > 0 ? "{$horas}h {$mins}m" : "{$mins}m";
}

// ---- Estado de la flota (señal principal de un dashboard de despacho) ----
$conteo_estados = [
    VEHICULO_OPERATIVO     => 0,
    VEHICULO_MANTENIMIENTO => 0,
    VEHICULO_FUERA         => 0,
];
$stmt = $pdo->query("SELECT estado_operativo, COUNT(*) AS total FROM vehiculos GROUP BY estado_operativo");
foreach ($stmt->fetchAll() as $fila) {
    $conteo_estados[$fila['estado_operativo']] = (int)$fila['total'];
}
$total_vehiculos = array_sum($conteo_estados);

$turnos_activos  = $pdo->query("SELECT COUNT(*) FROM turnos WHERE estado_turno = 'Activo'")->fetchColumn();
$alertas_activas = $pdo->query("SELECT COUNT(*) FROM alertas WHERE estado = 'Activa'")->fetchColumn();

// ---- Turnos en curso ----
$stmt = $pdo->query("
    SELECT c.nombre AS conductor, v.placa, v.id_camion, t.velocidad_promedio,
           TIMESTAMPDIFF(MINUTE, t.hora_inicio, NOW()) AS minutos_transcurridos
    FROM turnos t
    JOIN conductores c ON c.dni = t.dni_conductor
    JOIN vehiculos v ON v.id_camion = t.id_camion
    WHERE t.estado_turno = 'Activo'
    ORDER BY t.hora_inicio DESC
    LIMIT 8
");
$turnos_en_curso = $stmt->fetchAll();

// ---- Últimas alertas ----
$stmt = $pdo->query("
    SELECT a.tipo_alerta, a.nivel, a.descripcion, a.fecha_generacion, v.placa
    FROM alertas a
    JOIN vehiculos v ON v.id_camion = a.id_camion
    WHERE a.estado = 'Activa'
    ORDER BY a.fecha_generacion DESC
    LIMIT 8
");
$ultimas_alertas = $stmt->fetchAll();

// ---- Detalle de flota (lista densa con punto de estado) ----
$stmt = $pdo->query("
    SELECT id_camion, placa, marca, modelo, estado_operativo, kilometraje_total
    FROM vehiculos
    ORDER BY FIELD(estado_operativo, 'Fuera_Servicio', 'Mantenimiento', 'Operativo'), id_camion
    LIMIT 10
");
$detalle_flota = $stmt->fetchAll();

$titulo_pagina = 'Dashboard';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
    <h1 class="titulo-modulo" style="margin-bottom: 0;">Dashboard</h1>
    <span style="font-size:12px; color:#778;">Actualizado: <?= e(date('d/m/Y H:i')) ?></span>
</div>

<h2 style="font-size:14px; margin-bottom:10px; color:#667; text-transform:uppercase; letter-spacing:.4px;">
    Estado de la flota (<?= $total_vehiculos ?> vehículos)
</h2>
<div class="indicadores-estado">
    <div class="indicador-estado indicador-verde">
        <div class="valor"><?= $conteo_estados[VEHICULO_OPERATIVO] ?></div>
        <div class="etiqueta">Operativos</div>
    </div>
    <div class="indicador-estado indicador-amarillo">
        <div class="valor"><?= $conteo_estados[VEHICULO_MANTENIMIENTO] ?></div>
        <div class="etiqueta">En mantenimiento</div>
    </div>
    <div class="indicador-estado indicador-rojo">
        <div class="valor"><?= $conteo_estados[VEHICULO_FUERA] ?></div>
        <div class="etiqueta">Fuera de servicio</div>
    </div>
</div>

<div class="paneles-fila">
    <div class="panel">
        <div class="panel-header">
            <h2>Turnos en curso</h2>
            <span class="contador"><?= (int)$turnos_activos ?> activos</span>
        </div>
        <div class="panel-cuerpo">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Conductor</th>
                        <th>Vehículo</th>
                        <th>Transcurrido</th>
                        <th>Vel. Prom.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($turnos_en_curso)): ?>
                        <tr><td colspan="4">Sin turnos en curso.</td></tr>
                    <?php else: ?>
                        <?php foreach ($turnos_en_curso as $t): ?>
                            <tr>
                                <td><?= e($t['conductor']) ?></td>
                                <td><?= e($t['placa']) ?> <small style="color:#99a;">(<?= e($t['id_camion']) ?>)</small></td>
                                <td><?= e(formatear_duracion((int)$t['minutos_transcurridos'])) ?></td>
                                <td><?= number_format($t['velocidad_promedio'], 1) ?> km/h</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2>Alertas activas</h2>
            <span class="contador<?= $alertas_activas > 0 ? ' contador-alerta' : '' ?>"><?= (int)$alertas_activas ?> activas</span>
        </div>
        <div class="panel-cuerpo">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Tipo</th>
                        <th>Nivel</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ultimas_alertas)): ?>
                        <tr><td colspan="4">Sin alertas activas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ultimas_alertas as $a): ?>
                            <?php
                            $clase_badge = ['Baja'=>'badge-gris','Media'=>'badge-amarillo','Alta'=>'badge-rojo','Critica'=>'badge-rojo'][$a['nivel']] ?? 'badge-gris';
                            $clase_fila  = ['Media'=>'fila-alerta-amarilla','Alta'=>'fila-alerta-roja','Critica'=>'fila-alerta-roja'][$a['nivel']] ?? '';
                            ?>
                            <tr class="<?= $clase_fila ?>">
                                <td><?= e($a['placa']) ?></td>
                                <td><?= e($a['tipo_alerta']) ?></td>
                                <td><span class="badge <?= $clase_badge ?>"><?= e($a['nivel']) ?></span></td>
                                <td><?= e($a['descripcion']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="panel" style="margin-bottom:24px;">
    <div class="panel-header">
        <h2>Estado detallado de la flota</h2>
        <a href="<?= base_url() ?>/modulos/flota/index.php" style="font-size:12px; color:#2c4a7c; text-decoration:none;">Ver flota completa →</a>
    </div>
    <div class="panel-cuerpo">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Vehículo</th>
                    <th>Marca / Modelo</th>
                    <th>Estado</th>
                    <th>Kilometraje</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($detalle_flota)): ?>
                    <tr><td colspan="4">No hay vehículos registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($detalle_flota as $v): ?>
                        <?php
                        $clase_punto = [
                            VEHICULO_OPERATIVO     => 'punto-verde',
                            VEHICULO_MANTENIMIENTO => 'punto-amarillo',
                            VEHICULO_FUERA         => 'punto-rojo',
                        ][$v['estado_operativo']] ?? 'punto-verde';
                        ?>
                        <tr>
                            <td><span class="punto-estado <?= $clase_punto ?>"></span><strong><?= e($v['id_camion']) ?></strong> <small style="color:#99a;"><?= e($v['placa']) ?></small></td>
                            <td><?= e($v['marca']) ?> <?= e($v['modelo']) ?></td>
                            <td><?= e($v['estado_operativo']) ?></td>
                            <td><?= number_format($v['kilometraje_total'], 2) ?> km</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
