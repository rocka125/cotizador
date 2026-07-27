<?php
/**
 * auditoria.php — Entry point del log de auditoría.
 *
 * Solo orquesta: carga Auth, DB, Model, Controller y View.
 * Toda la lógica vive en controllers/AuditoriaController.php
 * Todas las queries viven en   models/AuditoriaModel.php
 * Todo el HTML vive en         views/auditoria.view.php
 *
 * Acceso restringido a administradores — el controller redirige si no.
 */

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/models/AuditoriaModel.php';
require_once __DIR__ . '/controllers/AuditoriaController.php';

$auth  = Auth::init();
$model = new AuditoriaModel($conexion);
$ctrl  = new AuditoriaController($model, $auth);
$ctrl->handle();

require __DIR__ . '/views/auditoria.view.php';
