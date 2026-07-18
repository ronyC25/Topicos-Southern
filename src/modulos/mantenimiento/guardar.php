<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_ADMIN_BD]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'crear') {
            $id_camion = $_POST['id_camion'] ?? '';
            $tipo_servicio = $_POST['tipo_servicio'] ?? '';
            $fecha_servicio = $_POST['fecha_servicio'] ?? '';
            $costo = !empty($_POST['costo']) ? (float)$_POST['costo'] : null;
            $tecnico_responsable = trim($_POST['tecnico_responsable'] ?? '') ?: null;
            $descripcion = trim($_POST['descripcion'] ?? '');

            if (empty($id_camion) || empty($tipo_servicio) || empty($fecha_servicio)) {
                throw new Exception('Faltan datos obligatorios para crear el mantenimiento.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO mantenimientos (id_camion, tipo_servicio, fecha_servicio, tecnico_responsable, descripcion, costo, estado)
                VALUES (?, ?, ?, ?, ?, ?, 'Pendiente')
            ");
            $stmt->execute([$id_camion, $tipo_servicio, $fecha_servicio, $tecnico_responsable, $descripcion, $costo]);
            
            // Actualizar el estado del vehículo a Mantenimiento
            $pdo->prepare("UPDATE vehiculos SET estado_operativo = 'Mantenimiento' WHERE id_camion = ?")->execute([$id_camion]);

            header('Location: index.php?msg=' . urlencode("Mantenimiento programado exitosamente."));
            exit;
            
        } elseif ($accion === 'estado') {
            $id_mantenimiento = (int)($_POST['id_mantenimiento'] ?? 0);
            $nuevo_estado = $_POST['nuevo_estado'] ?? '';

            if (empty($id_mantenimiento) || !in_array($nuevo_estado, ['En_Proceso', 'Completado', 'Cancelado'])) {
                throw new Exception('Datos inválidos para actualizar el estado.');
            }

            $stmt = $pdo->prepare("UPDATE mantenimientos SET estado = ? WHERE id_mantenimiento = ?");
            $stmt->execute([$nuevo_estado, $id_mantenimiento]);

            if ($nuevo_estado === 'Completado' || $nuevo_estado === 'Cancelado') {
                // Devolver el vehículo a Operativo al finalizar el mantenimiento
                // (Para simplificar asumiremos que vuelve a Operativo)
                $stmt_veh = $pdo->prepare("UPDATE vehiculos SET estado_operativo = 'Operativo' WHERE id_camion = (SELECT id_camion FROM mantenimientos WHERE id_mantenimiento = ?)");
                $stmt_veh->execute([$id_mantenimiento]);
            }

            header('Location: index.php?msg=' . urlencode("Estado del mantenimiento actualizado."));
            exit;
        }
    } catch (PDOException $e) {
        manejar_error_bd($e, 'mantenimiento/guardar');
    } catch (Exception $e) {
        header('Location: index.php?error=' . urlencode($e->getMessage()));
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
