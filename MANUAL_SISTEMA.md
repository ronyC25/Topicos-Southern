# FleetCore — Cómo funciona el sistema (guía para el equipo)

Este documento explica el sistema completo para alguien que **no** participó en el desarrollo a fondo: qué es FleetCore, quién lo usa, qué hace cada rol, cómo fluyen los datos entre módulos, cómo se despliega, y qué limitaciones o puntos pendientes hay que tener en cuenta. No sustituye al código ni a `CLAUDE.md`/`DESIGN.md`/`ROLES_Y_PERMISOS.md` (que son la referencia técnica exacta) — es la puerta de entrada para orientarse antes de leer esos documentos o el código.

---

## 1. Qué es FleetCore

FleetCore es el sistema interno de **gestión y despacho de flota** de una operación minera (SPCC): asigna conductores a vehículos por turno, registra su posición y velocidad, genera alertas cuando algo se sale de los límites definidos (velocidad, descansos, etc.), controla incidencias reportadas en ruta, y programa el mantenimiento de los vehículos. Es una aplicación web interna — no pública, no orientada a clientes — pensada para operar en la red local de la mina (o vía VPN/red interna) y consultarse desde escritorios de sala de control y desde celulares de conductor.

Es una aplicación PHP simple y directa: sin framework, sin build step, sin dependencias externas de paquetes. Todo el código es legible archivo por archivo — no hay "magia" de un framework que buscar en otro lado.

## 2. Los 5 roles y quién los usa

| Rol | Quién es en la vida real | Qué resuelve para esa persona |
|---|---|---|
| **Admin_Servidor** | Administrador general de sistemas | Gestiona usuarios, flota y conductores; supervisión general de turnos e incidencias; ve reportes y mantenimiento. Es el rol con más alcance. |
| **Admin_BD** | Administrador de base de datos | Se enfoca solo en mantenimiento de vehículos y reportes — no toca usuarios, flota, turnos ni alertas. |
| **Admin_Telemetria** | Encargado de monitoreo en tiempo real (sala de control) | Vive en el mapa de telemetría y en la cola de alertas (exceso de velocidad, descansos, etc.); las resuelve o descarta. |
| **Operador** | Personal de despacho / operaciones (día a día) | Asigna turnos (conductor + vehículo), gestiona la flota, atiende alertas e incidencias, exporta reportes. Es el rol más "operativo". |
| **Conductor** | El chofer del camión, entra desde el celular | Ve su turno activo, registra descansos, puede pausar/finalizar su turno, y reporta incidencias. Es el rol más restringido — no ve dashboard, flota, telemetría, alertas ni reportes. |

**Detalle importante de autenticación:** los 3 roles administrativos se autentican contra Active Directory real (`spcc.local` vía LDAP) en el servidor de producción. Operador y Conductor son cuentas locales (usuario/contraseña propios de la app, con bcrypt). Esto es independiente del rol — técnicamente se podría crear un Operador como cuenta AD o un admin como cuenta local, pero en la práctica no se usa así.

Para el detalle exacto de qué módulos ve cada rol y qué acción puede ejecutar en cada uno (con referencias de archivo), ver **`ROLES_Y_PERMISOS.md`** — esa tabla es la fuente autoritativa, extraída directamente del código (`verificar_rol()` de cada módulo), no una descripción aproximada.

## 3. Los 11 módulos, en una frase cada uno

| Módulo | Qué hace |
|---|---|
| **Dashboard** | Resumen general: semáforo de estado de flota, turnos en curso, últimas alertas. Punto de entrada para los 4 roles no-Conductor. |
| **Flota** | Alta/edición/baja de vehículos (id_camion, estado: Operativo / Mantenimiento / Fuera_Servicio). |
| **Conductores** | Alta/edición/baja de conductores (dato maestro, solo Admin_Servidor). |
| **Turnos** | El módulo central del sistema: asigna un conductor + un vehículo por un período de tiempo. Todo lo demás (telemetría, descansos, incidencias, alertas) cuelga de un turno. |
| **Telemetría** | Mapa con la posición/velocidad reportada durante cada turno. El propio celular del conductor envía sus coordenadas mientras su turno está `Activo` — ver sección 5 y sección 9 (requiere HTTPS en el servidor real). |
| **Alertas** | Cola de eventos generados automáticamente cuando algo excede un límite (velocidad, descanso, mantenimiento, geocercas, combustible, GPS) — se marcan como Resuelta o Descartada. |
| **Incidencias** | Reportes manuales de eventos en ruta (accidente, falla, etc.), con flujo de atención Pendiente → En_Revision → Resuelta/Cerrada. |
| **Mantenimiento** | Programación de mantenimientos preventivos/correctivos por vehículo, más tickets de atención asociados. |
| **Reportes** | Exportación CSV de turnos, mantenimientos e incidencias. |
| **Usuarios** | Alta/edición/baja de cuentas del sistema y asignación de rol — exclusivo de Admin_Servidor. |
| **Mi Perfil** | Cada usuario edita sus propios datos. Único módulo visible para los 5 roles. |

