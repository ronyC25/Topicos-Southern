# DESIGN.md — Sistema visual de FleetCore

**Leer este archivo antes de tocar la interfaz de cualquier módulo.** Documenta lo que YA existe en `src/assets/css/estilos.css` (y `login.css` para la pantalla de login). El objetivo es que los 11 módulos se vean como un solo sistema construido por el mismo equipo — no es una guía para rediseñar, es un inventario de lo que ya está construido para que se reutilice tal cual.

**Regla de oro: si un componente que necesitas ya existe (tarjeta, badge, tabla, modal, botón, formulario), úsalo con sus clases exactas. No inventes una clase nueva, un color nuevo, ni un espaciado nuevo, a menos que el usuario lo pida explícitamente.** Todo el CSS del sistema vive en dos archivos — no hay Sass, ni variables CSS, ni build step — así que cualquier valor nuevo debe copiarse literal del código existente.

## Paleta de colores (minera azul)

Todos los valores están tomados literal de `estilos.css`/`login.css`. No existen custom properties (`--variable`) — cada archivo repite los hex directamente.

| Uso | Hex | Dónde se usa |
|---|---|---|
| Azul oscuro (marca / texto de títulos) | `#1a2a45` | `.barra-superior` (fondo), `h1.titulo-modulo`, `.menu-lateral .activo a`, hover de `.boton` |
| Azul medio (acento / interactivo) | `#2c4a7c` | `.rol-etiqueta`, `.tarjeta .valor`, `.boton`, borde de `.menu-lateral .activo`, focus de inputs |
| Azul claro (detalle sobre fondo oscuro) | `#7ab0ff` | texto secundario dentro de `.marca` (login/topbar) |
| Fondo de página | `#f2f4f8` | `body` |
| Fondo de tarjetas/tablas/modal | `#fff` | `.tarjeta`, `table.tabla`, `.modal`, `.menu-lateral` |
| Texto base | `#223` | `body` |
| Texto de labels/menú | `#445` | `.menu-lateral a`, `.grupo-form label` |
| Texto muted (encabezados de tabla, etiquetas) | `#667`, `#778`, `#99a` | `.tabla th`, `.tarjeta .etiqueta`, `.pie-pagina`, `.boton-secundario` |
| Bordes suaves | `#e0e4ec`, `#eef0f5`, `#ccc`/`#ccd` | `.menu-lateral`, `.tabla td`, inputs |

### Colores de estado (badges y alertas) — el único set de semántica de color del sistema

| Estado | Fondo | Texto |
|---|---|---|
| Verde (éxito / operativo / resuelto) | `#e2f5e9` | `#1c7a3d` |
| Amarillo (advertencia / en proceso / media) | `#fdf3dc` | `#96700a` |
| Rojo (error / crítico / fuera de servicio) | `#fdeaea` | `#a33` |
| Gris (neutro / desconocido / baja) | `#eef0f5` | `#667` |

Estos 4 pares son los únicos colores de estado en todo el sistema. Cualquier badge o mensaje nuevo debe mapear a uno de estos cuatro — no inventar un quinto color de estado.

### Paleta ampliada (rediseño 2026-07 — animaciones, gradientes, topbar oscuro)

Un segundo pase de rediseño (commit `1db802d`) agregó colores nuevos, todos decorativos/de soporte — no son un segundo set de estado, no reemplazan la paleta original:

| Uso | Hex | Dónde se usa |
|---|---|---|
| Gradiente de fondo del topbar (extremo oscuro) | `#0f1a2e` | `.barra-superior` — gradiente `135deg, #0f1a2e, #1a2a45` (antes era el sólido `#1a2a45`) |
| Azul de acento (hover/gradientes decorativos) | `#5b8dee` | brillo de `.marca-glow`, gradiente de `.logo-icono`/campos del login, hover de `.dash-link` |
| Fondo de página del login (gradiente sutil) | `#e8ecf2`, `#dce1ea`, `#d4dae6` | fondo `body` de `index.php` (login) |
| Grises de superficie/hover (paneles, scrollbar, bordes) | `#f5f7fb`, `#f8faff`, `#f8f9fc`, `#fafbfc`, `#fafbfd`, `#e6eaf0`, `#e6eefb`, `#f0f4ff`, `#d0d4e0`, `#b0b8c4`, `#c8ceda` | hover de `.panel`/`.dash-fila`, `.panel-cuerpo::-webkit-scrollbar-thumb`, bordes de `.menu-lateral` |
| Textos secundarios nuevos | `#889`, `#aab`, `#b8c0cc`, `#556`, `#333` | subtítulos (`.dash-sub`), variantes de `.tabla`/`.pie-pagina` |

