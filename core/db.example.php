<?php
/**
 * db.example.php — Plantilla de configuración de base de datos.
 *
 * Copia este archivo a core/db.php y ajusta los valores a tu entorno.
 * core/db.php está en .gitignore: nunca subas ahí tus credenciales reales.
 */

// ── Configuración ─────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'cotizador');
define('DB_PORT',    3306);
define('DB_CHARSET', 'utf8mb4');

// ── Conexión ──────────────────────────────────────────────────────────────
$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conexion->connect_error) {
    // En producción: loguear el error real, nunca mostrarlo al usuario
    error_log('[DB] Fallo de conexión: ' . $conexion->connect_error);
    http_response_code(503);
    die(json_encode(['error' => 'Servicio no disponible. Intenta más tarde.']));
}

// utf8mb4 soporta emojis y caracteres especiales (mejor que utf8)
if (!$conexion->set_charset(DB_CHARSET)) {
    error_log('[DB] Error al definir charset: ' . $conexion->error);
}

// Zona horaria consistente entre PHP y MySQL
$conexion->query("SET time_zone = '-06:00'"); // Ajusta a tu zona horaria

// Modo estricto: evita datos silenciosamente truncados o inválidos
$conexion->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

require_once __DIR__ . '/estado_helper.php';
