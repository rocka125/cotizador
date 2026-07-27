<?php
/**
 * dashboard.php — Entry point del dashboard.
 *
 * Solo orquesta: carga Auth, DB, Model, Controller y View.
 * Toda la lógica vive en controllers/DashboardController.php
 * Todas las queries viven en models/DashboardModel.php
 * Todo el HTML vive en views/dashboard.view.php
 */

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/models/DashboardModel.php';
require_once __DIR__ . '/controllers/DashboardController.php';

$auth  = Auth::init();
$model = new DashboardModel($conexion, $auth->usuarioId(), $auth->esAdmin());
$ctrl  = new DashboardController($model, $auth);
$ctrl->handle();

require __DIR__ . '/views/dashboard.view.php';
