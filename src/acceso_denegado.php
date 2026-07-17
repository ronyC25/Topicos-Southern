<?php
require_once __DIR__ . '/auth/sesion.php';
// Solo requiere sesión activa (cualquier rol) para mostrar la página
if (!isset($_SESSION['nombre_usuario'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso denegado — FleetCore</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <div class="pagina-centrada">
        <h1>⛔ Acceso denegado</h1>
        <p>Su rol (<strong><?= e($_SESSION['rol']) ?></strong>) no tiene permisos para ese módulo.</p>
        <a href="javascript:history.back()" class="boton">Volver</a>
        <a href="logout.php" class="boton boton-secundario">Cerrar sesión</a>
    </div>
</body>
</html>