Si vas a agregar más color decorativo en esta línea (gradientes, glows, hovers), reusa uno de los de arriba antes de inventar uno nuevo. Los 4 pares de estado (verde/amarillo/rojo/gris) y la paleta base de marca (`#1a2a45`/`#2c4a7c`/`#7ab0ff`) siguen siendo intocables — este bloque es solo para efectos decorativos añadidos encima.

## Tipografía

Una sola familia en todo el sistema: `'Segoe UI', Arial, sans-serif` (declarada en `body`). No hay una escala tipográfica formal; los tamaños usados en la práctica son:
- Título de módulo (`h1.titulo-modulo`): `22px`, color `#1a2a45`
- Subtítulos sueltos dentro de un módulo (ej. "Últimas alertas activas" en dashboard): `16px`, inline (`style="font-size:16px; margin-bottom:12px;"`) — no hay una clase `h2` dedicada todavía, se repite el inline style donde aparece.
- Valor grande de tarjeta (`.tarjeta .valor`): `30px`, `700`
- Cuerpo de tabla (`.tabla td`): `14px`
- Encabezado de tabla (`.tabla th`): `12px`, mayúsculas, `#667`
- Texto pequeño / metadatos (`.etiqueta`, `.pie-pagina`): `11px`–`13px`

## Estructura de página (el "shell") — nunca reconstruir a mano

Todo módulo autenticado sigue el mismo esqueleto de tres includes. **Nunca escribas `<html>`, `<head>`, la barra superior o el menú lateral a mano dentro de un módulo** — siempre:

```php
$titulo_pagina = 'Nombre del Módulo';
require_once __DIR__ . '/../../includes/header.php';
?>
<!-- contenido del módulo aquí -->
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
```

`header.php` ya trae: `<!DOCTYPE>`, el `<link>` a `estilos.css`, `.barra-superior` (marca + usuario + rol + logout) y arranca `.contenedor-principal` + `.menu-lateral` (vía `sidebar.php`, que se autogenera desde `MENU_POR_ROL`) + `<main class="contenido">`. `footer.php` cierra esos divs y agrega `.pie-pagina`. El contenido de tu módulo va **solo** dentro de `<main class="contenido">` — es decir, todo lo que escribas entre header.php y footer.php.

La única excepción son las páginas fuera de sesión (`index.php` = login, con `login.css`) y `acceso_denegado.php` (usa `estilos.css` + la clase `.pagina-centrada` para un mensaje centrado de una sola columna).

### Barra superior: icono + color por rol, logout con icono + texto

`.usuario-info` (dentro de `.barra-superior`) muestra, en orden: nombre completo, `.rol-etiqueta` con ícono + color según el rol, y el logout como ícono **junto con** el texto "Salir" (no ícono solo — se probó y resultaba demasiado ambiguo sin la palabra). El mapeo rol → ícono/clase vive en `src/config/constantes.php` (`ICONOS_ROL`, `CLASES_ROL`, `ICONO_SALIR`, `ICONO_USUARIO_DEFECTO`), igual que `MENU_POR_ROL`/`NOMBRES_MODULOS` — es la fuente única de verdad, no hardcodear el ícono en `header.php`:
```php
$rol_actual = $_SESSION['rol'];
$icono_rol  = ICONOS_ROL[$rol_actual] ?? ICONO_USUARIO_DEFECTO;
$clase_rol  = CLASES_ROL[$rol_actual] ?? '';
```
```html
<span class="rol-etiqueta <?= $clase_rol ?>"><?= $icono_rol ?> <?= e($_SESSION['rol']) ?></span>
<a href="logout.php" class="cerrar-sesion" title="Cerrar sesión"><?= ICONO_SALIR ?> Salir</a>
```
**Los íconos de la barra superior son SVG de línea inline (monocromos, `stroke="currentColor"`, sin relleno)** — no una librería externa (no hay Font Awesome ni sprite descargado; coherente con "sin build step, sin CDN", y la red de la mina es interna/sin internet), pero tampoco emoji: se reemplazaron a propósito por líneas simples porque el emoji se ve informal e inconsistente entre Windows/Mac/navegadores para una herramienta de sala de control. Como `currentColor` hereda el color del texto que lo rodea, cada ícono de rol adopta automáticamente el color de su `.rol-etiqueta` sin CSS adicional. Para un ícono nuevo, seguir el mismo patrón: `viewBox="0 0 24 24"`, `stroke="currentColor"`, `stroke-width="2"`, trazo simple (rect/circle/path), y agregarlo como constante en `constantes.php` — no usar emoji para chrome de interfaz (nombre de rol, logout, acciones de la topbar). El emoji suelto que ya existía en `acceso_denegado.php` (⛔) queda como está — no se tocó porque no se pidió, pero no es el patrón a seguir para íconos nuevos.

