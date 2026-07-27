<?php
/**
 * email_config.example.php — Configuración central del sistema de correo.
 *
 * INSTRUCCIONES:
 *   1. Copia este archivo a core/email_config.php
 *   2. Edita SOLO los valores de esta sección con tus datos SMTP.
 *   core/email_config.php está en .gitignore: nunca subas ahí tus credenciales reales.
 *
 * Proveedores comunes:
 *   Gmail          → smtp.gmail.com   : 587 (TLS) — necesitas "contraseña de aplicación"
 *   Outlook/Office → smtp.office365.com: 587 (TLS)
 *   Hostinger      → smtp.hostinger.com: 465 (SSL) o 587 (TLS)
 *   cPanel genérico→ mail.tudominio.com: 465 (SSL) o 587 (TLS)
 */

// ── Remitente ─────────────────────────────────────────────────────────────
define('MAIL_FROM_ADDRESS', 'tu-correo@tudominio.com');  // tu correo de envío
define('MAIL_FROM_NAME',    'Tu Empresa Cotizaciones');  // nombre que verá el cliente

// ── Servidor SMTP ─────────────────────────────────────────────────────────
define('MAIL_SMTP_HOST',   'smtp.tudominio.com'); // servidor de tu proveedor
define('MAIL_SMTP_PORT',    465);                 // 465 = SSL  |  587 = TLS/STARTTLS
define('MAIL_SMTP_SECURE', 'ssl');                // 'ssl' o 'tls'
define('MAIL_SMTP_USER',   'tu-correo@tudominio.com'); // usuario SMTP
define('MAIL_SMTP_PASS',   'tu-contraseña-o-app-password'); // contraseña SMTP

// ── Opciones generales ────────────────────────────────────────────────────
define('MAIL_TIMEOUT', 15);    // segundos de espera al conectar
define('MAIL_DEBUG',   false); // true = muestra log en pantalla (solo desarrollo)

// ── URL base de la aplicación (para el pixel de rastreo de correos) ───────
define('APP_URL', 'https://tu-dominio-o-ngrok.example.com/cotizador');
