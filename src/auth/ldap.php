<?php
/**
 * FleetCore — Validación de credenciales contra Active Directory (LDAP)
 *
 * Solo funciona en la ETAPA FINAL, con la app desplegada en el servidor
 * real (SRV-DISPATCH-01) o en una máquina dentro de la red de la mina.
 * En desarrollo local se usa el modo desarrollo (ver autenticar.php).
 *
 * Requiere la extensión ldap habilitada:
 *   - Docker: ya viene en la imagen (Dockerfile)
 *   - XAMPP:  descomentar "extension=ldap" en C:\xampp\php\php.ini
 */

require_once __DIR__ . '/../config/constantes.php';

/**
 * Valida usuario y contraseña contra el dominio spcc.local.
 * Devuelve true si las credenciales son correctas.
 */
function validar_ldap(string $nombre_usuario, string $contrasena): bool {
    // LDAP rechaza bind con contraseña vacía como "anónimo exitoso" — bloquearlo
    if ($contrasena === '') {
        return false;
    }

    $conexion = ldap_connect(LDAP_SERVIDOR);
    if (!$conexion) {
        error_log("FleetCore LDAP: no se pudo conectar a " . LDAP_SERVIDOR);
        return false;
    }

    ldap_set_option($conexion, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($conexion, LDAP_OPT_REFERRALS, 0);
    ldap_set_option($conexion, LDAP_OPT_NETWORK_TIMEOUT, 5);

    $ldap_dn = $nombre_usuario . '@' . LDAP_DOMINIO;   // ej: admin.bd@spcc.local

    $validado = @ldap_bind($conexion, $ldap_dn, $contrasena);
    ldap_unbind($conexion);

    return $validado === true;
}