Los colores de `.rol-etiqueta` (`rol-admin-servidor`, `rol-admin-bd`, `rol-admin-telemetria`, `rol-operador`, `rol-conductor`, en `estilos.css`) son un set de identidad **separado** de los 4 colores de estado (verde/amarillo/rojo/gris de los badges) — no reutilizan esos pares a propósito, para que un rol nunca se confunda visualmente con un estado operativo (ej. el amarillo de "Admin_Telemetria" no debe leerse como "Mantenimiento"). Si se agrega un rol nuevo, sumar su entrada en `ICONOS_ROL`/`CLASES_ROL` y un par bg/texto nuevo aquí, evitando los 4 pares reservados a estado.

**Cada clase `.rol-*` tiene dos variantes, según el fondo donde aparece** (agregado en el rediseño 2026-07, tras un bug de contraste): la regla base (`.rol-admin-servidor`, etc.) usa fondo sólido claro + texto oscuro — legible sobre blanco, para cuando la píldora aparece en `usuarios/index.php` o `perfil/index.php` (fondo `#fff`/`#f2f4f8`). Una segunda regla, con más especificidad (`.barra-superior .rol-admin-servidor`, etc.), la sobreescribe con fondo pastel translúcido + texto claro, pensada para el topbar oscuro (`.barra-superior`, fondo `#1a2a45`→`#0f1a2e`). **Si agregas un rol nuevo, define ambas variantes** — solo la de topbar hará que el texto sea ilegible en cualquier otro sitio donde se muestre el rol.

**La píldora de rol con ícono/color no es exclusiva del topbar** — donde sea que se muestre el rol de un usuario (`usuarios/index.php` columna Rol, `perfil/index.php` dato Rol), usa el mismo patrón `ICONOS_ROL`/`CLASES_ROL`, nunca `<span class="rol-etiqueta"><?= e($rol) ?></span>` a secas.

### Sidebar: ícono por módulo
Cada link de `sidebar.php` lleva un ícono antes del texto, igual patrón que los íconos de rol: SVG de línea, `stroke="currentColor"`, mapeado en `ICONOS_MODULO` (`src/config/constantes.php`) por slug de módulo (`'flota' => '<svg ...>'`, etc.). El texto va envuelto en `<span>` (no suelto) para que la vista móvil (ver abajo) pueda mostrar ícono arriba / texto abajo:
```php
<a href="...">
    <?= ICONOS_MODULO[$mod] ?? '' ?>
    <span><?= e(NOMBRES_MODULOS[$mod] ?? ucfirst($mod)) ?></span>
</a>
```
Si se agrega un módulo nuevo, sumar su ícono en `ICONOS_MODULO` siguiendo el mismo estilo de trazo (`viewBox="0 0 24 24"`, `stroke-width="2"`, formas simples).

## Inventario de componentes (con el patrón real de uso)

### Título de módulo
```html
<h1 class="titulo-modulo">Nombre del Módulo</h1>
```

### Tarjetas de estadística (dashboard-style)
```html
<div class="tarjetas">
    <div class="tarjeta">
        <div class="valor"><?= (int)$total ?></div>
        <div class="etiqueta">Descripción corta</div>
    </div>
    <!-- repetir .tarjeta por cada métrica -->
</div>
```
`.tarjetas` es un grid automático (`repeat(auto-fit, minmax(200px, 1fr))`) — agregar más `.tarjeta` no rompe el layout.

