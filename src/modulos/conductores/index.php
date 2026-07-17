<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_OPERADOR]);

// Obtener lista de conductores
$stmt = $pdo->query("SELECT * FROM conductores ORDER BY fecha_creacion DESC");
$conductores = $stmt->fetchAll();

// Obtener lista de usuarios con rol Conductor que aún no están en la tabla conductores
$stmt_usuarios = $pdo->query("
    SELECT u.dni, u.nombre_usuario, u.nombre_completo 
    FROM usuarios u 
    LEFT JOIN conductores c ON u.dni = c.dni
    WHERE u.rol = 'Conductor' AND c.dni IS NULL AND u.dni IS NOT NULL
");
$usuarios_candidatos = $stmt_usuarios->fetchAll();

$titulo_pagina = 'Gestión de Conductores';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
    <h1 class="titulo-modulo" style="margin-bottom: 0;">Conductores</h1>
    <button class="boton" onclick="abrirModalNuevo()">+ Nuevo Conductor</button>
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
            <th>DNI (Usuario)</th>
            <th>Nombre Completo</th>
            <th>Licencia</th>
            <th>Contacto</th>
            <th>Estado</th>
            <th style="text-align: right;">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($conductores)): ?>
            <tr><td colspan="6">No hay conductores registrados.</td></tr>
        <?php else: ?>
            <?php foreach ($conductores as $c): ?>
                <tr>
                    <td><strong><?= e($c['dni']) ?></strong></td>
                    <td><?= e($c['nombre']) ?></td>
                    <td><?= e($c['licencia']) ?></td>
                    <td>
                        <small>Tel: <?= e($c['telefono']) ?><br>
                        Corr: <?= e($c['correo']) ?></small>
                    </td>
                    <td>
                        <?php
                        $clase_estado = [
                            'Activo' => 'badge-verde',
                            'Inactivo' => 'badge-gris',
                            'Suspendido' => 'badge-rojo'
                        ][$c['estado']] ?? 'badge-gris';
                        ?>
                        <span class="badge <?= $clase_estado ?>"><?= e($c['estado']) ?></span>
                    </td>
                    <td style="text-align: right;">
                        <button class="boton boton-secundario" style="padding: 5px 10px; font-size: 12px;" onclick='abrirModalEditar(<?= json_encode($c) ?>)'>Editar</button>
                        <form action="eliminar.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar a este conductor?');">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="dni" value="<?= e($c['dni']) ?>">
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
            <input type="hidden" name="dni_original" id="formDniOriginal" value="">

            <div class="modal-header">
                <h2 id="modalTitulo">Nuevo Conductor</h2>
                <button type="button" class="modal-close" onclick="cerrarModal()">&times;</button>
            </div>
            <div class="modal-body">
                
                <div class="grupo-form" id="divCandidatos">
                    <label style="color: #1c7a3d;">Vincular Usuario (Solo muestra usuarios Rol Conductor pendientes)*</label>
                    <select id="selectCandidato" onchange="cargarDatosCandidato(this)">
                        <option value="">-- Seleccione un usuario --</option>
                        <?php foreach ($usuarios_candidatos as $uc): ?>
                            <option value="<?= e($uc['dni']) ?>" data-nombre="<?= e($uc['nombre_completo']) ?>">
                                <?= e($uc['nombre_usuario']) ?> - <?= e($uc['nombre_completo']) ?> (DNI: <?= e($uc['dni']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="grupo-form">
                        <label>DNI (Usuario)*</label>
                        <input type="text" name="dni" id="formDni" required maxlength="15" readonly style="background:#eef0f5;">
                    </div>
                    <div class="grupo-form" style="grid-column: span 2;">
                        <label>Nombre Completo*</label>
                        <input type="text" name="nombre" id="formNombre" required maxlength="100" readonly style="background:#eef0f5;">
                    </div>
                    <div class="grupo-form">
                        <label>Licencia*</label>
                        <input type="text" name="licencia" id="formLicencia" required maxlength="20">
                    </div>
                    <div class="grupo-form">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" id="formTelefono" maxlength="20">
                    </div>
                    <div class="grupo-form">
                        <label>Correo Electrónico</label>
                        <input type="email" name="correo" id="formCorreo" maxlength="100">
                    </div>
                    <div class="grupo-form">
                        <label>Fecha Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" id="formNacimiento">
                    </div>
                    <div class="grupo-form">
                        <label>Estado*</label>
                        <select name="estado" id="formEstado" required>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                            <option value="Suspendido">Suspendido</option>
                        </select>
                    </div>
                    <div class="grupo-form" style="grid-column: span 2;">
                        <label>Dirección</label>
                        <textarea name="direccion" id="formDireccion" rows="2"></textarea>
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
function cargarDatosCandidato(sel) {
    var opt = sel.options[sel.selectedIndex];
    if(opt.value) {
        document.getElementById('formDni').value = opt.value;
        document.getElementById('formNombre').value = opt.getAttribute('data-nombre');
    } else {
        document.getElementById('formDni').value = '';
        document.getElementById('formNombre').value = '';
    }
}

function abrirModalNuevo() {
    document.getElementById('modalTitulo').innerText = 'Nuevo Conductor';
    document.getElementById('formAccion').value = 'crear';
    document.getElementById('formDniOriginal').value = '';
    
    // Mostrar dropdown de candidatos
    document.getElementById('divCandidatos').style.display = 'block';
    document.getElementById('selectCandidato').value = '';
    
    // Limpiar campos
    document.getElementById('formDni').value = '';
    document.getElementById('formLicencia').value = '';
    document.getElementById('formNombre').value = '';
    document.getElementById('formTelefono').value = '';
    document.getElementById('formCorreo').value = '';
    document.getElementById('formNacimiento').value = '';
    document.getElementById('formEstado').value = 'Activo';
    document.getElementById('formDireccion').value = '';
    
    document.getElementById('modalForm').style.display = 'flex';
}

function abrirModalEditar(c) {
    document.getElementById('modalTitulo').innerText = 'Editar Conductor';
    document.getElementById('formAccion').value = 'editar';
    document.getElementById('formDniOriginal').value = c.dni;
    
    // Ocultar dropdown de candidatos al editar
    document.getElementById('divCandidatos').style.display = 'none';
    
    // Llenar campos
    document.getElementById('formDni').value = c.dni;
    document.getElementById('formLicencia').value = c.licencia || '';
    document.getElementById('formNombre').value = c.nombre || '';
    document.getElementById('formTelefono').value = c.telefono || '';
    document.getElementById('formCorreo').value = c.correo || '';
    document.getElementById('formNacimiento').value = c.fecha_nacimiento || '';
    document.getElementById('formEstado').value = c.estado || 'Activo';
    document.getElementById('formDireccion').value = c.direccion || '';
    
    document.getElementById('modalForm').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalForm').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
