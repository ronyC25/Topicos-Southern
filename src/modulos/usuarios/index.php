<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

// SOLO Admin_Servidor
verificar_rol([ROL_ADMIN_SERVIDOR]);

$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY id DESC");
$usuarios = $stmt->fetchAll();

$titulo_pagina = 'Gestión de Usuarios';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
    <h1 class="titulo-modulo" style="margin-bottom: 0;">Usuarios del Sistema</h1>
    <button class="boton" onclick="abrirModalNuevo()">+ Nuevo Usuario</button>
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
            <th>Usuario (Login)</th>
            <th>DNI</th>
            <th>Nombre Completo</th>
            <th>Rol</th>
            <th>Auth</th>
            <th>Estado</th>
            <th style="text-align: right;">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><strong><?= e($u['nombre_usuario']) ?></strong></td>
                <td><?= e($u['dni'] ?? '-') ?></td>
                <td>
                    <?= e($u['nombre_completo']) ?><br>
                    <small style="color:#667;"><?= e($u['correo']) ?></small>
                </td>
                <td><span class="rol-etiqueta"><?= e($u['rol']) ?></span></td>
                <td>
                    <?php if ($u['tipo_autenticacion'] === AUTH_AD): ?>
                        <span class="badge badge-amarillo" title="Active Directory">AD</span>
                    <?php else: ?>
                        <span class="badge badge-gris" title="Local Database">Local</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($u['activo']): ?>
                        <span class="badge badge-verde">Activo</span>
                    <?php else: ?>
                        <span class="badge badge-rojo">Inactivo</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: right;">
                    <button class="boton boton-secundario" style="padding: 5px 10px; font-size: 12px;" onclick='abrirModalEditar(<?= json_encode($u) ?>)'>Editar</button>
                    <?php if ($u['nombre_usuario'] !== $_SESSION['nombre_usuario']): ?>
                        <form action="eliminar.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="id" value="<?= e($u['id']) ?>">
                            <button type="submit" class="boton" style="background:#a33; padding: 5px 10px; font-size: 12px;">Eliminar</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Modal Formulario -->
<div class="modal-overlay" id="modalForm">
    <div class="modal">
        <form action="guardar.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="accion" id="formAccion" value="crear">
            <input type="hidden" name="id_original" id="formIdOriginal" value="">

            <div class="modal-header">
                <h2 id="modalTitulo">Nuevo Usuario</h2>
                <button type="button" class="modal-close" onclick="cerrarModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="grupo-form" style="grid-column: span 2;">
                        <label>Nombre de Usuario (Login)*</label>
                        <input type="text" name="nombre_usuario" id="formUsuario" required maxlength="50" pattern="[a-zA-Z0-9\._-]+" title="Letras, números, puntos, guiones">
                    </div>
                    <div class="grupo-form">
                        <label>DNI (Físico)</label>
                        <input type="text" name="dni" id="formDni" maxlength="15">
                        <small style="color:#667; font-size:11px;">Requerido para conductores</small>
                    </div>
                    <div class="grupo-form">
                        <label>Nombre Completo*</label>
                        <input type="text" name="nombre_completo" id="formNombre" required maxlength="100">
                    </div>
                    <div class="grupo-form">
                        <label>Rol*</label>
                        <select name="rol" id="formRol" required>
                            <option value="<?= ROL_ADMIN_SERVIDOR ?>">Admin Servidor</option>
                            <option value="<?= ROL_ADMIN_BD ?>">Admin BD</option>
                            <option value="<?= ROL_ADMIN_TELEMETRIA ?>">Admin Telemetría</option>
                            <option value="<?= ROL_OPERADOR ?>">Operador</option>
                            <option value="<?= ROL_CONDUCTOR ?>">Conductor</option>
                        </select>
                    </div>
                    <div class="grupo-form">
                        <label>Tipo Autenticación*</label>
                        <select name="tipo_autenticacion" id="formAuth" required onchange="togglePassword()">
                            <option value="<?= AUTH_LOCAL ?>">Local</option>
                            <option value="<?= AUTH_AD ?>">Active Directory</option>
                        </select>
                    </div>
                    <div class="grupo-form" id="divPassword">
                        <label id="lblPassword">Contraseña*</label>
                        <input type="password" name="password" id="formPassword">
                        <small style="color:#667; font-size:11px;" id="helpPassword">Para AD, dejar en blanco.</small>
                    </div>
                    <div class="grupo-form">
                        <label>Correo Electrónico</label>
                        <input type="email" name="correo" id="formCorreo" maxlength="100">
                    </div>
                    <div class="grupo-form">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" id="formTelefono" maxlength="20">
                    </div>
                    <div class="grupo-form">
                        <label>Estado</label>
                        <select name="activo" id="formActivo">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
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
function togglePassword() {
    const isAD = document.getElementById('formAuth').value === '<?= AUTH_AD ?>';
    const pwdInput = document.getElementById('formPassword');
    const lbl = document.getElementById('lblPassword');
    
    if (isAD) {
        pwdInput.disabled = true;
        pwdInput.required = false;
        pwdInput.value = '';
    } else {
        pwdInput.disabled = false;
        // Si es crear local, password obligatorio
        if (document.getElementById('formAccion').value === 'crear') {
            pwdInput.required = true;
            lbl.innerText = 'Contraseña*';
        } else {
            pwdInput.required = false;
            lbl.innerText = 'Contraseña (Dejar blanco para no cambiar)';
        }
    }
}

function abrirModalNuevo() {
    document.getElementById('modalTitulo').innerText = 'Nuevo Usuario';
    document.getElementById('formAccion').value = 'crear';
    document.getElementById('formIdOriginal').value = '';
    
    document.getElementById('formUsuario').value = '';
    document.getElementById('formUsuario').readOnly = false;
    document.getElementById('formDni').value = '';
    document.getElementById('formNombre').value = '';
    document.getElementById('formRol').value = '<?= ROL_OPERADOR ?>';
    document.getElementById('formAuth').value = '<?= AUTH_LOCAL ?>';
    document.getElementById('formCorreo').value = '';
    document.getElementById('formTelefono').value = '';
    document.getElementById('formActivo').value = '1';
    
    togglePassword();
    document.getElementById('modalForm').style.display = 'flex';
}

function abrirModalEditar(u) {
    document.getElementById('modalTitulo').innerText = 'Editar Usuario';
    document.getElementById('formAccion').value = 'editar';
    document.getElementById('formIdOriginal').value = u.id;
    
    document.getElementById('formUsuario').value = u.nombre_usuario;
    document.getElementById('formUsuario').readOnly = true; // No permitir cambiar username
    document.getElementById('formDni').value = u.dni || '';
    document.getElementById('formNombre').value = u.nombre_completo;
    document.getElementById('formRol').value = u.rol;
    document.getElementById('formAuth').value = u.tipo_autenticacion;
    document.getElementById('formCorreo').value = u.correo || '';
    document.getElementById('formTelefono').value = u.telefono || '';
    document.getElementById('formActivo').value = u.activo;
    
    togglePassword();
    document.getElementById('modalForm').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modalForm').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
