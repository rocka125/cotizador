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
require_once __DIR__ . '/core/auditoria_helper.php';
require_once __DIR__ . '/core/firma_helper.php';
require_once __DIR__ . '/models/GuardarCotizacionModel.php';
require_once __DIR__ . '/controllers/GuardarCotizacionController.php';

$auth  = Auth::init();
$model = new GuardarCotizacionModel($conexion);
$ctrl  = new GuardarCotizacionController($model, $auth);
$ctrl->handle();