## 4. El modelo de datos, en una imagen mental

Todo cuelga de una tabla central: **`turnos`** (conductor + vehículo + ventana de tiempo). A partir de un turno:

```
usuarios ──(reportado_por)──► incidencias
                                   ▲
conductores ──┐                   │
              ├──► turnos ────────┼──► descansos
vehiculos ────┘        │          │
                        ├──► telemetria (posición/velocidad por turno)
                        └──► alertas (puede o no estar ligada a un turno)

vehiculos ──► mantenimientos ──► tickets_atencion
vehiculos ──► limites_operacion (velocidad máx, tiempo de manejo máx, descanso mín — por vehículo)

(todas las escrituras relevantes quedan también en `auditoria`: quién, qué tabla, acción, antes/después, IP)
```

Puntos a notar:
- Borrar un conductor o vehículo borra en cascada (`ON DELETE CASCADE`) sus turnos, telemetría, descansos, etc. — es una decisión de diseño de la BD, no un bug; hay que tenerlo presente antes de eliminar un registro maestro en producción.
- `incidencias.reportado_por` apunta a `usuarios.nombre_usuario`; si se borra el usuario, la incidencia queda con `reportado_por = NULL` (`ON DELETE SET NULL`), no se pierde el registro.
- La tabla `auditoria` existe en el esquema pero conviene verificar en el código actual qué operaciones la alimentan realmente antes de asumir que todo movimiento queda registrado ahí.

## 5. Ciclo de vida típico de un turno (cómo interactúan los roles entre sí)

1. **Operador** (o Admin_Servidor) inicia un turno asignando un conductor y un vehículo. El sistema valida que ninguno de los dos tenga ya un turno activo.
2. **Conductor** ve su turno desde el celular, registra descansos reglamentarios, y puede pausar/finalizar el turno — pero no puede iniciar uno nuevo por su cuenta.
3. Durante el turno se generan registros de **telemetría** (posición/velocidad) y, si algo excede un límite configurado en `limites_operacion`, una **alerta**.
4. **Admin_Telemetria** monitorea el mapa y la cola de alertas, y las marca como Resuelta o Descartada.
5. Si ocurre un evento en ruta, el **Conductor** (o el Operador) reporta una **incidencia**. Solo **Operador** o **Admin_Servidor** pueden mover su estado de atención — el conductor únicamente reporta, no cierra el caso.
6. **Admin_BD** o **Admin_Servidor** gestionan el **mantenimiento** del vehículo (independiente del turno puntual), generando tickets de atención si hace falta.
7. Cualquiera de los roles con acceso a **Reportes** exporta CSV de turnos/mantenimientos/incidencias para análisis fuera del sistema.

## 6. Cómo se despliega (dos entornos, un mismo código)

La misma carpeta `src/` corre sin modificaciones en dos contextos distintos — esto es una decisión de diseño explícita, no una coincidencia:

### Desarrollo (Docker, en la laptop de cualquier dev)
```bash
docker compose up -d
```
- App en `http://localhost:8080`, phpMyAdmin en `http://localhost:8081` (root/root).
- La BD se importa sola la primera vez desde `sql/01_spcc_database_v3.sql` (esquema) + `sql/02_ajustes_y_datos_prueba.sql` (usuarios y datos de prueba).
- `MODO_DESARROLLO=true` en `docker-compose.yml` simula el login de los 3 admins (AD) con la contraseña fija `Desarrollo_2026*`, porque en local no hay un Active Directory real contra el cual autenticar.
- Los cambios en `src/` se reflejan al instante (volumen montado) — no hay que reconstruir la imagen para probar un cambio de PHP.
- `docker compose down -v` borra la base de datos completa (reimporta al volver a subir) — usar con cuidado, es destructivo.

