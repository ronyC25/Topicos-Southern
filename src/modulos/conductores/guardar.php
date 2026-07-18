<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_OPERADOR]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $accion = $_POST['accion'] ?? '';
    $dni = trim($_POST['dni'] ?? '');
    $dni_original = trim($_POST['dni_original'] ?? '');
    
    $nombre = trim($_POST['nombre'] ?? '');
    $licencia = trim($_POST['licencia'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '') ?: null;
    $correo = trim($_POST['correo'] ?? '') ?: null;
    $fecha_nacimiento = trim($_POST['fecha_nacimiento'] ?? '') ?: null;
    $estado = $_POST['estado'] ?? 'Activo';
    $direccion = trim($_POST['direccion'] ?? '') ?: null;

    if (empty($dni) || empty($nombre) || empty($licencia)) {
        header('Location: index.php?error=' . urlencode('DNI, Nombre y Licencia son obligatorios.'));
        exit;
    }

    try {
        if ($accion === 'crear') {
            $stmt = $pdo->prepare("
                INSERT INTO conductores (dni, nombre, licencia, telefono, correo, fecha_nacimiento, estado, direccion, fecha_ingreso)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
            ");
            $stmt->execute([$dni, $nombre, $licencia, $telefono, $correo, $fecha_nacimiento, $estado, $direccion]);
            $msg = "Conductor registrado exitosamente.";
        } elseif ($accion === 'editar') {
            $stmt = $pdo->prepare("
                UPDATE conductores 
                SET dni = ?, nombre = ?, licencia = ?, telefono = ?, correo = ?, fecha_nacimiento = ?, estado = ?, direccion = ?
                WHERE dni = ?
            ");
            $stmt->execute([$dni, $nombre, $licencia, $telefono, $correo, $fecha_nacimiento, $estado, $direccion, $dni_original]);
            $msg = "Conductor actualizado exitosamente.";
        }
        
        header('Location: index.php?msg=' . urlencode($msg));
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            header('Location: index.php?error=' . urlencode("El DNI ya se encuentra registrado."));
            exit;
        }
        manejar_error_bd($e, 'conductores/guardar');
    }
} else {
    header('Location: index.php');
    exit;
}
