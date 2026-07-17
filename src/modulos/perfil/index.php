<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

// Todos los roles tienen acceso al perfil
verificar_rol([ROL_ADMIN_SERVIDOR, ROL_ADMIN_BD, ROL_ADMIN_TELEMETRIA, ROL_OPERADOR, ROL_CONDUCTOR]);

// Obtener datos del usuario actual
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['id_usuario']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die("Error: Usuario no encontrado.");
}

$titulo_pagina = 'Mi Perfil';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <h1 class="titulo-modulo">Mi Perfil</h1>

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

    <div class="tarjeta" style="margin-bottom: 20px;">
        <h2 style="font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">Información de la Cuenta</h2>
        
        <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px; font-size: 14px;">
            <strong style="color: #667;">Usuario:</strong>
            <div><?= e($usuario['nombre_usuario']) ?></div>
            
            <strong style="color: #667;">Nombre:</strong>
            <div><?= e($usuario['nombre_completo']) ?></div>
            
            <strong style="color: #667;">Rol:</strong>
            <div><span class="rol-etiqueta"><?= e($usuario['rol']) ?></span></div>
            
            <strong style="color: #667;">Correo:</strong>
            <div><?= e($usuario['correo'] ?: 'No registrado') ?></div>
            
            <strong style="color: #667;">Teléfono:</strong>
            <div><?= e($usuario['telefono'] ?: 'No registrado') ?></div>

            <strong style="color: #667;">Autenticación:</strong>
            <div><?= $usuario['tipo_autenticacion'] === AUTH_AD ? 'Active Directory' : 'Local' ?></div>
        </div>
    </div>

    <?php if ($usuario['tipo_autenticacion'] === AUTH_LOCAL): ?>
        <div class="tarjeta">
            <h2 style="font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">Cambiar Contraseña</h2>
            
            <form action="guardar.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="grupo-form">
                    <label>Contraseña Actual</label>
                    <input type="password" name="password_actual" required>
                </div>
                
                <div class="grupo-form">
                    <label>Nueva Contraseña</label>
                    <input type="password" name="password_nueva" required minlength="6">
                </div>
                
                <div class="grupo-form">
                    <label>Confirmar Nueva Contraseña</label>
                    <input type="password" name="password_confirmacion" required minlength="6">
                </div>
                
                <div style="text-align: right;">
                    <button type="submit" class="boton">Actualizar Contraseña</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="tarjeta" style="background: #fdf3dc; border-left: 4px solid #96700a;">
            <p style="color: #96700a; font-size: 14px;">
                <strong>Nota:</strong> Tu cuenta utiliza autenticación de Directorio Activo (AD). Para cambiar tu contraseña, debes hacerlo a través de los sistemas corporativos de SPCC.
            </p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
