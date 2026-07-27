<?php
/**
 * API/api_enviar_correo.php — Envía una cotización por correo con pixel de rastreo.
 *
 * Método: POST
 * Body JSON:
 *   {
 *     "cotizacion_id": 42,
 *     "email_destino": "cliente@empresa.com",
 *     "email_cc":      "copia@empresa.com",   // opcional
 *     "mensaje":       "Texto personalizado"   // opcional
 *   }
 *
 * Responde:
 *   { "ok": true, "numero": "COT-...", "email_destino": "...", "tracking": true }
 *   { "error": "mensaje de error" }
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);
set_error_handler(function($errno, $errstr) {
    // Respeta el operador "@" (p. ej. @fsockopen en EmailSender::send()):
    // dentro del handler, error_reporting() da 0 cuando el error fue suprimido
    // a propósito para que el código revise el valor de retorno con calma.
    if (error_reporting() === 0) {
        return false;
    }
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
require_once __DIR__ . '/../core/auditoria_helper.php';
require_once __DIR__ . '/../core/email_config.php';
require_once __DIR__ . '/../core/EmailSender.php';
require_once __DIR__ . '/../core/email_cotizacion_template.php';
require_once __DIR__ . '/../core/CotizacionPdf.php';
require_once __DIR__ . '/../models/SeguimientoModel.php';

// Sin redirect: es un endpoint JSON, no debe responder con un 302 a login.php
// cuando la sesión expiró (el fetch() del frontend espera JSON, no HTML).
$auth = Auth::init(redirectIfUnauthenticated: false);

if (!$auth->estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// ── Leer body JSON ────────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

$cotizacionId = intval($data['cotizacion_id'] ?? 0);
$emailDestino = trim($data['email_destino']   ?? '');
$emailCc      = trim($data['email_cc']        ?? '');
$mensaje      = trim($data['mensaje']         ?? '');

if ($cotizacionId <= 0) {
    echo json_encode(['error' => 'ID de cotización inválido']); exit;
}
if (!$emailDestino || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Email de destino inválido o vacío']); exit;
}
if ($emailCc && !filter_var($emailCc, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Email de CC inválido']); exit;
}

// ── Cargar cotización ─────────────────────────────────────────────────────
if ($auth->esAdmin()) {
    $stmt = $conexion->prepare('SELECT * FROM cotizaciones WHERE id = ?');
    $stmt->bind_param('i', $cotizacionId);
} else {
    $stmt = $conexion->prepare('SELECT * FROM cotizaciones WHERE id = ? AND usuario_id = ?');
    $uid  = $auth->usuarioId();
    $stmt->bind_param('ii', $cotizacionId, $uid);
}
$stmt->execute();
$cot = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cot) {
    echo json_encode(['error' => 'Cotización no encontrada o sin permiso']); exit;
}

// ── Generar token único para este envío (solo en memoria por ahora) ───────
// El historial de apertura (email_opened_at / email_open_count) NO se toca
// aquí: si el PDF falla o el SMTP falla más abajo, no debe perderse el
// tracking de una apertura anterior real. Solo se persiste una vez que el
// correo se envió con éxito (ver bloque tras $mailer->send()).
$emailToken = bin2hex(random_bytes(16)); // 32 caracteres hex seguros

// Verificar que las columnas de tracking existan antes de comprometernos
// a usar el pixel (si no existen, seguimos sin tracking).
$chkCols = $conexion->query("SHOW COLUMNS FROM cotizaciones LIKE 'email_token'");
if (!$chkCols || $chkCols->num_rows === 0) {
    $emailToken = null;
    error_log('[api_enviar_correo] AVISO: columnas de tracking no existen. Ejecuta migration_email_tracking.sql');
}

// ── Preparar datos para la plantilla ─────────────────────────────────────
$items = json_decode($cot['items'] ?? '[]', true) ?: [];

$empresa = [
    'nombre'    => $cot['empresa_razon_social'] ?? 'Fortress8 Cibersecurity Services SA de CV',
    'rfc'       => $cot['empresa_rfc']          ?? 'FCS180507LBA',
    'direccion' => $cot['empresa_direccion']    ?? 'Cerrada Montejo #190, El Cedro, Nacajuca, Tabasco. Cp. 86220',
    'web'       => $cot['empresa_web']          ?? 'www.fortress8.com',
    'email'     => $cot['empresa_email']        ?? 'contacto@fortress8.com',
    'tel_o'     => $cot['telefono_o']           ?? '9933179494',
    'tel_m'     => $cot['telefono_m']           ?? '9934581129',
];

// ── Generar el PDF real de la cotización (adjunto) ─────────────────────────
$condiciones = [
    't_entrega'   => $cot['tiempo_entrega']     ?? '',
    'v_servicios' => $cot['vigencia_servicios'] ?? '',
    'cond_pago'   => $cot['condiciones_pago']   ?? '',
    'l_entrega'   => $cot['lugar_entrega']      ?? '',
];
$firma = [
    'nombre'     => $cot['firma_nombre']   ?? '',
    'puesto'     => $cot['firma_puesto']   ?? '',
    'tel'        => $cot['firma_telefono'] ?? '',
    'firma_path' => $cot['firma_path']     ?? '',
    'sello_path' => $cot['sello_path']     ?? '',
];

try {
    $pdfBytes = generarCotizacionPdf($cot, $items, $empresa, $condiciones, $firma);
} catch (Throwable $e) {
    error_log('[api_enviar_correo] Error al generar PDF: ' . $e->getMessage());
    echo json_encode(['error' => 'No se pudo generar el PDF: ' . $e->getMessage()]);
    exit;
}

// ── Generar HTML CORTO del correo (el detalle completo va en el PDF) ──────
$htmlCuerpo = buildEmailCotizacionCorta($cot, $empresa, $mensaje);

// ── Inyectar pixel de rastreo al final del HTML ───────────────────────────
if ($emailToken) {
    // APP_URL viene de email_config.php — ya tiene la IP/dominio real
    // Ejemplo: 'http://172.168.50.43/cotizador'
    $pixelUrl = APP_URL . "/API/api_track_open.php?tok={$emailToken}";

    $pixelTag = "\n<!-- tracking -->\n"
              . "<img src=\"{$pixelUrl}\" width=\"1\" height=\"1\" "
              . "alt=\"\" style=\"display:none;width:1px;height:1px;border:0;\" />\n";

    if (stripos($htmlCuerpo, '</body>') !== false) {
        $htmlCuerpo = str_ireplace('</body>', $pixelTag . '</body>', $htmlCuerpo);
    } else {
        $htmlCuerpo .= $pixelTag;
    }
}

// ── Construir y enviar ────────────────────────────────────────────────────
$numero  = $cot['numero_cotizacion'] ?? "COT-{$cotizacionId}";
$cliente = trim($cot['atencion'] ?? $cot['cliente_nombre'] ?? '');

$nombreArchivoPdf = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $numero) . '.pdf';

$mailer = new EmailSender();
$mailer->setTo($emailDestino, $cliente)
       ->setSubject("Cotización {$numero} — " . ($empresa['nombre']))
       ->setBodyHtml($htmlCuerpo)
       ->attachString($pdfBytes, $nombreArchivoPdf, 'application/pdf');

if ($emailCc) {
    $mailer->addCc($emailCc);
}

$enviado = $mailer->send();

if (!$enviado) {
    $err = $mailer->getLastError();
    error_log("[api_enviar_correo] Error al enviar {$numero}: {$err}");
    echo json_encode(['error' => 'No se pudo enviar el correo: ' . $err]);
    exit;
}

// ── El correo SÍ se envió: ahora sí resetear el tracking de apertura ──────
// (antes de este punto, cualquier fallo de PDF/SMTP deja intacto el
// email_token/email_opened_at/email_open_count previos).
if ($emailToken) {
    $stmtToken = $conexion->prepare('
        UPDATE cotizaciones
        SET email_token      = ?,
            email_opened_at  = NULL,
            email_open_count = 0
        WHERE id = ?
    ');
    $stmtToken->bind_param('si', $emailToken, $cotizacionId);
    $stmtToken->execute();
    $stmtToken->close();
}

// ── Registro automático en seguimiento ────────────────────────────────────
try {
    $segModel = new SeguimientoModel($conexion, $auth->usuarioId(), $auth->esAdmin());
    $segModel->insertarAutomatico(
        $cotizacionId,
        $auth->usuarioId(),
        'email',
        "📧 Cotización {$numero} enviada por correo a {$emailDestino}."
        . ($mensaje ? " Mensaje: {$mensaje}" : '')
    );
} catch (Throwable $e) {
    error_log('[api_enviar_correo] Seguimiento falló: ' . $e->getMessage());
}

// ── Auditoría ─────────────────────────────────────────────────────────────
audit_cotizacion(
    $conexion,
    $auth->usuarioId(),
    $auth->usuarioNombre(),
    'enviar_email',
    $cotizacionId,
    $numero,
    ['email_destino' => $emailDestino]
);

echo json_encode([
    'ok'            => true,
    'numero'        => $numero,
    'email_destino' => $emailDestino,
    'tracking'      => $emailToken !== null,
]);