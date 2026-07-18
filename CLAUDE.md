# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

FleetCore is plain PHP 8.2 with **no framework, no Composer, no package manager, no build step**. All includes are manual `require_once` with relative paths. MySQL 8.0 via PDO (prepared statements only, no ORM). Deployed via Docker for dev and via XAMPP for production — same `src/` tree runs unmodified in both (see "Dual deployment" below).

There is no test suite, no linter, and no CI configured. Verification is manual: run the app and click through the affected module.

## UI work

Before touching the interface of any module, read @DESIGN.md — it documents the existing visual system (color palette, components: tarjetas/tabla/badges/modal/formularios, and the page shell in `header.php`/`sidebar.php`/`footer.php`) extracted directly from `src/assets/css/estilos.css` and how it's actually used. Reuse existing classes/patterns exactly; don't invent new colors, components, or a CSS framework.

## Running the app

```bash
docker compose up -d          # start (auto-imports sql/ on first run via a MySQL volume mount)
docker compose down           # stop
docker compose down -v        # stop AND wipe the MySQL volume (reimports SQL next start)
docker compose logs -f web    # tail PHP/Apache errors
```
- App: http://localhost:8080
- phpMyAdmin: http://localhost:8081 (root/root)

Test credentials (README.md has the full list): AD-simulated admins `admin.servidor` / `admin.bd` / `admin.telemetria` (password `Desarrollo_2026*`, only works because `MODO_DESARROLLO=true` in Docker — see Gotchas), local users `operador.prueba` / `conductor.prueba` (password `Prueba_2026*`).

## Project structure

`src/` is the htdocs root. The app has 11 modules under `src/modulos/`: `dashboard`, `flota` (vehículos), `conductores`, `turnos` (shifts — the central table joining conductor+vehículo+tiempo), `telemetria`, `alertas`, `incidencias`, `mantenimiento` (+ `tickets_atencion`), `reportes`, `usuarios`, `perfil`.

Which modules a role can see is centralized in `MENU_POR_ROL` and `NOMBRES_MODULOS` in `src/config/constantes.php` — modules are not individually wired into navigation, so adding a module means updating those constants, not `src/includes/sidebar.php` directly.

## Mandatory module boilerplate

Every module entry file must start with this exact pattern (see `src/modulos/dashboard/index.php` for the canonical example):

```php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_..., ...]);   // roles from src/config/constantes.php — never raw strings
```
Then `header.php` ... content ... `footer.php`.

## Security conventions

- All DB access through PDO prepared statements — never interpolate user input into SQL.
- All HTML output must go through the `e()` helper (`src/auth/sesion.php`) — an `htmlspecialchars` wrapper for XSS prevention.
- Every state-changing form must call `validar_csrf()` and include the token from `csrf_token()` (both in `src/auth/sesion.php`).
- **Never surface `$e->getMessage()` to the user.** `src/config/conexion.php` states this explicitly, but several existing modules violate it by putting the raw PDO exception message into a `?error=` redirect param (e.g. `usuarios/guardar.php`, `conductores/guardar.php`, `alertas/cambiar_estado.php`, `incidencias/cambiar_estado.php`). Don't copy that pattern into new code, and prefer fixing it opportunistically if you're already touching one of those files — but that's a existing/known issue, not something to go fix unprompted across the whole codebase.

## Dual deployment (Docker dev / XAMPP production)

The same `src/` tree runs in Docker (path `/`) and on the real server `SRV-DISPATCH-01` via XAMPP (path `/fleetcore`). `base_url()` in `src/auth/sesion.php` detects which context it's in from `SCRIPT_NAME`. `src/config/conexion.php` reads DB creds from env vars with fallbacks that match the XAMPP production defaults, so it needs no edits between environments.

## Known gotchas

- Auth is dual-mode per user: `usuarios.tipo_autenticacion` is `'local'` (bcrypt via `password_verify`) or `'active_directory'` (real LDAP bind against `spcc.local`, bypassed when `MODO_DESARROLLO=true` in `docker-compose.yml`, which is why `admin.servidor`/`admin.bd`/`admin.telemetria` log in with the fixed dev password instead of real AD).
- `fleetcore_produccion.zip` and `Reiniciar Server 1.pdf` in the repo root are stray build/ops artifacts, not part of the app source — don't read, edit, or regenerate them as part of code changes.
- ~~LDAP extension missing in Docker~~ and ~~`$e->getMessage()` leaked to users~~ were both real issues found early on — **already fixed** (see "Estado del proyecto" below). Don't re-flag or re-fix them; if you see either pattern again in a *new* file, follow the fix already applied elsewhere (`manejar_error_bd()` in `src/auth/sesion.php` for the error-message case).

## Git workflow

Commit directly to `main` — no feature-branch/PR convention is in use for this repo.

## Estado del proyecto (última sesión)

Todo lo de abajo está aplicado en el **working tree pero sin commitear** (`git status` lo confirma) — si vuelves a este proyecto y `git log` no muestra estos cambios, no se perdieron: solo falta el `git add`/`commit`.

**Hecho:**
1. **Fix de 2 inconsistencias reales** encontradas en revisión de código: extensión `ldap` faltante en `Dockerfile` (agregada), y 11 archivos que exponían `$e->getMessage()` al usuario (reemplazados por `manejar_error_bd()` en `src/auth/sesion.php`).
2. **`ROLES_Y_PERMISOS.md`** (raíz del repo) — quién usa el sistema, qué rol hace qué, y un hallazgo real: el menú (`MENU_POR_ROL`) no siempre coincide con el `verificar_rol()` real de cada archivo (Admin_Servidor y Operador tienen acceso de código a módulos que no ven en su menú). Documentado, no corregido — no se pidió.
3. **`DESIGN.md`** (raíz del repo) — sistema visual completo: paleta, componentes (`.panel`, `.badge`, `.punto-estado`, `.indicador-estado`), íconos SVG de línea por rol y por módulo (`ICONOS_ROL`/`ICONOS_MODULO` en `constantes.php`), breakpoint móvil (`@media max-width:640px`: sidebar → tab bar fija, tablas → tarjetas vía `data-label`, modales a pantalla completa). **Leer este archivo antes de tocar cualquier interfaz** — ya está enlazado arriba en "UI work".
4. **Rediseño aplicado a los 11 módulos**: dashboard con semáforo de flota + paneles densos; el mismo lenguaje (`.panel` con cabecera+contador) llevado a `flota`, `conductores`, `turnos`, `alertas`, `incidencias`, `usuarios`, `reportes`, `mantenimiento`; `telemetria` reenfocado en el mapa (mapa a 520px, chips de estado, historial degradado a panel secundario); vista móvil responsive aplicada a los 3 módulos que usa el rol Conductor (`turnos`, `incidencias`, `perfil`).
5. Verificado en vivo contra Docker con los 5 roles reales (capturas de pantalla escritorio + móvil vía Edge headless) — sin errores PHP en logs.

**Pendiente / no resuelto a propósito (mencionado al usuario, sin acción pedida):**
- `telemetria/index.php` carga Leaflet + tiles de OpenStreetMap desde CDN externo (`unpkg.com`) — no funcionará si el servidor de producción no tiene salida a internet. Está comentado en el propio archivo.
- El desfase entre `MENU_POR_ROL` y los `verificar_rol()` reales (punto 2 arriba) sigue sin sincronizar.
- El usuario preguntó si quería empujar el diseño hacia un sidebar oscuro/mayor densidad (inspirado en una referencia tipo Groundhog SIC & FMS) y respondió que el diseño actual está bien — no se tocó la paleta ni la densidad general.
