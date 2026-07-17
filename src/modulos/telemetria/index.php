<?php
require_once __DIR__ . '/../../auth/sesion.php';
require_once __DIR__ . '/../../config/conexion.php';

// Según constantes.php, la telemetría la ve principalmente Admin_Telemetria (y podríamos permitir a Admin_Servidor)
verificar_rol([ROL_ADMIN_TELEMETRIA, ROL_ADMIN_SERVIDOR]);

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

// Extraer la última ubicación conocida por camión para el mapa
$ubicaciones_recientes = [];
$vistos = [];
foreach ($registros as $r) {
    if (!isset($vistos[$r['id_camion']])) {
        $ubicaciones_recientes[] = [
            'id_camion' => $r['id_camion'],
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
        ['id_camion' => 'CAM-001 (Simulado)', 'latitud' => -17.2185, 'longitud' => -70.9254, 'velocidad_kmh' => 45],
        ['id_camion' => 'CAM-002 (Simulado)', 'latitud' => -17.2210, 'longitud' => -70.9310, 'velocidad_kmh' => 32],
        ['id_camion' => 'CAM-003 (Simulado)', 'latitud' => -17.2150, 'longitud' => -70.9200, 'velocidad_kmh' => 0]
    ];
}

$titulo_pagina = 'Telemetría en Vivo';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Librerías de Leaflet para el Mapa -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
    <h1 class="titulo-modulo" style="margin-bottom: 0;">Historial y Mapa de Telemetría</h1>
    <button class="boton boton-secundario" onclick="window.location.reload();">Actualizar Datos</button>
</div>

<div class="tarjeta" style="margin-bottom: 20px; background: #eef0f5; padding: 15px;">
    <p style="font-size: 13px; color: #556; margin: 0;">
        <strong>Nota:</strong> Los datos de telemetría (GPS y velocidad) son insertados automáticamente por el hardware de los camiones. Aquí se muestran las últimas ubicaciones y los últimos 100 registros.
    </p>
</div>

<!-- Contenedor del Mapa -->
<div id="mapa-telemetria" style="height: 400px; width: 100%; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ccc;"></div>

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
                    <td><?= e($r['fecha_registro']) ?></td>
                    <td>
                        <strong><?= e($r['id_camion']) ?></strong><br>
                        <small style="color:#667;"><?= e($r['placa']) ?></small>
                    </td>
                    <td>
                        <?= e($r['conductor'] ?: 'Desconocido') ?><br>
                        <small style="color:#667;">Turno #<?= e($r['id_turno']) ?></small>
                    </td>
                    <td>
                        <a href="https://maps.google.com/?q=<?= $r['latitud'] ?>,<?= $r['longitud'] ?>" target="_blank" style="color: #2c4a7c; text-decoration: none;">
                            <?= $r['latitud'] ?>, <?= $r['longitud'] ?> 📍
                        </a>
                    </td>
                    <td>
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
