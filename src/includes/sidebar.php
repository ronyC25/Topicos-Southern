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
    <ul>
        <?php foreach ($modulos_visibles as $mod): ?>
            <li class="<?= $mod === $modulo_actual ? 'activo' : '' ?>">
                <a href="<?= base_url() ?>/modulos/<?= $mod ?>/index.php">
                    <?= ICONOS_MODULO[$mod] ?? '' ?>
                    <span><?= e(NOMBRES_MODULOS[$mod] ?? ucfirst($mod)) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
