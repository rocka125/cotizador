<?php
/**
 * guardar_cotizacion.php — Entry point del endpoint de guardado.
 *
 * Recibe JSON por POST y responde JSON.
 * No tiene vista HTML — es un endpoint puro de API.
 *
 * Toda la lógica vive en controllers/GuardarCotizacionController.php
 * Todas las queries viven en   models/GuardarCotizacionModel.php
 */

// ── Capturar cualquier error/excepción y devolverlo como JSON ─────────────
set_exception_handler(function (Throwable $e) {
    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['error' => 'Excepción: ' . $e->getMessage()]);
    exit;
});
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
    // Si el error fue generado bajo el operador "@" (p. ej. @getimagesizefromstring()
    // en core/firma_helper.php para tratar una imagen corrupta como "inválida" sin
    // abortar), error_reporting() devuelve 0 dentro del handler — hay que respetar
    // esa supresión intencional en vez de convertirla en un 500 que tira todo el guardado.
    if (error_reporting() === 0) {
        return false;
    }
    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['error' => "PHP Error [$errno]: $errstr en línea $errline"]);
    exit;
});
ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/csrf_helper.php';
require_once __DIR__ . '/core/auditoria_helper.php';
require_once __DIR__ . '/core/firma_helper.php';
require_once __DIR__ . '/models/GuardarCotizacionModel.php';
require_once __DIR__ . '/controllers/GuardarCotizacionController.php';

// Sin redirect: es un endpoint JSON, no debe responder con un 302 a login.php
// cuando la sesión expiró (el fetch() del frontend espera JSON, no HTML).
$auth  = Auth::init(redirectIfUnauthenticated: false);
$model = new GuardarCotizacionModel($conexion);
$ctrl  = new GuardarCotizacionController($model, $auth);
$ctrl->handle();
