<?php
/**
 * FleetCore — Cabecera común
 * Incluir DESPUÉS de sesion.php y verificar_rol() en cada página de módulo.
 * Define $titulo_pagina antes de incluir para personalizar el título.
 */
$titulo_pagina = $titulo_pagina ?? 'FleetCore';
$base = base_url();
$rol_actual = $_SESSION['rol'] ?? '';
$icono_rol  = ICONOS_ROL[$rol_actual] ?? ICONO_USUARIO_DEFECTO;
$clase_rol  = CLASES_ROL[$rol_actual] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo_pagina) ?> — FleetCore</title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>/assets/favicon.svg">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/estilos.css?v=<?= @filemtime(__DIR__ . '/../assets/css/estilos.css') ?: time() ?>">
    <script defer src="<?= $base ?>/assets/js/global.js?v=<?= @filemtime(__DIR__ . '/../assets/js/global.js') ?: time() ?>"></script>
</head>
<body>
<header class="barra-superior">
    <div class="marca">
        <span class="marca-glow"></span>
        FleetCore <span>SPCC</span>
    </div>
    <div class="usuario-info">
        <div class="usuario-avatar"><?= e(strtoupper(substr($_SESSION['nombre_completo'] ?? 'U', 0, 1))) ?></div>
        <div class="usuario-detalle">
            <span class="usuario-nombre"><?= e($_SESSION['nombre_completo']) ?></span>
            <span class="usuario-rol"><?= e($_SESSION['rol']) ?></span>
        </div>
        <div class="usuario-divisor"></div>
        <span class="rol-etiqueta <?= $clase_rol ?>"><?= $icono_rol ?> <?= e($_SESSION['rol_corto'] ?? $_SESSION['rol']) ?></span>
        <a href="<?= $base ?>/logout.php" class="cerrar-sesion" title="Cerrar sesión">
            <svg class="salir-icono" viewBox="0 0 20 20" fill="none"><path d="M7 17H4a1 1 0 01-1-1V4a1 1 0 011-1h3M13 14l4-4-4-4M17 10H8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span>Salir</span>
        </a>
    </div>
    <div class="header-accent"></div>
</header>
<div class="contenedor-principal">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <main class="contenido">
