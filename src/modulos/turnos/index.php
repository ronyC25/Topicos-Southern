<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_OPERADOR, ROL_CONDUCTOR]);

$es_conductor = ($_SESSION['rol'] === ROL_CONDUCTOR);
$filtro_sql = "";
$params = [];

// Si es conductor, teóricamente solo debería ver sus propios turnos.
// Como no hay relación dura, asumimos que su nombre_usuario = dni.
if ($es_conductor) {
    $filtro_sql = "WHERE t.dni_conductor = ?";
    $params[] = $_SESSION['dni'] ?? '';
}

$sql = "
    SELECT t.*, c.nombre as conductor_nombre, v.placa as vehiculo_placa, v.modelo
    FROM turnos t
    JOIN conductores c ON t.dni_conductor = c.dni
    JOIN vehiculos v ON t.id_camion = v.id_camion
    $filtro_sql
    ORDER BY t.hora_inicio DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$turnos = $stmt->fetchAll();

// Turno activo del conductor logueado: dispara el envío de ubicación (ver script al final).
$turno_activo_conductor = null;
if ($es_conductor) {
    foreach ($turnos as $t) {
        if ($t['estado_turno'] === 'Activo') {
            $turno_activo_conductor = $t;
            break;
        }
    }
}

$titulo_pagina = 'Gestión de Turnos';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
    <h1 class="titulo-modulo" style="margin-bottom: 0;">Turnos Operativos</h1>
    <?php if (!$es_conductor): ?>
        <button class="boton" onclick="abrirModalNuevo()">+ Iniciar Turno</button>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div style="background: #e2f5e9; color: #1c7a3d; padding: 10px; border-radius: 6px; margin-bottom: 15px;">
        <?= e($_GET['msg']) ?>
    </div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div style="background: #fdeaea; color: #a33; padding: 10px; border-radius: 6px; margin-bottom: 15px;">
        <?= e($_GET['error']) ?>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <h2>Turnos registrados</h2>
        <span class="contador"><?= count($turnos) ?> turnos</span>
    </div>
    <div class="panel-cuerpo">
    <table class="tabla">
    <thead>
        <tr>
            <th>ID</th>
            <th>Conductor</th>
            <th>Vehículo</th>
            <th>Inicio / Fin</th>
            <th>Estado</th>
            <th style="text-align: right;">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($turnos)): ?>
            <tr><td colspan="6">No hay turnos registrados.</td></tr>
        <?php else: ?>
            <?php foreach ($turnos as $t): ?>
                <tr>
                    <td data-label="ID"><strong>#<?= e($t['id_turno']) ?></strong></td>
                    <td data-label="Conductor">
                        <?= e($t['conductor_nombre']) ?><br>
                        <small style="color:#667;">DNI: <?= e($t['dni_conductor']) ?></small>
                    </td>
                    <td data-label="Vehículo">
                        <?= e($t['vehiculo_placa']) ?><br>
                        <small style="color:#667;"><?= e($t['id_camion']) ?> (<?= e($t['modelo']) ?>)</small>
                    </td>
                    <td data-label="Inicio / Fin">
                        <small>
                            <strong>Ini:</strong> <?= e($t['hora_inicio']) ?><br>
                            <strong>Fin:</strong> <?= $t['hora_fin'] ? e($t['hora_fin']) : '---' ?>
                        </small>
                    </td>
                    <td data-label="Estado">
                        <?php
                        $clase_estado = [
                            'Activo' => 'badge-verde',
                            'Pausado' => 'badge-amarillo',
                            'Finalizado' => 'badge-gris',
                            'Cancelado' => 'badge-rojo'
                        ][$t['estado_turno']] ?? 'badge-gris';
                        ?>
                        <span class="badge <?= $clase_estado ?>"><?= e($t['estado_turno']) ?></span>
                    </td>
                    <td data-label="Acciones" style="text-align: right;">
                        <?php if ($t['estado_turno'] === 'Activo' || $t['estado_turno'] === 'Pausado'): ?>
                            <button class="boton boton-secundario" style="padding: 5px 10px; font-size: 12px;" onclick='abrirModalDescanso(<?= $t["id_turno"] ?>)'>Descanso</button>
                            <form action="cambiar_estado.php" method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="id_turno" value="<?= e($t['id_turno']) ?>">
                                <?php if ($t['estado_turno'] === 'Activo'): ?>
                                    <input type="hidden" name="nuevo_estado" value="Pausado">
                                    <button type="submit" class="boton" style="background:#f39c12; padding: 5px 10px; font-size: 12px;">Pausar</button>
                                <?php else: ?>
                                    <input type="hidden" name="nuevo_estado" value="Activo">
                                    <button type="submit" class="boton" style="background:#2ecc71; padding: 5px 10px; font-size: 12px;">Reanudar</button>
                                <?php endif; ?>
                            </form>
                            <form action="cambiar_estado.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Finalizar turno?');">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="id_turno" value="<?= e($t['id_turno']) ?>">
                                <input type="hidden" name="nuevo_estado" value="Finalizado">
                                <button type="submit" class="boton" style="background:#34495e; padding: 5px 10px; font-size: 12px;">Finalizar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    </table>
    </div>
