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

$auth  = Auth::init();
$model = new CambiarEstadoModel($conexion);
$ctrl  = new CambiarEstadoController($model, $auth);
$ctrl->handle();