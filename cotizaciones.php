<?php
/**
 * cotizaciones.php — Entry point del editor de cotizaciones.
 *
 * Solo orquesta: carga Auth, DB, Model, Controller y View.
 * Toda la lógica vive en controllers/CotizacionController.php
 * Todas las queries viven en   models/CotizacionModel.php
 * Todo el HTML vive en         views/cotizacion.view.php
 */

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/models/CotizacionModel.php';
require_once __DIR__ . '/controllers/CotizacionController.php';

$auth  = Auth::init();
$model = new CotizacionModel($conexion, $auth->usuarioId(), $auth->esAdmin());
$ctrl  = new CotizacionController($model, $auth);
$ctrl->handle();

require __DIR__ . '/views/cotizacion.view.php';
