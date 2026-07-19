# FleetCore — Roles, usuarios y permisos

Este documento describe quién usa FleetCore, qué rol tiene cada tipo de usuario, qué funciones puede ejecutar en cada módulo y qué limitaciones tiene. Se basa en el código real (`src/config/constantes.php`, `src/includes/sidebar.php` y el `verificar_rol()` de cada archivo de `src/modulos/`), no en una descripción teórica del sistema.

## 1. Los 5 roles del sistema

FleetCore define 5 roles (`src/config/constantes.php`), pensados para el sistema de despacho y telemetría de la operación minera (SPCC):

| Rol (constante) | Nombre en BD | Quién lo usa en la práctica |
|---|---|---|
| `ROL_ADMIN_SERVIDOR` | `Admin_Servidor` | Administrador general del sistema — gestiona usuarios, flota, conductores, mantenimiento y reportes. |
| `ROL_ADMIN_BD` | `Admin_BD` | Administrador de base de datos — se enfoca en mantenimiento de vehículos y reportes, sin gestionar usuarios ni flota. |
| `ROL_ADMIN_TELEMETRIA` | `Admin_Telemetria` | Encargado de monitoreo en tiempo real — telemetría y alertas de los vehículos. |
| `ROL_OPERADOR` | `Operador` | Personal de despacho/operaciones — gestiona flota, turnos, alertas e incidencias del día a día. |
| `ROL_CONDUCTOR` | `Conductor` | El chofer del camión — solo ve su propio turno, reporta incidencias y su perfil. |

### Autenticación por rol

- Los 3 roles administrativos (`Admin_Servidor`, `Admin_BD`, `Admin_Telemetria`) se crean como cuentas **Active Directory** (`tipo_autenticacion = 'active_directory'`) — autentican contra el dominio `spcc.local` vía LDAP en producción (en Docker/desarrollo, `MODO_DESARROLLO=true` simula esto con la contraseña de prueba `Desarrollo_2026*`).
- `Operador` y `Conductor` normalmente son cuentas **locales** (`tipo_autenticacion = 'local'`), autenticadas con bcrypt contra la tabla `usuarios`. Nada impide crear un Operador o Conductor como cuenta AD, o un admin como cuenta local — el campo es independiente del rol.

## 2. Qué módulos ve cada rol (menú de navegación)

Esto es lo que cada rol ve en la barra lateral (`MENU_POR_ROL` en `src/config/constantes.php`):

| Módulo | Admin_Servidor | Admin_BD | Admin_Telemetria | Operador | Conductor |
|---|:---:|:---:|:---:|:---:|:---:|
| Dashboard | ✅ | ✅ | ✅ | ✅ | ❌ |
| Flota | ✅ | ❌ | ❌ | ✅ | ❌ |
| Conductores | ✅ | ❌ | ❌ | ❌ | ❌ |
| Turnos | ✅ | ❌ | ❌ | ✅ | ✅ |
| Telemetría | ❌ | ❌ | ✅ | ❌ | ❌ |
| Alertas | ❌ | ❌ | ✅ | ✅ | ❌ |
| Incidencias | ✅ | ❌ | ❌ | ✅ | ✅ |
| Mantenimiento | ✅ | ✅ | ❌ | ❌ | ❌ |
| Reportes | ✅ | ✅ | ✅ | ✅ | ❌ |
| Usuarios | ✅ | ❌ | ❌ | ❌ | ❌ |
| Mi Perfil | ✅ | ✅ | ✅ | ✅ | ✅ |

## 3. Qué puede hacer cada rol (funciones por módulo)

Esta tabla resume las **acciones reales que el código permite** en cada módulo, verificadas contra el `verificar_rol()` de cada archivo:

### Admin_Servidor
- **Usuarios**: único rol que puede crear, editar y eliminar cuentas de cualquier tipo (local o AD), asignar roles y activar/desactivar usuarios.
- **Flota**: crear, editar y eliminar vehículos.
- **Conductores**: crear, editar y eliminar conductores.
- **Turnos**: iniciar un turno (asignar conductor + vehículo), cambiar su estado, registrar descansos — mismo alcance que Operador, para supervisión/corrección general.
- **Incidencias**: reportar una incidencia y cambiar su estado de atención — supervisión general.
- **Mantenimiento**: programar mantenimientos, crear/actualizar tickets de atención.
- **Reportes**: exportar CSV de turnos, mantenimientos e incidencias.
- **Perfil**: editar sus propios datos.
- No tiene acceso a Telemetría — es dominio exclusivo de Admin_Telemetria.

