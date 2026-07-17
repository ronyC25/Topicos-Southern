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
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-contenedor">
        <div class="login-caja">
            <h1>FleetCore</h1>
            <p class="subtitulo">Sistema de Gestión de Flota — SPCC</p>

            <?php if ($error): ?>
                <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($expirado): ?>
                <div class="alerta alerta-info"><?= htmlspecialchars($expirado) ?></div>
            <?php endif; ?>

            <form action="auth/autenticar.php" method="POST" autocomplete="off">
                <label for="nombre_usuario">Usuario</label>
                <input type="text" id="nombre_usuario" name="nombre_usuario"
                       placeholder="ej: operador.telemetria" required autofocus>

                <label for="contrasena">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena" required>

                <button type="submit">Ingresar</button>
            </form>

            <p class="pie">Red interna SPCC — Toquepala</p>
        </div>
    </div>
</body>
</html>
