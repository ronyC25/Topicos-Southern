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

// ---- Estadísticas para las tarjetas ----
$total_vehiculos   = $pdo->query("SELECT COUNT(*) FROM vehiculos")->fetchColumn();
$operativos        = $pdo->query("SELECT COUNT(*) FROM vehiculos WHERE estado_operativo = 'Operativo'")->fetchColumn();
$turnos_activos    = $pdo->query("SELECT COUNT(*) FROM turnos WHERE estado_turno = 'Activo'")->fetchColumn();
$alertas_activas   = $pdo->query("SELECT COUNT(*) FROM alertas WHERE estado = 'Activa'")->fetchColumn();

// ---- Últimas alertas ----
$stmt = $pdo->query("
    SELECT a.tipo_alerta, a.nivel, a.descripcion, a.fecha_generacion, v.placa
    FROM alertas a
    JOIN vehiculos v ON v.id_camion = a.id_camion
    WHERE a.estado = 'Activa'
    ORDER BY a.fecha_generacion DESC
    LIMIT 5
");
$ultimas_alertas = $stmt->fetchAll();

$titulo_pagina = 'Dashboard';
require_once __DIR__ . '/../../includes/header.php';
?>

<h1 class="titulo-modulo">Dashboard</h1>

<div class="tarjetas">
    <div class="tarjeta">
        <div class="valor"><?= (int)$total_vehiculos ?></div>
        <div class="etiqueta">Vehículos en flota</div>
    </div>
    <div class="tarjeta">
        <div class="valor"><?= (int)$operativos ?></div>
        <div class="etiqueta">Operativos</div>
    </div>
    <div class="tarjeta">
        <div class="valor"><?= (int)$turnos_activos ?></div>
        <div class="etiqueta">Turnos activos</div>
    </div>
    <div class="tarjeta">
        <div class="valor"><?= (int)$alertas_activas ?></div>
        <div class="etiqueta">Alertas activas</div>
    </div>
</div>

<h2 style="font-size:16px; margin-bottom:12px;">Últimas alertas activas</h2>
<table class="tabla">
    <thead>
        <tr>
            <th>Vehículo</th>
            <th>Tipo</th>
            <th>Nivel</th>
            <th>Descripción</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($ultimas_alertas)): ?>
            <tr><td colspan="5">Sin alertas activas.</td></tr>
        <?php else: ?>
            <?php foreach ($ultimas_alertas as $a): ?>
                <tr>
                    <td><?= e($a['placa']) ?></td>
                    <td><?= e($a['tipo_alerta']) ?></td>
                    <td>
                        <?php
                        $clase = ['Baja'=>'badge-gris','Media'=>'badge-amarillo','Alta'=>'badge-rojo','Critica'=>'badge-rojo'][$a['nivel']] ?? 'badge-gris';
                        ?>
                        <span class="badge <?= $clase ?>"><?= e($a['nivel']) ?></span>
                    </td>
                    <td><?= e($a['descripcion']) ?></td>
                    <td><?= e($a['fecha_generacion']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
