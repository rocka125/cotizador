<?php

/**
 * api_seguimiento.php — Endpoint para registrar contactos de seguimiento.
 *
 * POST /cotizador/API/api_seguimiento.php
 * Body JSON: { cotizacion_id, tipo, descripcion, fecha_contacto?, proximo_contacto? }
 *
 * Responde JSON: { ok: true, id, ... } | { error: string }
 */

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../models/SeguimientoModel.php';
require_once __DIR__ . '/../controllers/SeguimientoController.php';

$auth  = Auth::init();
$model = new SeguimientoModel($conexion, $auth->usuarioId(), $auth->esAdmin());
$ctrl  = new SeguimientoController($model, $auth);
$ctrl->handle();