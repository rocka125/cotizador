<?php
/**
 * ver_cotizacion.php — Entry point para ver una cotización.
 *
 * Solo orquesta: carga Auth, DB, Model, Controller y View.
 * Toda la lógica vive en controllers/VerCotizacionController.php
 * Todas las queries viven en   models/VerCotizacionModel.php
 * Todo el HTML vive en         views/ver_cotizacion.view.php
 */

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/models/VerCotizacionModel.php';
require_once __DIR__ . '/controllers/VerCotizacionController.php';

$auth  = Auth::init();
$model = new VerCotizacionModel($conexion, $auth->usuarioId(), $auth->esAdmin());
$ctrl  = new VerCotizacionController($model, $auth);
$ctrl->handle();

require __DIR__ . '/views/ver_cotizacion.view.php';