### Admin_BD
- **Mantenimiento**: mismas funciones que Admin_Servidor (programar mantenimientos, gestionar tickets).
- **Reportes**: exportar los 3 tipos de reporte.
- **Perfil**: editar sus propios datos.
- No tiene acceso a Flota, Conductores, Turnos, Alertas, Incidencias ni Usuarios.

### Admin_Telemetria
- **Telemetría**: consulta de posición/velocidad por turno.
- **Alertas**: ver y cambiar el estado de una alerta (marcarla como Resuelta o Descartada).
- **Reportes**: exportar los 3 tipos de reporte.
- **Perfil**: editar sus propios datos.
- No gestiona flota, conductores, turnos, incidencias ni usuarios.

### Operador
- **Flota**: crear, editar y eliminar vehículos (igual que Admin_Servidor).
- **Turnos**: iniciar un turno (asignar conductor + vehículo), cambiar su estado (pausar/finalizar/cancelar), registrar descansos.
- **Alertas**: ver y cambiar el estado de una alerta.
- **Incidencias**: reportar una incidencia y cambiar su estado de atención.
- **Reportes**: exportar CSV de turnos, mantenimientos e incidencias — extensión natural de su trabajo diario de despacho.
- **Perfil**: editar sus propios datos.
- No gestiona conductores, mantenimiento, telemetría ni usuarios.

### Conductor
- **Turnos**: ver su turno, cambiar su estado y registrar descansos — pero **no puede iniciar un turno nuevo** (`turnos/guardar.php` solo permite Admin_Servidor y Operador; el conductor no se autoasigna a un vehículo).
- **Telemetría (envío, no visualización)**: mientras su turno está `Activo`, su celular envía su ubicación GPS automáticamente en segundo plano (`turnos/registrar_ubicacion.php`, vía `navigator.geolocation` en `turnos/index.php`) — el Conductor no ve el módulo de Telemetría (sigue siendo exclusivo de Admin_Telemetria), solo alimenta sus datos sin saberlo explícitamente más allá de tener el turno abierto.
- **Incidencias**: puede reportar una incidencia, pero **no puede cambiar su estado de atención** una vez reportada (`incidencias/cambiar_estado.php` excluye explícitamente a Conductor — comentario en el código: *"Los conductores no pueden cambiar el estado de la incidencia, solo reportar"*).
- **Perfil**: editar sus propios datos.
- Es el rol más restringido: sin dashboard, sin flota, sin telemetría, sin alertas, sin reportes, sin gestión de usuarios.

## 4. Menú y permiso de código ya sincronizados

Anteriormente `MENU_POR_ROL` no coincidía con el `verificar_rol()` real de cada archivo: Admin_Servidor tenía acceso de código a `turnos/*`, `incidencias/*` y `telemetria/index.php` sin verlos en su menú, y Operador podía acceder a `reportes/*` sin tener el enlace visible. Esto se corrigió:

- Se agregó `turnos`, `incidencias` al menú de Admin_Servidor (ya tenía el acceso de código; ahora también es visible) y `reportes` al menú de Operador.
- Se removió el acceso de código de Admin_Servidor a `telemetria/index.php` — ese módulo queda exclusivo de Admin_Telemetria, ya que monitoreo en tiempo real es su dominio específico, no del admin general.

`MENU_POR_ROL` es ahora la fuente única de verdad: lo que un rol ve en el menú es exactamente lo que su `verificar_rol()` permite.

## 5. Ciclo de vida de un turno (ejemplo de interacción entre roles)

1. **Operador** inicia un turno asignando un `Conductor` y un vehículo (`turnos/guardar.php`) — valida que ni el conductor ni el vehículo tengan ya un turno activo.
2. **Conductor** ve su turno activo, registra descansos (`turnos/descansos.php`) y puede pausar/finalizar el turno (`turnos/cambiar_estado.php`).
3. Durante el turno, el celular del **Conductor** envía su posición GPS automáticamente (mientras el turno esté `Activo`) generando registros en `telemetria`, y potencialmente `alertas` (p. ej. exceso de velocidad) ligadas a ese turno.
4. **Admin_Telemetria** revisa y resuelve las alertas generadas.
5. Si ocurre un evento durante el turno, el **Conductor** (o el Operador) reporta una **incidencia**; solo **Operador** o **Admin_Servidor** pueden cambiar su estado de atención (Pendiente → En_Revision → Resuelta/Cerrada).
6. **Admin_BD** o **Admin_Servidor** gestionan el mantenimiento del vehículo si su kilometraje o estado lo requiere, generando tickets de atención independientes del turno.
