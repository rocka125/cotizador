<?php
/**
 * comparar_listas.php — Entry point del comparador de versiones.
 *
 * Solo orquesta: carga Auth, DB, Model, Controller y View.
 * Toda la lógica vive en controllers/CompararListasController.php
 * Todas las queries viven en   models/CompararListasModel.php
 * Todo el HTML vive en         views/comparar_listas.view.php
 */

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/models/CompararListasModel.php';
require_once __DIR__ . '/controllers/CompararListasController.php';

$auth  = Auth::init();
$model = new CompararListasModel($conexion);
$ctrl  = new CompararListasController($model);
$ctrl->handle();

require __DIR__ . '/views/comparar_listas.view.php';