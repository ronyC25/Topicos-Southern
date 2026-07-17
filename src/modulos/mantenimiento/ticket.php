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
            $tipo = $_POST['tipo'] ?? '';
            $prioridad = $_POST['prioridad'] ?? '';
            $descripcion = trim($_POST['descripcion'] ?? '');

            if (empty($id_camion) || empty($tipo) || empty($prioridad) || empty($descripcion)) {
                throw new Exception('Faltan datos obligatorios para crear el ticket.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO tickets_atencion (id_camion, tipo, prioridad, descripcion, usuario_creacion, estado)
                VALUES (?, ?, ?, ?, ?, 'Abierto')
            ");
            $stmt->execute([$id_camion, $tipo, $prioridad, $descripcion, $_SESSION['nombre_usuario']]);

            header('Location: index.php?msg=' . urlencode("Ticket creado exitosamente."));
            exit;
            
        } elseif ($accion === 'estado') {
            $id_ticket = (int)($_POST['id_ticket'] ?? 0);
            $nuevo_estado = $_POST['nuevo_estado'] ?? '';

            if (empty($id_ticket) || !in_array($nuevo_estado, ['En_Proceso', 'Resuelto', 'Cerrado'])) {
                throw new Exception('Datos inválidos para actualizar el ticket.');
            }

            $sql = "UPDATE tickets_atencion SET estado = ?";
            $params = [$nuevo_estado];

            if ($nuevo_estado === 'Resuelto' || $nuevo_estado === 'Cerrado') {
                $sql .= ", fecha_cierre = NOW()";
            }
            $sql .= " WHERE id_ticket = ?";
            $params[] = $id_ticket;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            header('Location: index.php?msg=' . urlencode("Ticket actualizado exitosamente."));
            exit;
        }
    } catch (Exception $e) {
        header('Location: index.php?error=' . urlencode($e->getMessage()));
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
