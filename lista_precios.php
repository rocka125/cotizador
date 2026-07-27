<?php
/**
 * lista_precios.php — Entry point de la lista de precios.
 *
 * Solo orquesta: carga Auth, DB, Model, Controller y View.
 * Toda la lógica vive en controllers/ListaPreciosController.php
 * Todas las queries viven en   models/ListaPreciosModel.php
 * Todo el HTML vive en         views/lista_precios.view.php
 */

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/models/ListaPreciosModel.php';
require_once __DIR__ . '/controllers/ListaPreciosController.php';

$auth  = Auth::init();
$model = new ListaPreciosModel($conexion);
$ctrl  = new ListaPreciosController($model);
$ctrl->handle();

require __DIR__ . '/views/lista_precios.view.php';
