<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_SERVIDOR, ROL_OPERADOR]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();

    $accion = $_POST['accion'] ?? '';
    $id_camion = trim($_POST['id_camion'] ?? '');
    $id_camion_original = trim($_POST['id_camion_original'] ?? '');
    
    $placa = trim($_POST['placa'] ?? '') ?: null;
    $marca = trim($_POST['marca'] ?? '') ?: null;
    $modelo = trim($_POST['modelo'] ?? '');
    $anio = !empty($_POST['anio']) ? (int)$_POST['anio'] : null;
    $estado_operativo = $_POST['estado_operativo'] ?? VEHICULO_OPERATIVO;
    $capacidad_carga = !empty($_POST['capacidad_carga']) ? (float)$_POST['capacidad_carga'] : null;
    $tipo_combustible = trim($_POST['tipo_combustible'] ?? '') ?: null;

    if (empty($id_camion) || empty($modelo)) {
        header('Location: index.php?error=' . urlencode('ID y Modelo son obligatorios.'));
        exit;
    }

    try {
        if ($accion === 'crear') {
            $stmt = $pdo->prepare("
                INSERT INTO vehiculos (id_camion, placa, marca, modelo, anio, estado_operativo, capacidad_carga, tipo_combustible)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id_camion, $placa, $marca, $modelo, $anio, $estado_operativo, $capacidad_carga, $tipo_combustible]);
            $msg = "Vehículo creado exitosamente.";
        } elseif ($accion === 'editar') {
            $stmt = $pdo->prepare("
                UPDATE vehiculos 
                SET id_camion = ?, placa = ?, marca = ?, modelo = ?, anio = ?, estado_operativo = ?, capacidad_carga = ?, tipo_combustible = ?
                WHERE id_camion = ?
            ");
            $stmt->execute([$id_camion, $placa, $marca, $modelo, $anio, $estado_operativo, $capacidad_carga, $tipo_combustible, $id_camion_original]);
            $msg = "Vehículo actualizado exitosamente.";
        }
        
        header('Location: index.php?msg=' . urlencode($msg));
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            header('Location: index.php?error=' . urlencode("El ID del camión ya existe en el sistema."));
            exit;
        }
        manejar_error_bd($e, 'flota/guardar');
    }
} else {
    header('Location: index.php');
    exit;
}
