<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_ADMIN_BD]);

// Obtener Mantenimientos
$stmt = $pdo->query("SELECT m.*, v.placa FROM mantenimientos m JOIN vehiculos v ON m.id_camion = v.id_camion ORDER BY m.fecha_creacion DESC LIMIT 50");
$mantenimientos = $stmt->fetchAll();

// Obtener Tickets
$stmt2 = $pdo->query("SELECT t.*, v.placa FROM tickets_atencion t JOIN vehiculos v ON t.id_camion = v.id_camion ORDER BY t.fecha_creacion DESC LIMIT 50");
$tickets = $stmt2->fetchAll();

$titulo_pagina = 'Mantenimiento de Flota';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
    <h1 class="titulo-modulo" style="margin-bottom: 0;">Mantenimientos y Tickets</h1>
    <div>
        <button class="boton boton-secundario" onclick="abrirModalTicket()">+ Nuevo Ticket</button>
        <button class="boton" onclick="abrirModalMant()">+ Nuevo Mantenimiento</button>
    </div>
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

<div class="panel" style="margin-bottom:24px;">
    <div class="panel-header">
        <h2>Registros de Mantenimiento</h2>
        <span class="contador"><?= count($mantenimientos) ?> registros</span>
    </div>
    <div class="panel-cuerpo">
    <table class="tabla">
    <thead>
        <tr>
            <th>ID / Camión</th>
            <th>Servicio</th>
            <th>Fecha Programada</th>
            <th>Costo (USD)</th>
            <th>Estado</th>
            <th style="text-align: right;">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($mantenimientos)): ?>
            <tr><td colspan="6">No hay mantenimientos registrados.</td></tr>
        <?php else: ?>
            <?php foreach ($mantenimientos as $m): ?>
                <tr>
                    <td>
                        <strong>#<?= e($m['id_mantenimiento']) ?></strong><br>
                        <small style="color:#667;"><?= e($m['id_camion']) ?> (<?= e($m['placa']) ?>)</small>
                    </td>
                    <td>
                        <?= e($m['tipo_servicio']) ?><br>
                        <small style="color:#667;">Técnico: <?= e($m['tecnico_responsable'] ?: 'No asignado') ?></small>
                    </td>
                    <td><?= e($m['fecha_servicio']) ?></td>
                    <td><?= number_format($m['costo'] ?: 0, 2) ?></td>
                    <td>
                        <?php
                        $clase_estado = [
                            'Pendiente' => 'badge-rojo',
                            'En_Proceso' => 'badge-amarillo',
                            'Completado' => 'badge-verde',
                            'Cancelado' => 'badge-gris'
                        ][$m['estado']] ?? 'badge-gris';
                        ?>
                        <span class="badge <?= $clase_estado ?>"><?= e($m['estado']) ?></span>
                    </td>
                    <td style="text-align: right;">
                        <?php if ($m['estado'] === 'Pendiente'): ?>
                            <form action="guardar.php" method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="id_mantenimiento" value="<?= e($m['id_mantenimiento']) ?>">
                                <input type="hidden" name="accion" value="estado">
                                <input type="hidden" name="nuevo_estado" value="En_Proceso">
                                <button type="submit" class="boton boton-secundario" style="padding: 5px 10px; font-size: 12px;">Iniciar</button>
                            </form>
                        <?php elseif ($m['estado'] === 'En_Proceso'): ?>
                            <form action="guardar.php" method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="id_mantenimiento" value="<?= e($m['id_mantenimiento']) ?>">
                                <input type="hidden" name="accion" value="estado">
                                <input type="hidden" name="nuevo_estado" value="Completado">
                                <button type="submit" class="boton" style="background:#2ecc71; padding: 5px 10px; font-size: 12px;">Completar</button>
                            </form>
                        <?php else: ?>
                            <span style="color:#99a; font-size:12px;">Cerrado</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    </table>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Tickets de Atención</h2>
        <span class="contador"><?= count($tickets) ?> tickets</span>
    </div>
    <div class="panel-cuerpo">
    <table class="tabla">
    <thead>
        <tr>
            <th>Ticket / Camión</th>
            <th>Tipo / Prioridad</th>
            <th>Descripción</th>
            <th>Estado</th>
            <th style="text-align: right;">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($tickets)): ?>
            <tr><td colspan="5">No hay tickets activos.</td></tr>
        <?php else: ?>
            <?php foreach ($tickets as $t): ?>
                <tr>
                    <td>
                        <strong>#<?= e($t['id_ticket']) ?></strong><br>
                        <small style="color:#667;"><?= e($t['id_camion']) ?></small>
                    </td>
                    <td>
                        <?= e($t['tipo']) ?><br>
                        <?php
                        $clase_pri = [
                            'Baja' => 'badge-verde',
                            'Media' => 'badge-amarillo',
                            'Alta' => 'badge-rojo',
                            'Critica' => 'badge-rojo'
                        ][$t['prioridad']] ?? 'badge-gris';
                        ?>
                        <span class="badge <?= $clase_pri ?>"><?= e($t['prioridad']) ?></span>
                    </td>
                    <td><?= e($t['descripcion']) ?></td>
                    <td>
                        <?php
                        $clase_est2 = [
                            'Abierto' => 'badge-rojo',
                            'En_Proceso' => 'badge-amarillo',
                            'Resuelto' => 'badge-verde',
                            'Cerrado' => 'badge-gris'
                        ][$t['estado']] ?? 'badge-gris';
                        ?>
                        <span class="badge <?= $clase_est2 ?>"><?= e($t['estado']) ?></span>
                    </td>
                    <td style="text-align: right;">
                        <?php if ($t['estado'] !== 'Cerrado' && $t['estado'] !== 'Resuelto'): ?>
                            <form action="ticket.php" method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="id_ticket" value="<?= e($t['id_ticket']) ?>">
                                <input type="hidden" name="accion" value="estado">
                                <input type="hidden" name="nuevo_estado" value="Resuelto">
                                <button type="submit" class="boton" style="background:#2ecc71; padding: 5px 10px; font-size: 12px;">Resolver</button>
                            </form>
                        <?php else: ?>
                            <span style="color:#99a; font-size:12px;">Cerrado</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    </table>
    </div>
