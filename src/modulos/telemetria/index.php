<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

verificar_rol([ROL_ADMIN_TELEMETRIA]);

// Obtener los últimos 100 registros de telemetría
$stmt = $pdo->query("
    SELECT t.*, v.placa, c.nombre as conductor
    FROM telemetria t
    JOIN vehiculos v ON t.id_camion = v.id_camion
    LEFT JOIN turnos tu ON t.id_turno = tu.id_turno
    LEFT JOIN conductores c ON tu.dni_conductor = c.dni
    ORDER BY t.fecha_registro DESC
    LIMIT 100
");
$registros = $stmt->fetchAll();

// Extraer la última ubicación conocida por camión para el mapa y los chips de estado
$ubicaciones_recientes = [];
$vistos = [];
foreach ($registros as $r) {
    if (!isset($vistos[$r['id_camion']])) {
        $ubicaciones_recientes[] = [
            'id_camion' => $r['id_camion'],
            'placa' => $r['placa'],
            'latitud' => (float)$r['latitud'],
            'longitud' => (float)$r['longitud'],
            'velocidad_kmh' => (float)$r['velocidad_kmh']
        ];
        $vistos[$r['id_camion']] = true;
    }
}

// Datos falsos por si no hay registros reales, solo para demostrar el mapa
if (empty($ubicaciones_recientes)) {
    $ubicaciones_recientes = [
        ['id_camion' => 'CAM-001 (Simulado)', 'placa' => '---', 'latitud' => -17.2185, 'longitud' => -70.9254, 'velocidad_kmh' => 45],
        ['id_camion' => 'CAM-002 (Simulado)', 'placa' => '---', 'latitud' => -17.2210, 'longitud' => -70.9310, 'velocidad_kmh' => 32],
        ['id_camion' => 'CAM-003 (Simulado)', 'placa' => '---', 'latitud' => -17.2150, 'longitud' => -70.9200, 'velocidad_kmh' => 0]
    ];
}

$titulo_pagina = 'Telemetría en Vivo';
require_once __DIR__ . '/../../includes/header.php';
?>

<!--
    Librerías de Leaflet para el Mapa, cargadas desde un CDN externo (unpkg.com y
    tile.openstreetmap.org). Si el servidor de producción corre en una red interna
    sin salida a internet, este mapa no cargará ahí — es una limitación preexistente
    de cómo está construido el módulo, no introducida por este rediseño.
-->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
    <h1 class="titulo-modulo" style="margin-bottom: 0;">Telemetría en Vivo</h1>
    <button class="boton boton-secundario" onclick="window.location.reload();">Actualizar Datos</button>
</div>

<!-- Chips de estado por vehículo: contexto de un vistazo antes del mapa -->
<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px;">
    <?php foreach ($ubicaciones_recientes as $u): ?>
        <?php $clase_chip = $u['velocidad_kmh'] > 40 ? 'badge-rojo' : 'badge-verde'; ?>
        <span class="badge <?= $clase_chip ?>" style="font-size:13px; padding:6px 12px;">
            <?= e($u['id_camion']) ?><?= $u['placa'] && $u['placa'] !== '---' ? ' · ' . e($u['placa']) : '' ?> · <?= number_format($u['velocidad_kmh'], 0) ?> km/h
        </span>
    <?php endforeach; ?>
</div>

<!-- El mapa es el elemento protagonista de este módulo -->
<div id="mapa-telemetria" style="height: 520px; width: 100%; border-radius: 10px; margin-bottom: 8px; border: 1px solid #ccc; box-shadow: 0 1px 4px rgba(0,0,0,.06);"></div>
<p style="font-size: 12px; color: #778; margin-bottom: 20px;">
    Las ubicaciones GPS y la velocidad se envían automáticamente desde el teléfono del conductor mientras su turno está activo. <?= empty($registros) ? 'No hay datos reales aún — se muestran ubicaciones simuladas.' : '' ?>
</p>

<div class="panel">
    <div class="panel-header">
        <h2>Historial de telemetría</h2>
        <span class="contador"><?= count($registros) ?> registros</span>
    </div>
    <div class="panel-cuerpo">
    <table class="tabla">
    <thead>
        <tr>
            <th>Fecha/Hora</th>
            <th>Camión</th>
            <th>Conductor (Turno)</th>
            <th>Coordenadas GPS</th>
            <th>Velocidad</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($registros)): ?>
            <tr><td colspan="5">No hay datos de telemetría disponibles en la base de datos (Mostrando ubicaciones simuladas en el mapa).</td></tr>
        <?php else: ?>
            <?php foreach ($registros as $r): ?>
                <tr>
                    <td data-label="Fecha/Hora"><?= e($r['fecha_registro']) ?></td>
                    <td data-label="Camión">
                        <strong><?= e($r['id_camion']) ?></strong><br>
                        <small style="color:#667;"><?= e($r['placa']) ?></small>
                    </td>
                    <td data-label="Conductor">
                        <?= e($r['conductor'] ?: 'Desconocido') ?><br>
                        <small style="color:#667;">Turno #<?= e($r['id_turno']) ?></small>
                    </td>
                    <td data-label="GPS">
                        <a href="https://maps.google.com/?q=<?= $r['latitud'] ?>,<?= $r['longitud'] ?>" target="_blank" style="color: #2c4a7c; text-decoration: none;">
                            <?= $r['latitud'] ?>, <?= $r['longitud'] ?> 📍
                        </a>
                    </td>
                    <td data-label="Velocidad">
                        <?php
                        $vel = $r['velocidad_kmh'];
                        $color = $vel > 40 ? 'color: #a33; font-weight: bold;' : 'color: #1c7a3d;';
                        ?>
                        <span style="<?= $color ?>"><?= number_format($vel, 1) ?> km/h</span>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    </table>
    </div>
</div>

<script>
    // Inicializar el mapa centrado en el primer camión, o en una ubicación por defecto
    var datosIniciales = <?= json_encode($ubicaciones_recientes) ?>;
    var centroLat = datosIniciales.length > 0 ? datosIniciales[0].latitud : -17.2185;
    var centroLng = datosIniciales.length > 0 ? datosIniciales[0].longitud : -70.9254;

    var map = L.map('mapa-telemetria').setView([centroLat, centroLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    var marcadores = {};

    function actualizarMapa(ubicaciones) {
        var limites = L.latLngBounds();

        ubicaciones.forEach(function(vehiculo) {
            var latLng = [vehiculo.latitud, vehiculo.longitud];
            var contenidoPopup = `<b>${vehiculo.id_camion}</b><br>Velocidad: ${vehiculo.velocidad_kmh} km/h`;

            if (marcadores[vehiculo.id_camion]) {
                marcadores[vehiculo.id_camion].setLatLng(latLng);
                marcadores[vehiculo.id_camion].setPopupContent(contenidoPopup);
            } else {
                marcadores[vehiculo.id_camion] = L.marker(latLng)
                    .addTo(map)
                    .bindPopup(contenidoPopup);
            }
            limites.extend(latLng);
        });

        // Ajustar el zoom para que se vean todos los camiones
        if (ubicaciones.length > 0) {
            map.fitBounds(limites, { padding: [30, 30] });
        }
    }

    // Cargar los marcadores iniciales
    actualizarMapa(datosIniciales);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
