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
6. Habilitar HTTPS (**obligatorio** para que Telemetría funcione desde el celular
   del conductor — ver "HTTPS para telemetría móvil" más abajo)
7. Acceso para todos: `http://192.168.10.10:8080/fleetcore`
   Acceso para Conductor (telemetría): `https://192.168.10.10:8443/fleetcore`
8. Avisar a los conductores que, durante el turno, deben dejar el navegador
   abierto en la página de Turnos y la pantalla del celular encendida — ver
   "Telemetría móvil — la pantalla tiene que quedar encendida" más abajo,
   es un requisito real de cómo funciona el envío de GPS, no un detalle menor.

---

## HTTPS para telemetría móvil (por qué y cómo)

El módulo de Telemetría envía la ubicación del conductor automáticamente desde
el navegador de su celular mientras su turno está `Activo` (`turnos/index.php`
+ `turnos/registrar_ubicacion.php`, usando `navigator.geolocation`). Los
navegadores solo permiten esta API en un **contexto seguro**: `https://` o
`localhost`. En Docker (`http://localhost:8080`) funciona sin hacer nada
porque `localhost` está exento. En el servidor real, sobre `http://192.168.10.10:8080`,
el navegador del celular **bloquea la geolocalización** — por eso hace falta
HTTPS ahí, aunque sea con un certificado autofirmado.

No hay dominio público ni salida entrante a internet para este servidor, así
que se usa un **certificado autofirmado directo sobre la IP** (no una CA
pública, no un hostname interno) — cada celular acepta la advertencia de
"conexión no privada" una sola vez y el navegador lo recuerda.

### Pasos en el servidor (una sola vez)

**1. Generar el certificado** (10 años de vigencia) con el OpenSSL que trae XAMPP:
```cmd
cd C:\xampp\apache\bin
openssl req -x509 -nodes -newkey rsa:2048 -keyout dispatch.key -out dispatch.crt -days 3650 -subj "/CN=192.168.10.10" -addext "subjectAltName=IP:192.168.10.10"
```
El `subjectAltName=IP:...` es obligatorio — Chrome y Safari rechazan certificados
para una IP que solo tengan el `CN` sin `SAN`.

**2. Copiar el certificado a las carpetas de Apache:**
```cmd
copy dispatch.crt C:\xampp\apache\conf\ssl.crt\
copy dispatch.key C:\xampp\apache\conf\ssl.key\
```

**3. Habilitar `mod_ssl`** — en `C:\xampp\apache\conf\httpd.conf`, descomentar:
```
LoadModule ssl_module modules/mod_ssl.so
Include conf/extra/httpd-ssl.conf
```

**4. Editar `C:\xampp\apache\conf\extra\httpd-ssl.conf`** — usar el puerto `8443`
(mismo patrón que el 8080 en vez del 80 estándar, para no chocar con nada que
ya use el 443 en ese Windows):
```
Listen 8443
<VirtualHost _default_:8443>
    DocumentRoot "C:/xampp/htdocs"
    ServerName 192.168.10.10:8443
    SSLEngine on
    SSLCertificateFile "conf/ssl.crt/dispatch.crt"
    SSLCertificateKeyFile "conf/ssl.key/dispatch.key"
</VirtualHost>
```

**5. Reiniciar Apache** desde el Panel de Control de XAMPP (Stop → Start). Si no
arranca, revisar `C:\xampp\apache\logs\error.log` (lo más común: puerto ocupado
o ruta del certificado mal escrita).

**6. Probar desde el celular del conductor:** abrir `https://192.168.10.10:8443/fleetcore`.
Va a salir "conexión no privada" — es esperado por ser autofirmado. Tocar
**Avanzado → Continuar de todas formas** (Chrome/Android) o **Mostrar detalles
→ visitar este sitio web** (Safari/iOS). El navegador recuerda la excepción
por dispositivo — no hay que repetirlo en cada visita.

**7. (Opcional, elimina la advertencia) instalar el certificado como confiable**
en cada celular:
- Android: enviar `dispatch.crt` al teléfono → Ajustes → Seguridad → Cifrado
  y credenciales → Instalar un certificado (CA).
- iPhone: enviar el archivo (AirDrop/correo) → abrirlo instala un perfil →
  Ajustes → General → Información → Confianza de certificados → activarlo.

### Pendiente relacionado, no resuelto a propósito
Con HTTPS ya disponible, en algún momento conviene apagar el acceso HTTP puro
(8080) o dejar de usarlo para el login — hoy las contraseñas viajan sin cifrar
por la red de la mina. No se hizo como parte de este cambio porque no fue lo
pedido; es una mejora de seguridad natural una vez esto esté funcionando.

