<?php
/**
 * FleetCore — Cabecera común
 * Incluir DESPUÉS de sesion.php y verificar_rol() en cada página de módulo.
 * Define $titulo_pagina antes de incluir para personalizar el título.
 */
$titulo_pagina = $titulo_pagina ?? 'FleetCore';
$base = base_url();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo_pagina) ?> — FleetCore</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/estilos.css">
</head>
<body>
<header class="barra-superior">
    <div class="marca">FleetCore <span>SPCC</span></div>
    <div class="usuario-info">
        <span><?= e($_SESSION['nombre_completo']) ?></span>
        <span class="rol-etiqueta"><?= e($_SESSION['rol']) ?></span>
        <a href="<?= $base ?>/logout.php" class="cerrar-sesion">Salir</a>
    </div>
</header>
<div class="contenedor-principal">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <main class="contenido">
