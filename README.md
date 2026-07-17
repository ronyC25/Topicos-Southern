# FleetCore — Sistema de Gestión de Flota (SPCC)

Aplicación web PHP servida con Docker en desarrollo y XAMPP en el servidor real.

---

## Requisitos (desarrollo local)

- Docker Desktop instalado y corriendo
- Nada más — ni XAMPP, ni MySQL, ni PHP local

## Levantar el entorno

```bash
docker compose up -d
```

La primera vez tarda unos minutos (descarga imágenes e importa la BD automáticamente).

| Servicio | URL |
|---|---|
| Aplicación | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 (root / root) |

## Usuarios de prueba

| Usuario | Contraseña | Rol | Tipo |
|---|---|---|---|
| admin.servidor | Desarrollo_2026* | Admin_Servidor | AD (simulado) |
| admin.bd | Desarrollo_2026* | Admin_BD | AD (simulado) |
| admin.telemetria | Desarrollo_2026* | Admin_Telemetria | AD (simulado) |
| operador.prueba | Prueba_2026* | Operador | local (bcrypt) |
| conductor.prueba | Prueba_2026* | Conductor | local (bcrypt) |

> Los 3 admins usan la contraseña de desarrollo porque en local no existe
> Active Directory (MODO_DESARROLLO=true en docker-compose.yml). En el
> servidor real validan contra AD via LDAP con su contraseña de dominio.

## Comandos útiles

```bash
docker compose up -d          # Levantar
docker compose down           # Detener
docker compose down -v        # Detener Y BORRAR la base de datos (reimporta al subir)
docker compose logs -f web    # Ver errores de PHP en vivo
```

---

## Cómo desarrollar un módulo

Cada módulo vive en `src/modulos/<nombre>/`. Los cambios en `src/` se ven
al instante en el navegador (volumen montado), sin reiniciar nada.

**Patrón obligatorio** — todo archivo de módulo empieza así:

```php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';
verificar_rol([ROL_OPERADOR, ROL_ADMIN_SERVIDOR]);   // roles permitidos
```

Y la página se arma así:

```php
$titulo_pagina = 'Mi Módulo';
require_once __DIR__ . '/../../includes/header.php';
// ... HTML del módulo ...
require_once __DIR__ . '/../../includes/footer.php';
```

**Ver `src/modulos/dashboard/index.php` como ejemplo completo del patrón.**

### Reglas de seguridad obligatorias

1. **Consultas SIEMPRE preparadas** — nunca concatenar variables en SQL:
   ```php
   $stmt = $pdo->prepare("SELECT * FROM turnos WHERE id_turno = ?");
   $stmt->execute([$id]);
   ```
2. **Toda salida de datos con `e()`** (previene XSS):
   ```php
   <td><?= e($fila['descripcion']) ?></td>
   ```
3. **Formularios que modifican datos llevan token CSRF:**
   ```php
   <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
   // Y al procesar el POST:
   validar_csrf();
   ```
4. **Roles con las constantes** de `config/constantes.php`, nunca strings sueltos.

### Roles y módulos

| Rol | Módulos visibles |
|---|---|
| Admin_Servidor | dashboard, flota, conductores, mantenimiento, reportes, usuarios, perfil |
| Admin_BD | dashboard, mantenimiento, reportes, perfil |
| Admin_Telemetria | dashboard, telemetria, alertas, reportes, perfil |
| Operador | dashboard, flota, turnos, alertas, incidencias, perfil |
| Conductor | turnos, incidencias, perfil |

(El menú lateral se genera solo desde `MENU_POR_ROL` en constantes.php —
no hay que programarlo en cada módulo.)

---

## Estructura

```
fleetcore/
├── docker-compose.yml        Entorno de desarrollo
├── Dockerfile                PHP 8.2 + Apache + PDO MySQL + LDAP
├── sql/
│   ├── 01_spcc_database_v3.sql          BD completa (español)
│   └── 02_ajustes_y_datos_prueba.sql    Tokens + usuarios y datos de prueba
└── src/                      ← Esto es lo que va a htdocs en el servidor
    ├── index.php             Login
    ├── logout.php
    ├── acceso_denegado.php
    ├── config/               conexion.php (PDO) · constantes.php
    ├── auth/                 sesion.php · autenticar.php · ldap.php
    ├── includes/             header.php · sidebar.php · footer.php
    ├── assets/               css · js · img
    └── modulos/              dashboard (ejemplo) + 10 carpetas por desarrollar
```

---

## Despliegue final en el servidor real (SRV-DISPATCH-01)

1. Copiar **solo la carpeta `src/`** a `C:\xampp\htdocs\fleetcore\`
2. Importar los dos scripts de `sql/` en el MySQL del XAMPP (phpMyAdmin)
   — **sin** los INSERT de usuarios/datos de prueba si ya es producción
3. Crear el usuario MySQL restringido:
   ```sql
   CREATE USER 'spcc_app'@'localhost' IDENTIFIED BY 'AppSpcc_2026*';
   GRANT SELECT, INSERT, UPDATE, DELETE ON spcc.* TO 'spcc_app'@'localhost';
   FLUSH PRIVILEGES;
   ```
4. Habilitar LDAP en PHP: descomentar `extension=ldap` en `C:\xampp\php\php.ini`
   y reiniciar Apache
5. **No definir MODO_DESARROLLO** — al no existir la variable, el login de los
   admins usa LDAP real contra spcc.local automáticamente
6. Acceso para todos: `http://192.168.10.10:8080/fleetcore`