## Telemetría móvil — la pantalla tiene que quedar encendida

El envío de ubicación (`turnos/index.php` + `turnos/registrar_ubicacion.php`,
`navigator.geolocation.watchPosition()`) es JavaScript corriendo en la pestaña
del navegador del celular mientras el turno está `Activo`. Esto **solo
funciona con la página abierta y en primer plano**:

- Si el conductor cambia a otra app, bloquea la pantalla, o cierra el
  navegador, Android/iOS pausan la ejecución de JS en segundo plano — el
  envío de GPS se corta hasta que la página vuelve a primer plano.
- No es un bug puntual ni algo que se arregle con un ajuste chico de código:
  una página web (a diferencia de una app nativa) no tiene forma confiable de
  seguir mandando ubicación con la pantalla apagada. Arreglarlo de verdad
  requeriría una app nativa (Android/iOS) — está fuera del alcance actual del
  proyecto (PHP plano, sin build step, sin apps nativas).
- **Indicación operativa para los conductores:** dejar el celular con la
  página de Turnos abierta y la pantalla encendida durante todo el turno.
  Si hace falta evitar que la pantalla se apague sola por inactividad, se
  puede evaluar agregar la [Screen Wake Lock
  API](https://developer.mozilla.org/docs/Web/API/Screen_Wake_Lock_API) al
  frontend — no resuelve el caso de cambiar de app, pero sí el de que el
  celular apague la pantalla solo. No implementado todavía, mencionado al
  usuario y quedó pendiente a pedido explícito (se priorizó documentar la
  limitación antes que resolverla).

### HTTPS para telemetría móvil — probarlo en local antes de desplegar

Docker corre en `http://localhost:8080`, y `localhost` está exento del
requisito de "contexto seguro" de `navigator.geolocation` — por eso el
bloqueo de geolocalización que sí ocurre en el servidor real (HTTP plano
sobre una IP) **no se reproduce solo con `docker compose up`**. Para
probarlo de verdad antes de desplegar, el repo ya trae el soporte HTTPS
armado (mismo patrón que arriba, sobre Docker):

1. Generar el certificado con tu propia IP de LAN (`ipconfig`, adaptador
   Wi-Fi) — **no** commitear esto, `docker-ssl/` está en `.gitignore`
   porque el certificado es específico de la IP de quien lo genera:
   ```cmd
   openssl req -x509 -nodes -newkey rsa:2048 -keyout docker-ssl/dispatch.key -out docker-ssl/dispatch.crt -days 3650 -subj "/CN=TU_IP_LAN" -addext "subjectAltName=IP:TU_IP_LAN"
   ```
2. `docker compose up -d --build` — el `Dockerfile` ya habilita `mod_ssl`
   y `docker/ssl.conf` (sí versionado, sin datos sensibles) sirve HTTPS en
   el puerto 443 del contenedor, mapeado a `8443` en `docker-compose.yml`.
3. Windows bloquea por defecto las conexiones entrantes al puerto de Docker
   desde otros dispositivos de la red — no existe ninguna regla para
   Docker/8080/8443 de fábrica. Agregar una regla de firewall (una sola vez,
   PowerShell **como administrador**, acotada al perfil de red Privado):
   ```powershell
   New-NetFirewallRule -DisplayName "FleetCore Docker HTTPS (8443, prueba local)" -Direction Inbound -Protocol TCP -LocalPort 8443 -Action Allow -Profile Private
   ```
4. Desde el celular, en la misma Wi-Fi: `https://TU_IP_LAN:8443/`, aceptar
   la advertencia de certificado autofirmado, loguear como un Conductor con
   turno `Activo` (el `dni` de `usuarios` debe coincidir con el `dni` de
   `conductores` — si no, nunca va a ver su turno, es el mismo problema que
   tiene `conductor.prueba` con `dni = NULL`, ver más abajo).

**Si el navegador nunca pide permiso de ubicación ni en local ni en el
servidor real, no asumas que es un bug de código** — primero revisar en el
celular: Ubicación del sistema operativo activada, permiso de Chrome/Safari
a nivel de app, y la configuración de sitios del navegador
(`chrome://settings/content/location` en Android o el equivalente en iOS)
por si el sitio quedó bloqueado de una prueba anterior. El flujo de servidor
(`turnos/registrar_ubicacion.php` + el JS de `turnos/index.php`) ya está
verificado end-to-end — la causa más común es ambiental, no de código.
