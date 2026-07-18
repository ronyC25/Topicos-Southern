<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $accion = $_POST['accion'] ?? '';
    $id = trim($_POST['id_original'] ?? '');
    
    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
    $dni = trim($_POST['dni'] ?? '') ?: null;
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $rol = $_POST['rol'] ?? ROL_OPERADOR;
    $tipo_autenticacion = $_POST['tipo_autenticacion'] ?? AUTH_LOCAL;
    $correo = trim($_POST['correo'] ?? '') ?: null;
    $telefono = trim($_POST['telefono'] ?? '') ?: null;
    $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
    
    $password_plana = $_POST['password'] ?? '';

    if ($accion === 'crear' && empty($nombre_usuario)) {
        header('Location: index.php?error=' . urlencode('Nombre de usuario es obligatorio.'));
        exit;
    }

    try {
        if ($accion === 'crear') {
            $hash = null;
            if ($tipo_autenticacion === AUTH_LOCAL) {
                if (empty($password_plana)) {
                    header('Location: index.php?error=' . urlencode('Contraseña es obligatoria para usuarios locales.'));
                    exit;
                }
                $hash = password_hash($password_plana, PASSWORD_DEFAULT);
            }

            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nombre_usuario, dni, contrasena_hash, tipo_autenticacion, rol, nombre_completo, correo, telefono, activo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nombre_usuario, $dni, $hash, $tipo_autenticacion, $rol, $nombre_completo, $correo, $telefono, $activo]);
            $msg = "Usuario creado exitosamente.";
            
        } elseif ($accion === 'editar') {
            // Si el usuario cambia su propio rol o estado (podría perder acceso, pero se le permite, con cuidado)
            // Si es local y proporcionó password, actualizamos el hash
            
            if ($tipo_autenticacion === AUTH_LOCAL && !empty($password_plana)) {
                $hash = password_hash($password_plana, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE usuarios 
                    SET dni = ?, rol = ?, tipo_autenticacion = ?, nombre_completo = ?, correo = ?, telefono = ?, activo = ?, contrasena_hash = ?
                    WHERE id = ?
                ");
                $stmt->execute([$dni, $rol, $tipo_autenticacion, $nombre_completo, $correo, $telefono, $activo, $hash, $id]);
            } else {
                // Si es AD, o es Local sin cambio de clave, el hash se mantiene igual (o a null si cambia a AD, pero vamos a forzar NULL si es AD)
                if ($tipo_autenticacion === AUTH_AD) {
                    $stmt = $pdo->prepare("
                        UPDATE usuarios 
                        SET dni = ?, rol = ?, tipo_autenticacion = ?, nombre_completo = ?, correo = ?, telefono = ?, activo = ?, contrasena_hash = NULL
                        WHERE id = ?
                    ");
                    $stmt->execute([$dni, $rol, $tipo_autenticacion, $nombre_completo, $correo, $telefono, $activo, $id]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE usuarios 
                        SET dni = ?, rol = ?, tipo_autenticacion = ?, nombre_completo = ?, correo = ?, telefono = ?, activo = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$dni, $rol, $tipo_autenticacion, $nombre_completo, $correo, $telefono, $activo, $id]);
                }
            }
            $msg = "Usuario actualizado exitosamente.";
        }
        
        header('Location: index.php?msg=' . urlencode($msg));
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            header('Location: index.php?error=' . urlencode("El nombre de usuario o DNI ya está en uso."));
            exit;
        }
        manejar_error_bd($e, 'usuarios/guardar');
    }
} else {
    header('Location: index.php');
    exit;
}
