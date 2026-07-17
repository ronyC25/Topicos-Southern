<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_OPERADOR]);

// Obtener lista de vehículos
$stmt = $pdo->query("SELECT * FROM vehiculos ORDER BY fecha_creacion DESC");
$vehiculos = $stmt->fetchAll();

$titulo_pagina = 'Gestión de Flota';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
    <h1 class="titulo-modulo" style="margin-bottom: 0;">Flota de Vehículos</h1>
    <button class="boton" onclick="abrirModalNuevo()">+ Nuevo Vehículo</button>
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
            <th>ID / Placa</th>
            <th>Marca y Modelo</th>
            <th>Año</th>
            <th>Estado</th>
            <th>Kilometraje</th>
            <th style="text-align: right;">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($vehiculos)): ?>
            <tr><td colspan="6">No hay vehículos registrados.</td></tr>
        <?php else: ?>
            <?php foreach ($vehiculos as $v): ?>
                <tr>
                    <td>
                        <strong><?= e($v['id_camion']) ?></strong><br>
                        <small style="color:#667;"><?= e($v['placa']) ?></small>
                    </td>
                    <td><?= e($v['marca']) ?> <?= e($v['modelo']) ?></td>
                    <td><?= e($v['anio']) ?></td>
                    <td>
                        <?php
                        $clase_estado = [
                            VEHICULO_OPERATIVO => 'badge-verde',
                            VEHICULO_MANTENIMIENTO => 'badge-amarillo',
                            VEHICULO_FUERA => 'badge-rojo'
                        ][$v['estado_operativo']] ?? 'badge-gris';
                        ?>
                        <span class="badge <?= $clase_estado ?>"><?= e($v['estado_operativo']) ?></span>
                    </td>
                    <td><?= number_format($v['kilometraje_total'], 2) ?> km</td>
                    <td style="text-align: right;">
                        <button class="boton boton-secundario" style="padding: 5px 10px; font-size: 12px;" onclick='abrirModalEditar(<?= json_encode($v) ?>)'>Editar</button>
                        <form action="eliminar.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este vehículo?');">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="id_camion" value="<?= e($v['id_camion']) ?>">
                            <button type="submit" class="boton" style="background:#a33; padding: 5px 10px; font-size: 12px;">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Modal Formulario -->
<div class="modal-overlay" id="modalForm">
    <div class="modal">
        <form action="guardar.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="accion" id="formAccion" value="crear">
            <input type="hidden" name="id_camion_original" id="formIdOriginal" value="">

            <div class="modal-header">
                <h2 id="modalTitulo">Nuevo Vehículo</h2>
                <button type="button" class="modal-close" onclick="cerrarModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="grupo-form">
                        <label>ID Camión (Código interno)*</label>
                        <input type="text" name="id_camion" id="formId" required maxlength="20">
                    </div>
                    <div class="grupo-form">
                        <label>Placa</label>
                        <input type="text" name="placa" id="formPlaca" maxlength="20">
                    </div>
                    <div class="grupo-form">
                        <label>Marca</label>
                        <input type="text" name="marca" id="formMarca" maxlength="50">
                    </div>
                    <div class="grupo-form">
                        <label>Modelo*</label>
                        <input type="text" name="modelo" id="formModelo" required maxlength="50">
                    </div>
                    <div class="grupo-form">
                        <label>Año</label>
                        <input type="number" name="anio" id="formAnio" min="1990" max="2100">
                    </div>
                    <div class="grupo-form">
                        <label>Estado*</label>
                        <select name="estado_operativo" id="formEstado" required>
                            <option value="<?= VEHICULO_OPERATIVO ?>">Operativo</option>
                            <option value="<?= VEHICULO_MANTENIMIENTO ?>">Mantenimiento</option>
                            <option value="<?= VEHICULO_FUERA ?>">Fuera de Servicio</option>
                        </select>
                    </div>
                    <div class="grupo-form">
                        <label>Capacidad Carga (TN)</label>
                        <input type="number" step="0.01" name="capacidad_carga" id="formCapacidad">
                    </div>
                    <div class="grupo-form">
                        <label>Tipo Combustible</label>
                        <input type="text" name="tipo_combustible" id="formCombustible" maxlength="50">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="boton boton-secundario" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="boton">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalNuevo() {
    document.getElementById('modalTitulo').innerText = 'Nuevo Vehículo';
    document.getElementById('formAccion').value = 'crear';
    document.getElementById('formIdOriginal').value = '';
    
    // Limpiar campos
    document.getElementById('formId').value = '';
    document.getElementById('formPlaca').value = '';
    document.getElementById('formMarca').value = '';
    document.getElementById('formModelo').value = '';
    document.getElementById('formAnio').value = '';
    document.getElementById('formEstado').value = '<?= VEHICULO_OPERATIVO ?>';
    document.getElementById('formCapacidad').value = '';
    document.getElementById('formCombustible').value = '';
    
    document.getElementById('modalForm').style.display = 'flex';
}

function abrirModalEditar(v) {
    document.getElementById('modalTitulo').innerText = 'Editar Vehículo';
    document.getElementById('formAccion').value = 'editar';
    document.getElementById('formIdOriginal').value = v.id_camion;
    
    // Llenar campos
    document.getElementById('formId').value = v.id_camion;
    document.getElementById('formPlaca').value = v.placa || '';
    document.getElementById('formMarca').value = v.marca || '';
    document.getElementById('formModelo').value = v.modelo || '';
    document.getElementById('formAnio').value = v.anio || '';
    document.getElementById('formEstado').value = v.estado_operativo || '<?= VEHICULO_OPERATIVO ?>';
    document.getElementById('formCapacidad').value = v.capacidad_carga || '';
    document.getElementById('formCombustible').value = v.tipo_combustible || '';
    
    document.getElementById('modalForm').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalForm').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
