<?php
/**
 * FleetCore — Menú lateral dinámico según rol
 * Se incluye automáticamente desde header.php
 */
$modulos_visibles = MENU_POR_ROL[$_SESSION['rol']] ?? ['perfil'];
$modulo_actual = '';
if (preg_match('#/modulos/([^/]+)/#', $_SERVER['SCRIPT_NAME'], $m)) {
    $modulo_actual = $m[1];
}
?>
<nav class="menu-lateral">
    <div class="sidebar-header">
        <div class="sidebar-header-icon">
            <svg viewBox="0 0 20 20" fill="none"><rect x="2" y="3" width="16" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/><line x1="6" y1="8" x2="14" y2="8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><line x1="6" y1="12" x2="12" y2="12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
        <span>Navegación</span>
    </div>
    <ul>
        <?php $idx_menu = 0; ?>
        <?php foreach ($modulos_visibles as $mod): $idx_menu++; ?>
            <li class="menu-item <?= $mod === $modulo_actual ? 'activo' : '' ?>" style="--i: <?= $idx_menu ?>">
                <a href="<?= base_url() ?>/modulos/<?= $mod ?>/index.php">
                    <span class="menu-icono"><?= ICONOS_MODULO[$mod] ?? '' ?></span>
                    <span class="menu-texto"><?= e(NOMBRES_MODULOS[$mod] ?? ucfirst($mod)) ?></span>
                    <span class="menu-accent"></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