### Producción (servidor real `SRV-DISPATCH-01`, XAMPP)
1. Se copia **solo** la carpeta `src/` a `C:\xampp\htdocs\fleetcore\`.
2. Se importan los mismos dos scripts SQL en el MySQL de XAMPP — en producción real, sin los INSERT de usuarios/datos de prueba.
3. Se crea un usuario MySQL restringido (`spcc_app`, solo SELECT/INSERT/UPDATE/DELETE, sin privilegios de administración) — la app nunca se conecta como root.
4. Se habilita la extensión LDAP de PHP (`extension=ldap` en `php.ini`) y se reinicia Apache.
5. **No se define `MODO_DESARROLLO`** — al no existir esa variable, el login de los 3 admins usa LDAP real contra `spcc.local` automáticamente. Es el mismo código de login en ambos entornos; lo que cambia es una variable de entorno.
6. Se habilita HTTPS con un certificado autofirmado sobre la IP (puerto `8443`) — requisito para que la telemetría móvil funcione (ver sección 9). Instrucciones exactas en `README.md`, sección "HTTPS para telemetría móvil".
7. Se accede vía `http://192.168.10.10:8080/fleetcore` (escritorio) o `https://192.168.10.10:8443/fleetcore` (conductor, celular) — red interna, no expuesto a internet público.

`base_url()` (en `src/auth/sesion.php`) es lo que permite que el mismo código funcione en la raíz (Docker, `/`) o en un subdirectorio (XAMPP, `/fleetcore`) — detecta el contexto automáticamente a partir de `SCRIPT_NAME`, así que nunca hay que hardcodear una ruta.

## 7. Seguridad — qué está implementado y por qué importa

- **Todo el SQL usa consultas preparadas (PDO)** — nunca se concatena input del usuario en una query. Previene inyección SQL.
- **Toda salida a HTML pasa por `e()`** (un wrapper de `htmlspecialchars`) — previene XSS.
- **Todo formulario que modifica datos exige un token CSRF** (`csrf_token()` / `validar_csrf()`) — previene que un sitio externo dispare una acción en nombre del usuario logueado.
- **Los mensajes de error de base de datos nunca se muestran al usuario** — se registran en el log del servidor (`error_log`) y el usuario ve un mensaje genérico. Esto evita filtrar detalles internos (nombres de tabla, estructura de queries) que ayudarían a un atacante.
- **Sesión con `httponly` + `strict_mode`**, y expira a los 30 minutos de inactividad.
- **Autenticación dual por usuario**: cada cuenta es `local` (bcrypt) o `active_directory` (LDAP real), es un campo por usuario, no un modo global del sistema.
- **El acceso a cada módulo se valida en el propio archivo** (`verificar_rol()`), no solo ocultando el link del menú — es decir, aunque un rol no vea un módulo en su sidebar, si intenta acceder por URL directa, el código lo bloquea (o no, si hay un desfase — ver sección 9).

## 8. Frontend — qué hay y qué no hay

- Un único stylesheet (`estilos.css`) con un sistema visual documentado a fondo en `DESIGN.md`: paleta de colores fija, 4 colores de estado (verde/amarillo/rojo/gris) para todo el sistema, componentes reutilizables (`.panel`, `.tarjeta`, `.badge`, `.modal`, `.punto-estado`). Cualquiera que toque una interfaz debe leer ese documento antes — no se inventan colores ni componentes nuevos.
- No hay React/Vue/librería de frontend — HTML generado por PHP + JS plano puntual (abrir/cerrar modales, mapa de telemetría, envío de geolocalización del conductor vía `fetch()`).
- El módulo de **Telemetría** usa **Leaflet + tiles de OpenStreetMap desde un CDN externo** (`unpkg.com`, `tile.openstreetmap.org`). Esto requiere que el servidor tenga salida a internet — confirmado que la tiene en el servidor real, así que no es un problema actualmente, pero es una dependencia externa a tener presente si algún día se restringe la red de la mina.
- Vista móvil: un solo `@media (max-width: 640px)` al final de `estilos.css` que convierte el sidebar en tab bar inferior y las tablas en tarjetas apiladas. Aplicado principalmente a los 3 módulos que usa Conductor (`turnos`, `incidencias`, `perfil`), porque ese rol entra típicamente desde el celular.

