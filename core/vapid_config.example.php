<?php
/**
 * vapid_config.example.php — Claves VAPID para Web Push Notifications
 *
 * Copia este archivo a core/vapid_config.php y genera tus propias claves.
 * core/vapid_config.php está en .gitignore: nunca subas ahí tus claves reales.
 *
 * Genera un par de claves con: https://web-push-codelab.glitch.me/
 * o con la librería web-push-libs correspondiente.
 */

// ── Tu email de contacto para el servidor de push ─────────────────────────
define('VAPID_SUBJECT', 'mailto:admin@tudominio.com');

// ── Claves VAPID (generadas con OpenSSL / web-push-libs) ──────────────────
define('VAPID_PUBLIC_KEY',  'TU_CLAVE_PUBLICA_AQUI');
define('VAPID_PRIVATE_KEY', 'TU_CLAVE_PRIVADA_AQUI');