</div>

<!-- Modal Nuevo Mantenimiento -->
<div class="modal-overlay" id="modalMant">
    <div class="modal">
        <form action="guardar.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="accion" value="crear">

            <div class="modal-header">
                <h2>Programar Mantenimiento</h2>
                <button type="button" class="modal-close" onclick="cerrarModalMant()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="grupo-form">
                    <label>Vehículo*</label>
                    <select name="id_camion" required>
                        <option value="">-- Seleccionar --</option>
                        <?php
                        $veh = $pdo->query("SELECT id_camion, placa FROM vehiculos")->fetchAll();
                        foreach ($veh as $v) {
                            echo "<option value=\"".e($v['id_camion'])."\">".e($v['id_camion'])." - ".e($v['placa'])."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="grupo-form">
                        <label>Tipo de Servicio*</label>
                        <select name="tipo_servicio" required>
                            <option value="Preventivo">Preventivo</option>
                            <option value="Correctivo">Correctivo</option>
                            <option value="Programado">Programado</option>
                        </select>
                    </div>
                    <div class="grupo-form">
                        <label>Fecha Programada*</label>
                        <input type="date" name="fecha_servicio" required>
                    </div>
                    <div class="grupo-form">
                        <label>Costo Estimado (USD)</label>
                        <input type="number" step="0.01" name="costo">
                    </div>
                    <div class="grupo-form">
                        <label>Técnico Responsable</label>
                        <input type="text" name="tecnico_responsable" maxlength="100">
                    </div>
                </div>
                <div class="grupo-form">
                    <label>Descripción General*</label>
                    <textarea name="descripcion" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="boton boton-secundario" onclick="cerrarModalMant()">Cancelar</button>
                <button type="submit" class="boton">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Nuevo Ticket -->
<div class="modal-overlay" id="modalTicket">
    <div class="modal">
        <form action="ticket.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="accion" value="crear">

            <div class="modal-header">
                <h2>Crear Ticket de Atención</h2>
                <button type="button" class="modal-close" onclick="cerrarModalTicket()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="grupo-form">
                    <label>Vehículo*</label>
                    <select name="id_camion" required>
                        <option value="">-- Seleccionar --</option>
                        <?php
                        foreach ($veh as $v) {
                            echo "<option value=\"".e($v['id_camion'])."\">".e($v['id_camion'])." - ".e($v['placa'])."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="grupo-form">
                        <label>Tipo*</label>
                        <select name="tipo" required>
                            <option value="Mantenimiento">Mantenimiento</option>
                            <option value="Reparacion">Reparación</option>
                            <option value="Inspeccion">Inspección</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="grupo-form">
                        <label>Prioridad*</label>
                        <select name="prioridad" required>
                            <option value="Baja">Baja</option>
                            <option value="Media">Media</option>
                            <option value="Alta">Alta</option>
                            <option value="Critica">Crítica</option>
                        </select>
                    </div>
                </div>
                <div class="grupo-form">
                    <label>Problema reportado*</label>
                    <textarea name="descripcion" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="boton boton-secundario" onclick="cerrarModalTicket()">Cancelar</button>
                <button type="submit" class="boton">Abrir Ticket</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalMant() {
    document.getElementById('modalMant').style.display = 'flex';
}
function cerrarModalMant() {
    document.getElementById('modalMant').style.display = 'none';
}
function abrirModalTicket() {
    document.getElementById('modalTicket').style.display = 'flex';
}
function cerrarModalTicket() {
    document.getElementById('modalTicket').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