## 9. Puntos importantes / limitaciones a tener presentes

- **La telemetría en vivo ahora sí se alimenta sola, desde el celular del conductor** (actualizado 2026-07-19). Mientras el Conductor tiene un turno en estado `Activo`, `turnos/index.php` corre `navigator.geolocation.watchPosition()` y manda su posición (máximo cada 15s) a `turnos/registrar_ubicacion.php`, que valida su turno activo y hace el `INSERT` en `telemetria` con el `id_turno`/`id_camion` correctos. `telemetria/index.php` no cambió — sigue siendo de solo lectura, simplemente ahora sí hay filas reales que leer. El comentario viejo del código ("insertado por el hardware de los camiones") ya no aplica y fue corregido.
  - **Requiere HTTPS en el servidor real.** `navigator.geolocation` solo funciona en un "contexto seguro" (`https://` o `localhost`) — en Docker funciona gratis porque `localhost` está exento, pero en `http://192.168.10.10:8080` el navegador del celular bloquea la API. Ver `README.md`, sección "HTTPS para telemetría móvil", para los pasos de certificado autofirmado (puerto `8443`) — sin ese paso, esta funcionalidad no corre en producción aunque el código esté bien.
  - **Hallazgo de datos de prueba, no corregido a propósito:** la cuenta `conductor.prueba` tiene `dni = NULL` en `sql/02_ajustes_y_datos_prueba.sql`, así que no puede ver ningún turno propio (ni antes ni después de este cambio) — para probar este flujo en un navegador real hace falta corregir ese `dni` en la semilla, o loguearse con un conductor real que sí tenga turno activo.
- **No hay test suite, linter ni CI.** La verificación de cualquier cambio es manual: levantar Docker y probar el flujo afectado con los 5 roles.
- **`MENU_POR_ROL` (qué ve cada rol) y `verificar_rol()` (qué puede hacer cada rol) fueron corregidos para estar sincronizados** en una revisión reciente — antes había desfases (ej. Admin_Servidor tenía acceso de código a telemetría sin verla en su menú). Si se agrega un módulo o se cambia un permiso, hay que actualizar ambos lados a la vez, o se vuelve a introducir ese mismo tipo de desfase.
- **Los mensajes de error de BD ya fueron corregidos en 11 archivos** para no exponer `$e->getMessage()` al usuario — si se escribe código nuevo, seguir el patrón de `manejar_error_bd()` (`src/auth/sesion.php`), nunca reintroducir el patrón antiguo.
- **Credenciales de BD:** el fallback de `DB_USER`/`DB_PASS` en `src/config/conexion.php` es `root`/`` (vacío, sin contraseña) — esto fue confirmado con el responsable como las credenciales reales configuradas en el servidor de producción actual. No cambiar sin coordinar, porque rompería el acceso a la BD en producción.
- **`fleetcore_produccion.zip` y `Reiniciar Server 1.pdf`** en la raíz del repo son artefactos de build/operación (no código fuente) — no forman parte del árbol `src/` que se despliega, y no hace falta abrirlos para entender el sistema.
- **Sin Composer ni gestor de paquetes**: cualquier librería PHP nueva se agrega copiando el archivo o vía extensión de PHP (como se hizo con `ldap`) — no `composer require`.

## 10. Dónde seguir leyendo

| Pregunta | Documento / archivo |
|---|---|
| "¿Qué puede hacer exactamente el rol X en el módulo Y?" | `ROLES_Y_PERMISOS.md` |
| "¿Qué clase CSS uso para un badge/tabla/modal nuevo?" | `DESIGN.md` |
| "¿Cómo levanto el entorno y qué usuarios de prueba hay?" | `README.md` |
| "¿Cuál es el patrón obligatorio de un módulo nuevo?" | `CLAUDE.md` (sección "Mandatory module boilerplate") o el skill `nuevo-modulo` |
| "¿Qué tablas/columnas existen exactamente?" | `sql/01_spcc_database_v3.sql` (esquema) |
