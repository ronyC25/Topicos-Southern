<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_TELEMETRIA, ROL_OPERADOR]);

// Filtro por estado
$estado_filtro = $_GET['estado'] ?? 'Activa';

$sql = "
    SELECT a.*, v.placa, c.nombre as conductor
    FROM alertas a
    JOIN vehiculos v ON a.id_camion = v.id_camion
    LEFT JOIN turnos t ON a.id_turno = t.id_turno
    LEFT JOIN conductores c ON t.dni_conductor = c.dni
";

$params = [];
if ($estado_filtro !== 'Todas') {
    $sql .= " WHERE a.estado = ?";
    $params[] = $estado_filtro;
}
$sql .= " ORDER BY a.fecha_generacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alertas = $stmt->fetchAll();

$titulo_pagina = 'Gestión de Alertas';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
    <h1 class="titulo-modulo" style="margin-bottom: 0;">Bandeja de Alertas</h1>
    
    <div>
        <form method="GET" style="display: inline-block;">
            <select name="estado" onchange="this.form.submit()" style="padding: 6px; border-radius: 4px; border: 1px solid #ccc;">
                <option value="Activa" <?= $estado_filtro === 'Activa' ? 'selected' : '' ?>>Solo Activas</option>
                <option value="Todas" <?= $estado_filtro === 'Todas' ? 'selected' : '' ?>>Todas</option>
                <option value="Resuelta" <?= $estado_filtro === 'Resuelta' ? 'selected' : '' ?>>Resueltas</option>
                <option value="Descartada" <?= $estado_filtro === 'Descartada' ? 'selected' : '' ?>>Descartadas</option>
            </select>
        </form>
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

<table class="tabla">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Camión / Turno</th>
            <th>Tipo / Nivel</th>
            <th>Descripción</th>
            <th>Estado</th>
            <th style="text-align: right;">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($alertas)): ?>
            <tr><td colspan="6">No hay alertas en esta vista.</td></tr>
        <?php else: ?>
            <?php foreach ($alertas as $a): ?>
                <tr>
                    <td>
                        <small>
                            Gen: <?= e($a['fecha_generacion']) ?><br>
                            <?= $a['fecha_resolucion'] ? 'Res: ' . e($a['fecha_resolucion']) : '' ?>
                        </small>
                    </td>
                    <td>
                        <strong><?= e($a['id_camion']) ?></strong> (<?= e($a['placa']) ?>)<br>
                        <?php if ($a['id_turno']): ?>
                            <small style="color:#667;">Turno #<?= e($a['id_turno']) ?> (<?= e($a['conductor']) ?>)</small>
                        <?php else: ?>
                            <small style="color:#99a;">Sin turno activo</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= e($a['tipo_alerta']) ?><br>
                        <?php
                        $clase_nivel = [
                            'Baja' => 'badge-verde',
                            'Media' => 'badge-amarillo',
                            'Alta' => 'badge-rojo',
                            'Critica' => 'badge-rojo'
                        ][$a['nivel']] ?? 'badge-gris';
                        ?>
                        <span class="badge <?= $clase_nivel ?>"><?= e($a['nivel']) ?></span>
                    </td>
                    <td><?= e($a['descripcion']) ?></td>
                    <td>
                        <?php
                        $clase_estado = [
                            'Activa' => 'badge-rojo',
                            'Resuelta' => 'badge-verde',
                            'Descartada' => 'badge-gris'
                        ][$a['estado']] ?? 'badge-gris';
                        ?>
                        <span class="badge <?= $clase_estado ?>"><?= e($a['estado']) ?></span>
                    </td>
                    <td style="text-align: right;">
                        <?php if ($a['estado'] === 'Activa'): ?>
                            <form action="cambiar_estado.php" method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="id_alerta" value="<?= e($a['id_alerta']) ?>">
                                <input type="hidden" name="nuevo_estado" value="Resuelta">
                                <button type="submit" class="boton" style="background:#2ecc71; padding: 5px 10px; font-size: 12px; margin-bottom: 2px;">Resolver</button>
                            </form>
                            <form action="cambiar_estado.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Descartar esta alerta?');">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="id_alerta" value="<?= e($a['id_alerta']) ?>">
                                <input type="hidden" name="nuevo_estado" value="Descartada">
                                <button type="submit" class="boton" style="background:#95a5a6; padding: 5px 10px; font-size: 12px;">Descartar</button>
                            </form>
                        <?php else: ?>
                            <span style="color:#99a; font-size:12px;">Sin acciones</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
