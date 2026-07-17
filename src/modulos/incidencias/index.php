<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_OPERADOR, ROL_CONDUCTOR]);

$es_conductor = ($_SESSION['rol'] === ROL_CONDUCTOR);
$filtro_sql = "";
$params = [];

// Conductores solo ven incidencias de sus turnos
if ($es_conductor) {
    $filtro_sql = "WHERE t.dni_conductor = ?";
    $params[] = $_SESSION['nombre_usuario'];
}

$sql = "
    SELECT i.*, t.id_camion, c.nombre as conductor_nombre
    FROM incidencias i
    JOIN turnos t ON i.id_turno = t.id_turno
    JOIN conductores c ON t.dni_conductor = c.dni
    $filtro_sql
    ORDER BY i.fecha_reporte DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$incidencias = $stmt->fetchAll();

$titulo_pagina = 'Gestión de Incidencias';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
    <h1 class="titulo-modulo" style="margin-bottom: 0;">Incidencias Operativas</h1>
    <button class="boton" onclick="abrirModalNuevo()">+ Reportar Incidencia</button>
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

<table class="tabla">
    <thead>
        <tr>
            <th>ID / Turno</th>
            <th>Detalles</th>
            <th>Severidad</th>
            <th>Estado</th>
            <th style="text-align: right;">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($incidencias)): ?>
            <tr><td colspan="5">No hay incidencias registradas.</td></tr>
        <?php else: ?>
            <?php foreach ($incidencias as $i): ?>
                <tr>
                    <td>
                        <strong>#<?= e($i['id_incidencia']) ?></strong><br>
                        <small style="color:#667;">Turno #<?= e($i['id_turno']) ?><br>Camión: <?= e($i['id_camion']) ?></small>
                    </td>
                    <td>
                        <small style="color:#667;"><?= e($i['fecha_reporte']) ?> por <?= e($i['reportado_por']) ?></small><br>
                        <?= e($i['descripcion']) ?>
                    </td>
                    <td>
                        <?php
                        $clase_sev = [
                            'Baja' => 'badge-verde',
                            'Media' => 'badge-amarillo',
                            'Alta' => 'badge-rojo',
                            'Critica' => 'badge-rojo'
                        ][$i['nivel_severidad']] ?? 'badge-gris';
                        ?>
                        <span class="badge <?= $clase_sev ?>"><?= e($i['nivel_severidad']) ?></span>
                    </td>
                    <td>
                        <?php
                        $clase_est = [
                            'Pendiente' => 'badge-rojo',
                            'En_Revision' => 'badge-amarillo',
                            'Resuelta' => 'badge-verde',
                            'Cerrada' => 'badge-gris'
                        ][$i['estado_atencion']] ?? 'badge-gris';
                        ?>
                        <span class="badge <?= $clase_est ?>"><?= e($i['estado_atencion']) ?></span>
                    </td>
                    <td style="text-align: right;">
                        <?php if (!$es_conductor && $i['estado_atencion'] !== 'Cerrada'): ?>
                            <button class="boton boton-secundario" style="padding: 5px 10px; font-size: 12px;" onclick='abrirModalEstado(<?= $i["id_incidencia"] ?>, "<?= e($i["estado_atencion"]) ?>")'>Actualizar Estado</button>
                        <?php else: ?>
                            <span style="color:#99a; font-size:12px;">Sin acciones</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Modal Nueva Incidencia -->
<div class="modal-overlay" id="modalForm">
    <div class="modal">
        <form action="guardar.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="modal-header">
                <h2>Reportar Incidencia</h2>
                <button type="button" class="modal-close" onclick="cerrarModalNuevo()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="grupo-form">
                    <label>Turno Activo*</label>
                    <select name="id_turno" required>
                        <option value="">-- Seleccionar Turno --</option>
                        <?php
                        $sql_turnos = "SELECT t.id_turno, t.id_camion, c.nombre FROM turnos t JOIN conductores c ON t.dni_conductor = c.dni WHERE t.estado_turno IN ('Activo', 'Pausado')";
                        $params_turnos = [];
                        if ($es_conductor) {
                            $sql_turnos .= " AND t.dni_conductor = ?";
                            $params_turnos[] = $_SESSION['nombre_usuario'];
                        }
                        $stmt = $pdo->prepare($sql_turnos);
                        $stmt->execute($params_turnos);
                        $turnos_activos = $stmt->fetchAll();
                        
                        foreach ($turnos_activos as $t) {
                            echo "<option value=\"".e($t['id_turno'])."\">Turno #".e($t['id_turno'])." - ".e($t['id_camion'])." (".e($t['nombre']).")</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="grupo-form">
                    <label>Severidad*</label>
                    <select name="nivel_severidad" required>
                        <option value="Baja">Baja</option>
                        <option value="Media">Media</option>
                        <option value="Alta">Alta</option>
                        <option value="Critica">Crítica</option>
                    </select>
                </div>
                <div class="grupo-form">
                    <label>Descripción detallada*</label>
                    <textarea name="descripcion" rows="4" required placeholder="Describe lo sucedido..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="boton boton-secundario" onclick="cerrarModalNuevo()">Cancelar</button>
                <button type="submit" class="boton">Guardar Reporte</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cambiar Estado -->
<div class="modal-overlay" id="modalEstado">
    <div class="modal">
        <form action="cambiar_estado.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="id_incidencia" id="formIdIncidencia" value="">

            <div class="modal-header">
                <h2>Actualizar Estado</h2>
                <button type="button" class="modal-close" onclick="cerrarModalEstado()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="grupo-form">
                    <label>Nuevo Estado*</label>
                    <select name="estado_atencion" id="formEstadoIncidencia" required>
                        <option value="Pendiente">Pendiente</option>
                        <option value="En_Revision">En Revisión</option>
                        <option value="Resuelta">Resuelta</option>
                        <option value="Cerrada">Cerrada</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="boton boton-secundario" onclick="cerrarModalEstado()">Cancelar</button>
                <button type="submit" class="boton">Actualizar</button>
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
function abrirModalEstado(id, estado_actual) {
    document.getElementById('formIdIncidencia').value = id;
    document.getElementById('formEstadoIncidencia').value = estado_actual;
    document.getElementById('modalEstado').style.display = 'flex';
}
function cerrarModalEstado() {
    document.getElementById('modalEstado').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
