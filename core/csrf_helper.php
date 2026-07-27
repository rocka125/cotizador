<?php

/**
 * csrf_helper.php — Token CSRF por sesión.
 *
 * Protege los endpoints que mutan datos usando solo la cookie de sesión
 * (fetch con credentials, sin preflight) contra peticiones cross-site:
 * sin este token, un sitio externo puede disparar POSTs válidos aprovechando
 * la sesión ya autenticada de la víctima en el navegador.
 */

if (!function_exists('csrf_token')) {
    /** Devuelve el token de la sesión actual, generándolo la primera vez. */
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_validar')) {
    /** Compara el token recibido contra el de la sesión (comparación segura). */
    function csrf_validar(?string $token): bool
    {
        return !empty($_SESSION['csrf_token'])
            && !empty($token)
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}