### Indicadores de estado grandes (semáforo de sala de control)
Para KPIs donde el estado operativo debe ser la señal dominante (p. ej. el resumen de flota del dashboard), usar `.indicadores-estado` en vez de `.tarjetas` genéricas — mismo concepto que `.tarjeta` pero con número más grande, fondo tintado y borde izquierdo de color, mapeado 1:1 a los mismos 3 colores de los badges (no agrega un color nuevo):
```html
<div class="indicadores-estado">
    <div class="indicador-estado indicador-verde">
        <div class="valor"><?= $conteo ?></div>
        <div class="etiqueta">Operativos</div>
    </div>
    <!-- .indicador-amarillo, .indicador-rojo -->
</div>
```
`.tarjeta-alarma` (modificador de `.tarjeta`) pasa una tarjeta genérica a fondo/texto rojo cuando el valor requiere atención (ej. alertas activas > 0):
```php
<div class="tarjeta<?= $alertas_activas > 0 ? ' tarjeta-alarma' : '' ?>">
```

### Paneles: wrapper estándar de CUALQUIER tabla principal de un módulo
`.panel` ya no es exclusivo del dashboard — es el wrapper estándar para la tabla principal de cualquier módulo de listado (`flota`, `conductores`, `turnos`, `alertas`, `incidencias`, `usuarios`, `reportes`, `mantenimiento`). Un módulo nuevo con una tabla **siempre** va envuelto en `.panel`, no en un `<table class="tabla">` suelto:
```html
<div class="panel">
    <div class="panel-header">
        <h2>Título de la lista</h2>
        <span class="contador"><?= count($items) ?> registros</span>
    </div>
    <div class="panel-cuerpo">
        <table class="tabla">...</table>
    </div>
</div>
```
Si el módulo tiene un filtro (ej. el `<select>` de estado en `alertas/index.php`), va dentro del `panel-header`, junto al contador — no arriba del título como un elemento separado. Si un módulo tiene dos tablas relacionadas pero con columnas distintas (ej. `mantenimiento`: mantenimientos + tickets), van en dos `.panel` apilados verticalmente, no lado a lado — `.paneles-fila` (ver abajo) es solo para tablas angostas que sí caben una junto a otra.

### `.paneles-fila` (varias tablas angostas lado a lado)
Cuando varias tablas caben cómodamente una junto a otra (pocas columnas, ej. dashboard: "Turnos en curso" + "Alertas activas"), usar `.paneles-fila` (grid responsive) con dos o más `.panel` dentro:
```html
<div class="paneles-fila">
    <div class="panel">
        <div class="panel-header">
            <h2>Título del panel</h2>
            <span class="contador">N activos</span>
        </div>
        <div class="panel-cuerpo">
            <table class="tabla">...</table>
        </div>
    </div>
    <!-- otro .panel al lado -->
</div>
```
`.contador` es neutro (gris) por defecto; agregar `contador-alerta` para que se vea como alarma (rojo) cuando el número requiere atención. Un `.panel` también puede usarse solo (ancho completo, fuera de `.paneles-fila`) para una tabla con cabecera propia — ver "Estado detallado de la flota" en `dashboard/index.php`.

### Punto de estado compacto (listas densas)
Para listas largas donde un badge completo sería demasiado ancho (ej. una fila por vehículo), usar `.punto-estado` — un círculo de 9px con el mismo color de texto que el badge correspondiente, antes del identificador:
```php
<?php $clase = ['Operativo'=>'punto-verde','Mantenimiento'=>'punto-amarillo','Fuera_Servicio'=>'punto-rojo'][$valor] ?? 'punto-verde'; ?>
<span class="punto-estado <?= $clase ?>"></span><?= e($valor) ?>
```
Regla de uso: `.badge` para estados en tablas de detalle (una fila = un registro con varios metadatos, como la tabla de alertas); `.punto-estado` para listas de identidad densa (una fila = un ítem de una lista larga, como el detalle de flota).

### Tabla de datos
```html
<table class="tabla">
    <thead>
        <tr><th>Columna</th>...</tr>
    </thead>
    <tbody>
        <?php if (empty($filas)): ?>
            <tr><td colspan="N">Sin registros.</td></tr>
        <?php else: foreach ($filas as $f): ?>
            <tr><td><?= e($f['campo']) ?></td>...</tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>
```
Siempre incluir el `<tr>` de "sin registros" con `colspan` — es el patrón usado en todos los módulos existentes (ver `dashboard/index.php`, `flota/index.php`).

