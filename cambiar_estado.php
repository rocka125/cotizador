<?php
/**
 * cambiar_estado.php — Entry point del endpoint de cambio de estado.
 *
 * Solo admin. Recibe POST y responde JSON.
 * No tiene vista HTML — es un endpoint puro de API.
 *
 * Toda la lógica vive en controllers/CambiarEstadoController.php
 * Todas las queries viven en   models/CambiarEstadoModel.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/auditoria_helper.php';
require_once __DIR__ . '/models/CambiarEstadoModel.php';
require_once __DIR__ . '/controllers/CambiarEstadoController.php';

// Sin redirect: es un endpoint JSON, no debe responder con un 302 a login.php
// cuando la sesión expiró (el fetch() del frontend espera JSON, no HTML).
$auth  = Auth::init(redirectIfUnauthenticated: false);
$model = new CambiarEstadoModel($conexion);
$ctrl  = new CambiarEstadoController($model, $auth);
$ctrl->handle();