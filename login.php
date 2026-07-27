<?php
/**
 * login.php — Entry point del formulario de acceso.
 *
 * Solo orquesta: carga Auth, Model, Controller y View.
 * Si el usuario ya tiene sesión activa, redirige directo al dashboard.
 *
 * Toda la lógica vive en controllers/LoginController.php
 * Todas las queries viven en   models/LoginModel.php
 * Todo el HTML vive en         views/login.view.php
 */

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/models/LoginModel.php';
require_once __DIR__ . '/controllers/LoginController.php';

// Si ya está autenticado no tiene caso mostrar el login
$auth = Auth::init(redirectIfUnauthenticated: false);
if ($auth->estaAutenticado()) {
    header('Location: dashboard.php');
    exit;
}

$model = new LoginModel($conexion);
$ctrl  = new LoginController($model);
$ctrl->handle();

require __DIR__ . '/views/login.view.php';