</div>

<?php if (!$es_conductor): ?>
<!-- Modal Iniciar Turno -->
<div class="modal-overlay" id="modalForm">
    <div class="modal">
        <form action="guardar.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="modal-header">
                <h2>Iniciar Nuevo Turno</h2>
                <button type="button" class="modal-close" onclick="cerrarModalNuevo()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="grupo-form">
                    <label>Conductor*</label>
                    <select name="dni_conductor" required>
                        <option value="">-- Seleccionar --</option>
                        <?php
                        $cond = $pdo->query("SELECT dni, nombre FROM conductores WHERE estado = 'Activo'")->fetchAll();
                        foreach ($cond as $c) {
                            echo "<option value=\"".e($c['dni'])."\">".e($c['nombre'])." (".e($c['dni']).")</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="grupo-form">
                    <label>Vehículo*</label>
                    <select name="id_camion" required>
                        <option value="">-- Seleccionar --</option>
                        <?php
                        $veh = $pdo->query("SELECT id_camion, placa, modelo FROM vehiculos WHERE estado_operativo = 'Operativo'")->fetchAll();
                        foreach ($veh as $v) {
                            echo "<option value=\"".e($v['id_camion'])."\">".e($v['id_camion'])." - ".e($v['placa'])." (".e($v['modelo']).")</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="boton boton-secundario" onclick="cerrarModalNuevo()">Cancelar</button>
                <button type="submit" class="boton">Iniciar Turno</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal Registrar Descanso -->
<div class="modal-overlay" id="modalDescanso">
    <div class="modal">
        <form action="descansos.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="id_turno" id="formIdTurnoDescanso" value="">

            <div class="modal-header">
                <h2>Registrar Descanso</h2>
                <button type="button" class="modal-close" onclick="cerrarModalDescanso()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="grupo-form">
                    <label>Tipo de Descanso*</label>
                    <select name="tipo" required>
                        <option value="Descanso_Reglamentario">Descanso Reglamentario</option>
                        <option value="Almuerzo">Almuerzo</option>
                        <option value="Descanso_Intermedio">Descanso Intermedio</option>
                    </select>
                </div>
                <div class="grupo-form">
                    <label>Duración (minutos)*</label>
                    <input type="number" name="duracion_minutos" required min="5" max="300" value="60">
                </div>
                <div class="grupo-form">
                    <label>Ubicación</label>
                    <input type="text" name="ubicacion" placeholder="Ej. Comedor Base, Área 5...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="boton boton-secundario" onclick="cerrarModalDescanso()">Cancelar</button>
                <button type="submit" class="boton">Guardar Descanso</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalNuevo() {
    document.getElementById('modalForm').style.display = 'flex';
}
function cerrarModalNuevo() {
    document.getElementById('modalForm').style.display = 'none';
}
function abrirModalDescanso(id_turno) {
    document.getElementById('formIdTurnoDescanso').value = id_turno;
    document.getElementById('modalDescanso').style.display = 'flex';
}
function cerrarModalDescanso() {
    document.getElementById('modalDescanso').style.display = 'none';
}
</script>

<?php if ($es_conductor && $turno_activo_conductor): ?>
<script>
// Envía la ubicación del conductor mientras su turno esté Activo, a intervalos
// (no en cada evento del GPS, para no saturar la tabla telemetria con puntos).
(function () {
    if (!('geolocation' in navigator)) return;

    var csrfToken = '<?= csrf_token() ?>';
    var intervaloMinimoMs = 15000;
    var ultimoEnvio = 0;
    var enviando = false;

    function enviarUbicacion(pos) {
        var ahora = Date.now();
        if (enviando || (ahora - ultimoEnvio) < intervaloMinimoMs) return;
        ultimoEnvio = ahora;
        enviando = true;

        var velocidadKmh = (pos.coords.speed !== null && pos.coords.speed !== undefined)
            ? pos.coords.speed * 3.6
            : 0;

        var datos = new URLSearchParams();
        datos.set('csrf_token', csrfToken);
        datos.set('latitud', pos.coords.latitude);
        datos.set('longitud', pos.coords.longitude);
        datos.set('velocidad_kmh', velocidadKmh);

        fetch('registrar_ubicacion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: datos.toString()
        }).catch(function (err) {
            console.warn('No se pudo enviar la ubicación:', err);
        }).finally(function () {
            enviando = false;
        });
    }

    navigator.geolocation.watchPosition(enviarUbicacion, function (err) {
        console.warn('No se pudo obtener la ubicación del dispositivo:', err.message);
    }, { enableHighAccuracy: true, maximumAge: 10000, timeout: 15000 });
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