Cuando una fila representa algo que requiere atención inmediata (ej. una alerta de nivel Alta/Critica en el dashboard), agregar la clase `fila-alerta-roja` o `fila-alerta-amarilla` al `<tr>` — tiñe toda la fila con el mismo color del badge correspondiente, no solo la celda del badge:
```php
<?php $clase_fila = ['Media'=>'fila-alerta-amarilla','Alta'=>'fila-alerta-roja','Critica'=>'fila-alerta-roja'][$nivel] ?? ''; ?>
<tr class="<?= $clase_fila ?>">...</tr>
```

### Badges de estado
El patrón siempre es un array de mapeo PHP (valor de BD → clase CSS) resuelto justo antes de imprimir el badge — **no** un `if/elseif` largo:
```php
<?php
$clase = [
    'Operativo'     => 'badge-verde',
    'Mantenimiento' => 'badge-amarillo',
    'Fuera_Servicio'=> 'badge-rojo',
][$valor] ?? 'badge-gris';
?>
<span class="badge <?= $clase ?>"><?= e($valor) ?></span>
```
Usa las constantes de `src/config/constantes.php` como claves del array (no strings sueltos), como hace `flota/index.php`.

### Botones
```html
<button class="boton">Acción principal</button>
<button class="boton boton-secundario">Cancelar</button>
```
Para una acción destructiva (eliminar), el patrón existente (`flota/index.php`) es reusar `.boton` con un `style="background:#a33;"` inline puntual — **no existe una clase `.boton-peligro`**. Sigue ese mismo patrón inline si necesitas un botón rojo; no inventes una clase nueva para esto sin que se pida explícitamente.

### Formularios
```html
<div class="grupo-form">
    <label>Etiqueta*</label>
    <input type="text" name="campo" required maxlength="50">
</div>
```
`.grupo-form` maneja label + spacing + estilo de focus automáticamente para `input`, `select` y `textarea`.

### Modal (crear/editar)
Patrón completo en `flota/index.php` — reutilizar tal cual:
```html
<div class="modal-overlay" id="modalForm">
    <div class="modal">
        <form action="guardar.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="modal-header">
                <h2 id="modalTitulo">Título</h2>
                <button type="button" class="modal-close" onclick="cerrarModal()">&times;</button>
            </div>
            <div class="modal-body">
                <!-- .grupo-form aquí, en grid si son muchos campos:
                     style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;" -->
            </div>
            <div class="modal-footer">
                <button type="button" class="boton boton-secundario" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="boton">Guardar</button>
            </div>
        </form>
    </div>
</div>
```
Abrir/cerrar es JS plano (sin librerías): `document.getElementById('modalForm').style.display = 'flex'` / `'none'`. No introducir un framework JS ni una librería de modales.

### Mensajes de éxito/error (flash messages vía `?msg=`/`?error=`)
Patrón actual (idéntico en todos los módulos, ej. `flota/index.php`):
```php
<?php if (isset($_GET['msg'])): ?>
    <div style="background: #e2f5e9; color: #1c7a3d; padding: 10px; border-radius: 6px; margin-bottom: 15px;">
        <?= e($_GET['msg']) ?>
    </div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div style="background: #fdeaea; color: #a33; padding: 10px; border-radius: 6px; margin-bottom: 15px;">
        <?= e($_GET['error']) ?>
    </div>
<?php endif; ?>
```
**Nota:** estos colores son exactamente los de `.badge-verde`/`.badge-rojo`, pero se repiten inline en cada módulo en vez de usar una clase compartida (no existe `.mensaje-exito`/`.mensaje-error` en `estilos.css`). Es una inconsistencia menor del sistema actual — cópialo tal cual (inline) para mantener consistencia con el resto de módulos; no lo conviertas a una clase nueva salvo que se pida explícitamente.

## Vista móvil (breakpoint `max-width: 640px`)

FleetCore es sobre todo una herramienta de escritorio (sala de control), pero el rol Conductor típicamente entra desde el celular (`turnos`, `incidencias`, `perfil`). Como un media query no puede condicionarse al rol de sesión (eso es PHP, no CSS), el breakpoint móvil en `estilos.css` actúa por **ancho de viewport**, no por rol — cualquier módulo se vuelve responsive en pantallas angostas, pero en la práctica solo lo van a ver los 3 módulos de Conductor.

