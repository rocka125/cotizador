<?php
/**
 * notificaciones.php — Entry point del centro de notificaciones.
 *
 * Recibe GET (listar) o POST (marcar leída/todas) y responde JSON.
 * No tiene vista HTML — es un endpoint puro de API.
 *
 * Toda la lógica vive en controllers/NotificacionesController.php
 * Todas las queries viven en   models/NotificacionesModel.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/models/NotificacionesModel.php';
require_once __DIR__ . '/controllers/NotificacionesController.php';

$auth  = Auth::init();
$model = new NotificacionesModel($conexion, $auth->usuarioId());
$ctrl  = new NotificacionesController($model);
$ctrl->handle();
