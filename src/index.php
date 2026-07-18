<?php
session_start();
// Si ya hay sesión activa, ir directo a su módulo
if (isset($_SESSION['nombre_usuario'])) {
    require_once __DIR__ . '/config/constantes.php';
    $modulos = MENU_POR_ROL[$_SESSION['rol']] ?? ['perfil'];
    header("Location: modulos/" . $modulos[0] . "/index.php");
    exit();
}

$mensajes = [
    'credenciales' => 'Usuario o contraseña incorrectos.',
    'vacio'        => 'Complete todos los campos.',
];
$error    = $mensajes[$_GET['error'] ?? ''] ?? '';
$expirado = isset($_GET['expirado']) ? 'Su sesión expiró por inactividad.' : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FleetCore — Iniciar Sesión</title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="fondo-shapes">
        <div class="particula" style="--x:15%;--y:20%;--d:12s;--s:2px"></div>
        <div class="particula" style="--x:80%;--y:15%;--d:16s;--s:3px"></div>
        <div class="particula" style="--x:45%;--y:70%;--d:14s;--s:2px"></div>
        <div class="particula" style="--x:70%;--y:55%;--d:19s;--s:4px"></div>
        <div class="particula" style="--x:25%;--y:85%;--d:11s;--s:2px"></div>
        <div class="particula" style="--x:90%;--y:40%;--d:17s;--s:3px"></div>
        <div class="particula" style="--x:10%;--y:60%;--d:13s;--s:2px"></div>
        <div class="particula" style="--x:55%;--y:10%;--d:20s;--s:3px"></div>
        <div class="particula" style="--x:35%;--y:45%;--d:15s;--s:2px"></div>
        <div class="particula" style="--x:65%;--y:80%;--d:18s;--s:4px"></div>
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
        <div class="rejilla"></div>
    </div>

    <div class="login-contenedor">
        <div class="login-caja">
            <div class="login-accent"></div>

            <div class="logo-icono">
                <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="8" y="18" width="32" height="24" rx="3" stroke="#2c4a7c" stroke-width="2.2" fill="none"/>
                    <rect x="11" y="21" width="9" height="6" rx="1.2" fill="#2c4a7c" opacity=".12"/>
                    <rect x="28" y="21" width="9" height="6" rx="1.2" fill="#2c4a7c" opacity=".12"/>
                    <line x1="17" y1="8" x2="17" y2="18" stroke="#2c4a7c" stroke-width="2.2" stroke-linecap="round"/>
                    <line x1="31" y1="8" x2="31" y2="18" stroke="#2c4a7c" stroke-width="2.2" stroke-linecap="round"/>
                    <line x1="10" y1="18" x2="17" y2="15" stroke="#2c4a7c" stroke-width="1.2" stroke-linecap="round"/>
                    <line x1="38" y1="18" x2="31" y2="15" stroke="#2c4a7c" stroke-width="1.2" stroke-linecap="round"/>
                </svg>
            </div>
            <h1>FleetCore</h1>
            <p class="subtitulo">Sistema de Gestión de Flota — SPCC</p>

            <?php if ($error): ?>
                <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($expirado): ?>
                <div class="alerta alerta-info"><?= htmlspecialchars($expirado) ?></div>
            <?php endif; ?>

            <form action="auth/autenticar.php" method="POST" autocomplete="off" id="form-login">
                <div class="campo">
                    <label for="nombre_usuario">Usuario</label>
                    <div class="campo-input">
                        <span class="campo-icono-bg">
                            <svg class="campo-icono" viewBox="0 0 22 22" fill="none">
                                <path d="M11 10a3.5 3.5 0 100-7 3.5 3.5 0 000 7z" fill="#5b8dee" opacity=".2"/>
                                <path d="M11 10a3.5 3.5 0 100-7 3.5 3.5 0 000 7z" stroke="#2c4a7c" stroke-width="1.6" stroke-linecap="round"/>
                                <path d="M4 19c0-4 3.5-6.5 7-6.5s7 2.5 7 6.5" stroke="#2c4a7c" stroke-width="1.6" stroke-linecap="round"/>
                                <circle cx="11" cy="15.5" r=".8" fill="#2c4a7c"/>
                            </svg>
                        </span>
                        <input type="text" id="nombre_usuario" name="nombre_usuario" placeholder="ej: operador.telemetria" required autofocus>
                    </div>
                </div>

                <div class="campo">
                    <label for="contrasena">Contraseña</label>
                    <div class="campo-input">
                        <span class="campo-icono-bg">
                            <svg class="campo-icono" viewBox="0 0 22 22" fill="none">
                                <rect x="4" y="10" width="14" height="9" rx="2" fill="#5b8dee" opacity=".12"/>
                                <rect x="4" y="10" width="14" height="9" rx="2" stroke="#2c4a7c" stroke-width="1.6"/>
                                <path d="M8 10V7a3 3 0 016 0v3" stroke="#2c4a7c" stroke-width="1.6" stroke-linecap="round"/>
                                <circle cx="11" cy="14.5" r="1.2" fill="#2c4a7c"/>
                            </svg>
                        </span>
                        <input type="password" id="contrasena" name="contrasena" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" id="btn-ingresar">
                    <span class="btn-texto">Ingresar</span>
                    <span class="btn-spinner"></span>
                    <svg class="btn-icono" viewBox="0 0 20 20" fill="none"><path d="M3 10h14M13 6l4 4-4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span class="btn-onda"></span>
                </button>
            </form>

            <p class="pie">Red interna SPCC — Toquepala</p>
        </div>
    </div>

    <script>
    (function() {
        var form = document.getElementById('form-login');
        var btn  = document.getElementById('btn-ingresar');
        var caja = document.querySelector('.login-caja');
        var inputs = form.querySelectorAll('input');

        form.addEventListener('submit', function() { btn.classList.add('cargando'); });

        inputs.forEach(function(inp) {
            inp.addEventListener('focus', function() { caja.classList.add('enfocado'); });
            inp.addEventListener('blur', function() {
                var alguno = false;
                inputs.forEach(function(i) { if (i === document.activeElement) alguno = true; });
                if (!alguno) caja.classList.remove('enfocado');
            });
            inp.addEventListener('input', function() {
                var lleno = true;
                inputs.forEach(function(i) { if (i.value.trim() === '') lleno = false; });
                btn.classList.toggle('listo', lleno);
            });
        });
    })();
    </script>
</body>
</html>
