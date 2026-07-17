<?php
/**
 * FleetCore — Conexión a la base de datos (PDO)
 *
 * Lee variables de entorno (Docker). Si no existen, usa los valores
 * del servidor real (XAMPP en SRV-DISPATCH-01), donde MySQL está en localhost.
 * Así el MISMO archivo funciona en desarrollo y en producción sin editar nada.
 */

$host       = getenv('DB_HOST') ?: 'localhost';
$bd         = getenv('DB_NAME') ?: 'db_dispatch';
$usuario    = getenv('DB_USER') ?: 'spcc_app';
$contrasena = getenv('DB_PASS') ?: 'AppSpcc_2026*';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$bd;charset=utf8mb4",
        $usuario,
        $contrasena,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Nunca mostrar el detalle del error al usuario (expone información interna)
    error_log("FleetCore BD: " . $e->getMessage());
    die("Error de conexión a la base de datos. Contacte al administrador.");
}
