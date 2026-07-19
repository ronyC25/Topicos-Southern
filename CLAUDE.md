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

Todo lo de abajo ya está commiteado en `main` (commit `3b57208` — "Rediseno visual completo estilo sala de control + fixes de seguridad").

**Hecho:**
1. **Fix de 2 inconsistencias reales** encontradas en revisión de código: extensión `ldap` faltante en `Dockerfile` (agregada), y 11 archivos que exponían `$e->getMessage()` al usuario (reemplazados por `manejar_error_bd()` en `src/auth/sesion.php`).
2. **`ROLES_Y_PERMISOS.md`** (raíz del repo) — quién usa el sistema, qué rol hace qué, y un hallazgo real: el menú (`MENU_POR_ROL`) no siempre coincide con el `verificar_rol()` real de cada archivo (Admin_Servidor y Operador tienen acceso de código a módulos que no ven en su menú). Documentado, no corregido — no se pidió.
3. **`DESIGN.md`** (raíz del repo) — sistema visual completo: paleta, componentes (`.panel`, `.badge`, `.punto-estado`, `.indicador-estado`), íconos SVG de línea por rol y por módulo (`ICONOS_ROL`/`ICONOS_MODULO` en `constantes.php`), breakpoint móvil (`@media max-width:640px`: sidebar → tab bar fija, tablas → tarjetas vía `data-label`, modales a pantalla completa). **Leer este archivo antes de tocar cualquier interfaz** — ya está enlazado arriba en "UI work".
4. **Rediseño aplicado a los 11 módulos**: dashboard con semáforo de flota + paneles densos; el mismo lenguaje (`.panel` con cabecera+contador) llevado a `flota`, `conductores`, `turnos`, `alertas`, `incidencias`, `usuarios`, `reportes`, `mantenimiento`; `telemetria` reenfocado en el mapa (mapa a 520px, chips de estado, historial degradado a panel secundario); vista móvil responsive aplicada a los 3 módulos que usa el rol Conductor (`turnos`, `incidencias`, `perfil`).
5. Verificado en vivo contra Docker con los 5 roles reales (capturas de pantalla escritorio + móvil vía Edge headless) — sin errores PHP en logs.

**Actualización 2026-07-18:** un compañero descargó el repo, aplicó otro pase de rediseño visual (login/dashboard/header/sidebar con animaciones, favicon, `global.js`) y subió el commit `1db802d` ("Actualización de diseños"), ya traído a este working tree via `git pull`. Revisión de ese diff encontró:
- `src/config/conexion.php` cambió el fallback de `DB_USER`/`DB_PASS` de `spcc_app`/`AppSpcc_2026*` a `root`/`` (vacío) — **confirmado con el usuario que son las credenciales reales que están configuradas en el servidor actualmente, no tocar.**
- El módulo trajo ~30 colores hex nuevos no documentados en `DESIGN.md` — pendiente de reflejar en la paleta (ver DESIGN.md, sección de paleta ampliada).
- `telemetria/index.php` sigue dependiendo de Leaflet/OpenStreetMap vía CDN externo, pero el usuario confirmó que el servidor de producción **sí tiene salida a internet** (comparte datos de un teléfono; configuración de red documentada en `Reiniciar Server 1.pdf`, que no se lee/edita por instrucción de este archivo). No es un problema real, se puede dejar de mencionar como pendiente.
- El desfase entre `MENU_POR_ROL` y los `verificar_rol()` reales — **ya corregido**, ver `ROLES_Y_PERMISOS.md` sección 4: se agregó `turnos`/`incidencias` al menú de Admin_Servidor y `reportes` al de Operador; se removió el acceso de código de Admin_Servidor a `telemetria` (queda exclusivo de Admin_Telemetria).

**Pendiente / no resuelto a propósito (mencionado al usuario, sin acción pedida):**
- El usuario preguntó si quería empujar el diseño hacia un sidebar oscuro/mayor densidad (inspirado en una referencia tipo Groundhog SIC & FMS) y respondió que el diseño actual está bien — no se tocó la paleta ni la densidad general.

**Actualización 2026-07-19 — telemetría en vivo desde el celular del conductor:**
- Se creó `MANUAL_SISTEMA.md` (raíz del repo) — documento explicativo del sistema completo para el equipo (roles, módulos, modelo de datos, despliegue, seguridad, limitaciones) pensado para quien no trabajó a fondo en el desarrollo. Punto de entrada antes de leer `ROLES_Y_PERMISOS.md`/`DESIGN.md`/este archivo.
- Al revisar el módulo de Telemetría con el usuario, se confirmó que **no existía ninguna ingesta real de GPS** — `telemetria/index.php` solo leía la tabla, y un comentario en el código asumía (incorrectamente) que "el hardware de los camiones" insertaba los datos. La idea real del usuario: el celular del conductor, logueado con su rol, empieza a enviar su posición en cuanto su turno pasa a `Activo`.
- **Implementado:** `src/modulos/turnos/registrar_ubicacion.php` (endpoint nuevo, solo rol Conductor + CSRF, valida turno `Activo` propio antes de insertar en `telemetria`) + JS en `src/modulos/turnos/index.php` (`navigator.geolocation.watchPosition()`, throttle de 15s, solo se renderiza si el Conductor tiene turno activo). Verificado end-to-end en Docker con curl real (login, iniciar turno, POST de ubicación, verificación en `telemetria/index.php`, casos de error CSRF/coordenadas/rol) — sin tocar datos de prueba de forma permanente.
- **Bloqueante real descubierto:** `navigator.geolocation` exige un "contexto seguro" (`https://` o `localhost`) — en Docker funciona gratis, pero en el servidor real (`http://192.168.10.10:8080`, HTTP plano) el navegador del celular lo bloquearía. Se decidió con el usuario (vía pregunta explícita) resolverlo con un **certificado autofirmado directo sobre la IP** (no CA local/mkcert, no Let's Encrypt — no hay control confirmado del DNS interno). Instrucciones completas documentadas en `README.md`, sección "HTTPS para telemetría móvil" (puerto `8443`, comando `openssl` con `subjectAltName=IP:...` ya validado dentro de un contenedor).
- **Hallazgo de datos de prueba, no corregido a propósito:** `conductor.prueba` tiene `dni = NULL` en `sql/02_ajustes_y_datos_prueba.sql` — nunca puede ver un turno propio (independiente de este cambio). Si alguien quiere demostrar este flujo en un navegador real, hay que corregir ese `dni` en la semilla o usar un conductor con turno activo real.
- **Pendiente mencionado, no resuelto:** una vez HTTPS esté andando en el servidor real, apagar/dejar de usar el acceso HTTP puro (8080) para el login, porque hoy las contraseñas viajan sin cifrar por la red de la mina. No se pidió, no se tocó.
