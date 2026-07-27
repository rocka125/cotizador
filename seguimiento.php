<?php
/**
 * seguimiento.php — Entry point del módulo de seguimiento de clientes.
 *
 * Uso:
 *   seguimiento.php              → lista de cotizaciones con alertas
 *   seguimiento.php?id=42        → historial de la cotización #42
 *
 * Solo orquesta: carga Auth, DB, Model, Controller y View.
 * Toda la lógica vive en controllers/SeguimientoListaController.php
 * Todas las queries viven en   models/SeguimientoModel.php
 * Todo el HTML vive en         views/seguimiento.view.php
 */

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/models/SeguimientoModel.php';
require_once __DIR__ . '/controllers/SeguimientoListaController.php';

$auth  = Auth::init();
$model = new SeguimientoModel($conexion, $auth->usuarioId(), $auth->esAdmin());
$ctrl  = new SeguimientoListaController($model, $auth);
$ctrl->handle();

require __DIR__ . '/views/seguimiento.view.php';