<?php
/**
 * lista_cotizaciones.php — Entry point del listado de cotizaciones.
 *
 * Solo orquesta: carga Auth, DB, Model, Controller y View.
 * Toda la lógica vive en controllers/ListaCotizacionesController.php
 * Todas las queries viven en   models/ListaCotizacionesModel.php
 * Todo el HTML vive en         views/lista_cotizaciones.view.php
 */

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/models/ListaCotizacionesModel.php';
require_once __DIR__ . '/controllers/ListaCotizacionesController.php';

$auth  = Auth::init();
$model = new ListaCotizacionesModel($conexion, $auth->usuarioId(), $auth->esAdmin());
$ctrl  = new ListaCotizacionesController($model, $auth);
$ctrl->handle();

require __DIR__ . '/views/lista_cotizaciones.view.php';
