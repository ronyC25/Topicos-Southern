---
name: nuevo-modulo
description: Scaffold a new FleetCore module under src/modulos/ with the standard boilerplate (session/DB includes, role check, header/footer) and wire it into the role-based menu. Use when the user asks to add a new module/section to FleetCore.
---

Given a module name (e.g. `combustible`) and the roles allowed to see it, create `src/modulos/<nombre>/index.php` following the exact pattern used by every existing module (see `src/modulos/dashboard/index.php` as the canonical reference):

```php
<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_..., ...]);   // use role constants from src/config/constantes.php, never raw strings

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- module content -->

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
```

If the module needs data-mutating actions, add sibling files (`guardar.php`, `eliminar.php`, `cambiar_estado.php`, etc.) following the same require_once + verificar_rol + validar_csrf() pattern used in existing modules like `src/modulos/flota/guardar.php` or `src/modulos/alertas/cambiar_estado.php`. Use PDO prepared statements only, and never put `$e->getMessage()` into a user-facing redirect (see the "Never surface $e->getMessage()" note in CLAUDE.md).

After creating the module, wire it into the navigation by editing `src/config/constantes.php`:
1. Add the module's slug to `MENU_POR_ROL` for each role that should see it.
2. Add a human-readable label to `NOMBRES_MODULOS`.

Do not touch `src/includes/sidebar.php` directly — it reads from those two constants.

If the module needs new DB tables, add a new numbered SQL file in `sql/` (following the `0X_descripcion.sql` naming already in use) rather than editing the existing `01_spcc_database_v3.sql` — MySQL's `docker-entrypoint-initdb.d` auto-import runs files in alphabetical order, and existing installs won't rerun already-applied files.
