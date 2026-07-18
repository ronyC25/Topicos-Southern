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

<div class="dash-header">
    <div>
        <h1 class="titulo-modulo" style="margin-bottom: 0;">Dashboard</h1>
        <p class="dash-sub">Panel de control — SPCC</p>
    </div>
    <div class="dash-hora" id="dash-reloj"><?= e(date('d/m/Y H:i')) ?></div>
</div>

<div class="dash-section-titulo">
    <span class="dash-live"></span>
    <span>Estado de la flota (<strong><?= $total_vehiculos ?></strong> vehículos)</span>
</div>
<div class="indicadores-estado" id="dash-indicadores">
    <div class="indicador-estado indicador-verde dash-entrada" style="animation-delay:.1s">
        <div class="indicador-icono">
            <svg viewBox="0 0 24 24" fill="none"><path d="M5 13l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/></svg>
        </div>
        <div class="valor" data-contar="<?= $conteo_estados[VEHICULO_OPERATIVO] ?>">0</div>
        <div class="etiqueta">Operativos</div>
    </div>
    <div class="indicador-estado indicador-amarillo dash-entrada" style="animation-delay:.2s">
        <div class="indicador-icono">
            <svg viewBox="0 0 24 24" fill="none"><path d="M12 8v4l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/></svg>
        </div>
        <div class="valor" data-contar="<?= $conteo_estados[VEHICULO_MANTENIMIENTO] ?>">0</div>
        <div class="etiqueta">En mantenimiento</div>
    </div>
    <div class="indicador-estado indicador-rojo dash-entrada" style="animation-delay:.3s">
        <div class="indicador-icono">
            <svg viewBox="0 0 24 24" fill="none"><path d="M15 9l-6 6M9 9l6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/></svg>
        </div>
        <div class="valor" data-contar="<?= $conteo_estados[VEHICULO_FUERA] ?>">0</div>
        <div class="etiqueta">Fuera de servicio</div>
    </div>
</div>

<div class="paneles-fila">
    <div class="panel dash-entrada" style="animation-delay:.4s">
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
                            <tr class="dash-fila">
                                <td><span class="dash-conductor"></span> <?= e($t['conductor']) ?></td>
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

    <div class="panel dash-entrada" style="animation-delay:.5s">
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

<div class="panel dash-entrada" style="animation-delay:.6s; margin-bottom:24px;">
    <div class="panel-header">
        <h2>Estado detallado de la flota</h2>
        <a href="<?= base_url() ?>/modulos/flota/index.php" class="dash-link">Ver flota completa →</a>
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
                        <tr class="dash-fila">
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

<style>
/* ---- Dashboard animations & enhancements ---- */
.dash-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 22px; animation: dap .6s ease-out;
}
.dash-sub { font-size:13px; color:#889; margin-top:2px; }
.dash-hora {
    font-size:12px; color:#778; background:#f0f2f6; padding:6px 14px;
    border-radius:8px; font-variant-numeric: tabular-nums;
    transition: background .3s;
}
.dash-hora:hover { background:#e6eaf0; }

.dash-section-titulo {
    display:flex; align-items:center; gap:10px;
    font-size:13px; color:#667; text-transform:uppercase; letter-spacing:.4px;
    margin-bottom:14px; animation: dap .6s ease-out; animation-delay:.05s; animation-fill-mode:both;
}
.dash-live {
    width:8px; height:8px; border-radius:50%; background:#1c7a3d;
    animation: dashPulso 1.5s ease-in-out infinite; flex-shrink:0;
}
@keyframes dashPulso {
    0%,100% { box-shadow: 0 0 0 0 rgba(28,122,61,.4); }
    50%     { box-shadow: 0 0 0 5px rgba(28,122,61,0); }
}

/* Entrance */
.dash-entrada {
    animation: dap .6s ease-out both;
}
@keyframes dap {
    from { opacity:0; transform:translateY(20px); }
    to   { opacity:1; transform:translateY(0); }
}

/* Indicator icons */
.indicador-icono {
    width:28px; height:28px; margin-bottom:6px;
}
.indicador-icono svg { width:100%; height:100%; }

/* Enhanced indicators */
.indicador-estado {
    position:relative; overflow:hidden;
    transition:transform .3s, box-shadow .3s;
}
.indicador-estado:hover {
    transform:translateY(-3px) scale(1.02);
    box-shadow:0 8px 24px rgba(0,0,0,.1);
}

/* Panel hover */
.panel {
    transition:transform .35s, box-shadow .35s;
}
.panel:hover {
    transform:translateY(-2px);
    box-shadow:0 8px 28px rgba(0,0,0,.08);
}

/* Table row hover */
.dash-fila {
    transition:background .2s;
}
.dash-fila:hover td {
    background:#f8faff !important;
}

/* Conductor dot */
.dash-conductor {
    display:inline-block; width:7px; height:7px; border-radius:50%;
    background:#1c7a3d; margin-right:6px; vertical-align:middle;
}

/* Link stylish */
.dash-link {
    font-size:12px; color:#2c4a7c; text-decoration:none;
    transition:color .2s, transform .2s; display:inline-flex; align-items:center; gap:4px;
}
.dash-link:hover { color:#5b8dee; transform:translateX(3px); }

/* Counter animation */
@keyframes contarSube {
    from { opacity:0; transform:translateY(8px); }
    to   { opacity:1; transform:translateY(0); }
}
</style>

<script>
(function(){
    var d = document;

    // ---- Live clock ----
    var reloj = d.getElementById('dash-reloj');
    if (reloj) {
        function actualizarHora() {
            var a = new Date();
            reloj.textContent =
                String(a.getDate()).padStart(2,'0') + '/' +
                String(a.getMonth()+1).padStart(2,'0') + '/' +
                a.getFullYear() + ' ' +
                String(a.getHours()).padStart(2,'0') + ':' +
                String(a.getMinutes()).padStart(2,'0');
        }
        setInterval(actualizarHora, 10000);
    }

    // ---- Counter animation ----
    var indicadores = d.getElementById('dash-indicadores');
    if (indicadores) {
        var vals = indicadores.querySelectorAll('.valor[data-contar]');
        vals.forEach(function(el) {
            var target = parseInt(el.getAttribute('data-contar'), 10);
            if (isNaN(target) || target === 0) { el.textContent = target; return; }
            var dur = 600, paso = Math.ceil(target / 30);
            var actual = 0;
            var step = function() {
                actual += paso;
                if (actual >= target) { el.textContent = target; return; }
                el.textContent = actual;
                setTimeout(step, dur / 30);
            };
            setTimeout(step, 400);
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
