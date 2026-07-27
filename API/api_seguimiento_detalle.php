<?php
/**
 * API/api_seguimiento_detalle.php — Endpoint dual para la vista split.
 *
 * GET  ?id=N  → { cotizacion: {...}, historial: [...] }
 * POST        → guarda un seguimiento manual, responde { ok, id, ... }
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);
set_error_handler(function($errno, $errstr) {
    http_response_code(500);
    echo json_encode(['error' => "PHP [{$errno}]: {$errstr}"]);
    exit;
});
set_exception_handler(function(Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
});

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../models/SeguimientoModel.php';

// Sin redirect: es un endpoint JSON, no debe responder con un 302 a login.php
// cuando la sesión expiró (el fetch() del frontend espera JSON, no HTML).
$auth = Auth::init(redirectIfUnauthenticated: false);

if (!$auth->estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$model = new SeguimientoModel($conexion, $auth->usuarioId(), $auth->esAdmin());

// ════════════════════════════════════════════════════════════════
//  GET: devolver cotización + historial
// ════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    if (!$model->cotizacionAccesible($id)) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin acceso a esta cotización']);
        exit;
    }

    $cot      = $model->getCotizacionResumen($id);
    $historial = $model->getHistorial($id);

    // Campo de display unificado para el header derecho
    $cot['cliente_display'] = trim(
        $cot['empresa'] ?? $cot['atencion'] ?? $cot['cliente_nombre'] ?? ''
    ) ?: '—';

    // Estado de tracking de email para mostrar en panel derecho
    $cot['email_enviado'] = !empty($cot['email_token']);
    $cot['email_abierto'] = !empty($cot['email_opened_at']);

    // Agregar tiempo relativo a cada registro del historial
    foreach ($historial as &$h) {
        $h['tiempo_relativo'] = tiempoRelativo($h['fecha_contacto'] ?? '');
    }
    unset($h);

    echo json_encode([
        'cotizacion' => $cot,
        'historial'  => $historial,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ════════════════════════════════════════════════════════════════
//  POST: guardar seguimiento manual
// ════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!$data) $data = $_POST;

    $cotizacionId    = intval($data['cotizacion_id']    ?? 0);
    $tipo            = trim($data['tipo']               ?? '');
    $descripcion     = trim($data['descripcion']        ?? '');
    $fechaContacto   = trim($data['fecha_contacto']     ?? '');
    $proximoContacto = trim($data['proximo_contacto']   ?? '') ?: null;

    if ($cotizacionId <= 0) {
        echo json_encode(['error' => 'ID de cotización inválido']); exit;
    }
    if (!in_array($tipo, SeguimientoModel::TIPOS_VALIDOS)) {
        echo json_encode(['error' => 'Tipo inválido']); exit;
    }
    if (strlen($descripcion) < 3) {
        echo json_encode(['error' => 'La descripción es muy corta']); exit;
    }
    if (!$model->cotizacionAccesible($cotizacionId)) {
        http_response_code(403);
        echo json_encode(['error' => 'Sin acceso a esta cotización']); exit;
    }

    // Normalizar fecha
    if (!$fechaContacto || !strtotime($fechaContacto)) {
        $fechaContacto = date('Y-m-d H:i:s');
    } elseif (strlen($fechaContacto) === 10) {
        $fechaContacto .= ' ' . date('H:i:s');
    } else {
        $fechaContacto = str_replace('T', ' ', $fechaContacto);
        if (strlen($fechaContacto) === 16) $fechaContacto .= ':00';
    }

    $nuevoId = $model->insertar(
        $cotizacionId,
        $tipo,
        $descripcion,
        $fechaContacto,
        $proximoContacto
    );

    if (!$nuevoId) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar el seguimiento']);
        exit;
    }

    echo json_encode([
        'ok'               => true,
        'id'               => $nuevoId,
        'tipo'             => $tipo,
        'descripcion'      => $descripcion,
        'fecha_contacto'   => $fechaContacto,
        'proximo_contacto' => $proximoContacto,
        'usuario_nombre'   => $auth->usuarioNombre(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Método no permitido
http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);

// ── Helper ────────────────────────────────────────────────────────────────
function tiempoRelativo(string $fecha): string {
    if (!$fecha) return '';
    $d = time() - strtotime($fecha);
    if ($d < 60)     return 'hace un momento';
    if ($d < 3600)   return 'hace ' . floor($d / 60) . ' min';
    if ($d < 86400)  return 'hace ' . floor($d / 3600) . ' h';
    if ($d < 172800) return 'ayer';
    if ($d < 604800) return 'hace ' . floor($d / 86400) . ' días';
    return date('d M Y', strtotime($fecha));
}