Todo vive dentro de un único `@media (max-width: 640px)` al final de `estilos.css` — no crear un archivo CSS separado ni un segundo stylesheet para móvil.

- **`.menu-lateral` se convierte en una tab bar fija abajo** (ícono arriba, texto abajo, `<ul>` en fila) en vez del sidebar lateral — patrón estándar de apps móviles. Por esto todo ícono de módulo/rol existe como SVG con `currentColor`: se reusa igual en escritorio y en la tab bar.
- **Las tablas se vuelven tarjetas apiladas** (técnica CSS pura, sin JS): se oculta el `<thead>` y cada `<td>` se muestra como fila `etiqueta: valor` vía `content: attr(data-label)`. Esto **requiere** que cada `<td>` de la tabla tenga `data-label="Nombre de columna"` — ya aplicado en `turnos/index.php` e `incidencias/index.php` (las tablas que Conductor realmente usa). Si otro módulo necesita verse bien en celular, agregar `data-label` a sus `<td>` sigue el mismo patrón; el CSS ya es genérico y no hace falta tocarlo.
- **Grids de 2 columnas con `style` inline no se pueden sobreescribir por CSS** (los media queries no pueden ganarle a un inline style sin `!important`, que no usamos). Por eso `perfil/index.php` usa una clase `.perfil-datos-grid` (definida en `estilos.css`, no inline) en vez de `style="display:grid; grid-template-columns:120px 1fr"` — así el media query sí puede colapsarla a 1 columna. Si un modal/formulario nuevo necesita ser responsive, usar una clase en vez de grid inline.
- **Los modales pasan a pantalla completa** (`.modal{width:100%;height:100%;border-radius:0}`). Esto solo se probó con los modales de una sola columna que usa Conductor (Registrar Descanso, Reportar Incidencia, Cambiar Contraseña); los modales con grid de 2 columnas de otros módulos (flota/usuarios/mantenimiento) no se ajustaron porque Conductor no los usa — si se necesitan responsive, primero convertir su grid inline a clase (punto anterior).

## Módulos con mapa (referencia: `telemetria/index.php`)

Cuando un módulo gira en torno a un mapa (telemetría hoy; cualquier futuro módulo de geolocalización), el mapa es el elemento protagonista, no una tarjeta más:
- El mapa va inmediatamente después del título/acciones, a todo el ancho, alto generoso (520px, no 400px).
- Una fila de chips compactos (`.badge` con velocidad/estado por vehículo) antes del mapa da contexto de un vistazo sin tener que leer la tabla de abajo.
- La tabla de historial/detalle se degrada a un `.panel` con `panel-cuerpo` de altura limitada — sigue disponible pero no compite visualmente con el mapa.
- **`telemetria/index.php` carga Leaflet + tiles de OpenStreetMap desde un CDN externo (`unpkg.com`, `tile.openstreetmap.org`)** — si el servidor de producción no tiene salida a internet (el README dice "red interna SPCC"), el mapa no cargará ahí. Es una limitación preexistente del módulo, documentada en un comentario HTML en el propio archivo; no se resolvió como parte del rediseño visual porque implica una decisión de infraestructura (self-hostear los tiles) fuera de alcance de "mejorar el diseño".

## Checklist antes de tocar la UI de un módulo

1. ¿Ya existe un componente para esto (tarjeta, tabla, badge, modal, botón, formulario)? Si sí, copia el patrón exacto de un módulo existente — no reescribas el HTML/CSS desde cero.
2. ¿Necesitas un color? Usa uno de la paleta de arriba. Si es un color de estado, mapea a verde/amarillo/rojo/gris — no agregues un quinto.
3. ¿El módulo incluye `header.php`/`footer.php` correctamente y todo tu contenido vive dentro de esos includes?
4. ¿Los badges usan el patrón de array de mapeo con las constantes de `constantes.php`, no un `if/elseif`?
5. Si el usuario pide explícitamente un componente nuevo (ej. un `.boton-peligro` real, o una clase compartida para los mensajes flash), es válido agregarlo a `estilos.css` y documentarlo aquí — pero no lo hagas por iniciativa propia mientras el patrón inline/ad-hoc siga siendo consistente con el resto del sistema.
