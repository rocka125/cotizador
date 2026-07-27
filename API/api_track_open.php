<?php
/**
 * API/api_track_open.php — Pixel de rastreo de apertura de correos.
 *
 * Flujo:
 *   1. El correo incluye <img src="...api_track_open.php?tok=TOKEN">
 *   2. El cliente de correo descarga la imagen → este script se ejecuta
 *   3. Se valida el TOKEN contra cotizaciones.email_token
 *   4. Se registra en email_opens
 *   5. Primera apertura real: actualiza cotizaciones + seguimiento + push
 *   6. Siempre responde con GIF transparente 1×1px
 *
 */

// ── Log de debug ──────────────────────────────────────────────────────────
define('DEBUG', true);
$logFile = __DIR__ . '/../logs/track_debug.log';

function debugLog(string $msg): void {
    if (!DEBUG) return;
    file_put_contents(
        $GLOBALS['logFile'],
        date('[Y-m-d H:i:s] ') . $msg . PHP_EOL,
        FILE_APPEND
    );
}

ini_set('display_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', $logFile);

// ── Bypass ngrok interstitial ─────────────────────────────────────────────
header('ngrok-skip-browser-warning: 1');

// ── Función pixel — siempre responde con GIF 1×1 ─────────────────────────
function responderPixel(): never
{
    $gif = "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00"
         . "\xff\xff\xff\x00\x00\x00\x21\xf9\x04\x00\x00\x00\x00\x00"
         . "\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3b";

    header('Content-Type: image/gif');
    header('Content-Length: ' . strlen($gif));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    header('X-Content-Type-Options: nosniff');
    echo $gif;
    exit;
}

// ── 1. Leer y validar token ───────────────────────────────────────────────
$token = trim($_GET['tok'] ?? '');
debugLog("=== REQUEST === IP:" . ($_SERVER['REMOTE_ADDR'] ?? '?') . " UA:" . substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 80));
debugLog("Token recibido: '{$token}' (longitud " . strlen($token) . ")");

if (!$token || !preg_match('/^[a-f0-9]{32,64}$/i', $token)) {
    debugLog("Token inválido — saliendo");
    responderPixel();
}

debugLog("Token válido — conectando a BD");

// ── 2. Conectar a BD ──────────────────────────────────────────────────────
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../models/SeguimientoModel.php';
require_once __DIR__ . '/../core/push_helper.php';

debugLog("BD conectada — buscando token en cotizaciones");

// ── 3. Buscar token en cotizaciones ──────────────────────────────────────
$stmt = $conexion->prepare('
    SELECT
        c.id,
        c.numero_cotizacion,
        c.usuario_id,
        c.cliente_nombre,
        c.atencion,
        c.empresa,
        c.email_opened_at,
        c.email_open_count,
        u.nombre AS vendedor_nombre
    FROM cotizaciones c
    LEFT JOIN usuarios u ON u.id = c.usuario_id
    WHERE c.email_token = ?
      AND c.email_token IS NOT NULL
    LIMIT 1
');

if (!$stmt) {
    debugLog("ERROR: prepare() falló — " . $conexion->error);
    responderPixel();
}

$stmt->bind_param('s', $token);
$stmt->execute();
$cot = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cot) {
    debugLog("Token no encontrado en BD — saliendo");
    responderPixel();
}

$cotizacionId = (int)$cot['id'];
debugLog("Cotización encontrada: id={$cotizacionId} num={$cot['numero_cotizacion']}");

// ── 4. Detectar proxy ────────────────────────────────────────────────────
$userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
$ip        = obtenerIp();
$esProxy   = detectarProxy($userAgent, $ip);
debugLog("IP={$ip} esProxy=" . ($esProxy ? 'SI' : 'NO') . " UA={$userAgent}");

// ── 5. INSERT en email_opens ──────────────────────────────────────────────
$stmtOpen = $conexion->prepare('
    INSERT INTO email_opens
        (cotizacion_id, email_token, ip, user_agent, es_proxy)
    VALUES (?, ?, ?, ?, ?)
');

if (!$stmtOpen) {
    debugLog("ERROR: prepare INSERT email_opens falló — " . $conexion->error);
} else {
    $esProxyInt = $esProxy ? 1 : 0;
    $stmtOpen->bind_param('isssi', $cotizacionId, $token, $ip, $userAgent, $esProxyInt);
    $ok = $stmtOpen->execute();
    debugLog("INSERT email_opens: " . ($ok ? "OK (id={$conexion->insert_id})" : "ERROR: " . $stmtOpen->error));
    $stmtOpen->close();
}

// ── 6. Primera apertura (incluye proxy de Gmail como apertura válida) ────
// Gmail y Outlook precargan las imágenes a través de sus proxies.
// Para clientes externos esto ES la apertura real — si no contamos el proxy
// nunca registraríamos la apertura porque todo pasa por el proxy de Gmail.
// Marcamos es_proxy=1 en email_opens para estadísticas, pero sí actualizamos
// email_opened_at en la primera vez (sea proxy o no).
$esPrimeraAperturaReal = empty($cot['email_opened_at']);
debugLog("esPrimeraAperturaReal=" . ($esPrimeraAperturaReal ? 'SI' : 'NO') . " email_opened_at=" . ($cot['email_opened_at'] ?? 'NULL') . " esProxy=" . ($esProxy ? 'SI' : 'NO'));

if ($esPrimeraAperturaReal) {
    $ahora    = date('Y-m-d H:i:s');
    $stmtMark = $conexion->prepare('
        UPDATE cotizaciones
        SET email_opened_at  = ?,
            email_open_count = email_open_count + 1
        WHERE id = ?
          AND email_opened_at IS NULL
    ');
    if ($stmtMark) {
        $stmtMark->bind_param('si', $ahora, $cotizacionId);
        $stmtMark->execute();
        $afectadas = $stmtMark->affected_rows;
        $stmtMark->close();
        debugLog("UPDATE cotizaciones: {$afectadas} filas afectadas");
    } else {
        debugLog("ERROR: prepare UPDATE falló — " . $conexion->error);
        $afectadas = 0;
    }

    if ($afectadas > 0) {
        $numero     = $cot['numero_cotizacion'] ?? "COT-{$cotizacionId}";
        $cliente    = obtenerNombreCliente($cot);
        $desc       = "👁 Cotización {$numero} abierta por el cliente"
                    . ($cliente ? " ({$cliente})" : '')
                    . " — IP: {$ip}";
        $vendedorId = (int)($cot['usuario_id'] ?? 0);

        // Intentar 'apertura_email', fallback a 'email'
        $stmtSeg = $conexion->prepare('
            INSERT INTO seguimiento_cotizacion
                (cotizacion_id, usuario_id, tipo, descripcion, fecha_contacto)
            VALUES (?, ?, \'apertura_email\', ?, ?)
        ');
        if (!$stmtSeg) {
            debugLog("ENUM apertura_email no existe, usando fallback 'email'");
            $stmtSeg = $conexion->prepare('
                INSERT INTO seguimiento_cotizacion
                    (cotizacion_id, usuario_id, tipo, descripcion, fecha_contacto)
                VALUES (?, ?, \'email\', ?, ?)
            ');
        }
        if ($stmtSeg) {
            $stmtSeg->bind_param('iiss', $cotizacionId, $vendedorId, $desc, $ahora);
            $ok2 = $stmtSeg->execute();
            debugLog("INSERT seguimiento: " . ($ok2 ? "OK" : "ERROR: " . $stmtSeg->error));
            $stmtSeg->close();
        }

        if ($vendedorId > 0) {
            try {
                push_notificar(
                    $conexion,
                    $vendedorId,
                    "👁 Correo abierto — {$numero}",
                    ($cliente ?: 'El cliente') . " abrió la cotización.",
                    "/cotizador/seguimiento.php?id={$cotizacionId}",
                    'email-abierto-' . $cotizacionId,
                    ['cotizacion_id' => $cotizacionId]
                );
                debugLog("Push enviado a vendedorId={$vendedorId}");
            } catch (Throwable $e) {
                debugLog("Push falló: " . $e->getMessage());
            }
        }
    }

} else {
    $stmtCount = $conexion->prepare('
        UPDATE cotizaciones
        SET email_open_count = email_open_count + 1
        WHERE id = ?
    ');
    if ($stmtCount) {
        $stmtCount->bind_param('i', $cotizacionId);
        $stmtCount->execute();
        $stmtCount->close();
        debugLog("Apertura subsiguiente — contador incrementado");
    }
}

debugLog("=== FIN — respondiendo pixel ===");
responderPixel();

// ════════════════════════════════════════════════════════════════
// Funciones auxiliares
// ════════════════════════════════════════════════════════════════

function obtenerIp(): string
{
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
        if (filter_var($ip, FILTER_VALIDATE_IP)) return substr($ip, 0, 45);
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip  = trim($ips[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return substr($ip, 0, 45);
        }
    }
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

function detectarProxy(string $userAgent, string $ip): bool
{
    $uaPatterns = [
        'GoogleImageProxy', 'YahooMailProxy', 'Outlook', 'MailChimp',
        'Sendgrid', 'AppleMailPrivacyProxy', 'Twitterbot',
        'facebookexternalhit', 'LinkedInBot', 'Slackbot',
        'Baiduspider', 'msnbot',
        // Gateways corporativos de seguridad de correo: descargan las imágenes
        // al escanear el mensaje, antes de que el humano lo abra. Sin esto,
        // el escaneo automático marca la cotización como "abierta por el
        // cliente" de forma permanente (email_opened_at solo se fija una vez).
        'Mimecast', 'Proofpoint', 'Barracuda', 'IronPort',
        'MessageLabs', 'ATP Safe Links', 'SafeLinks',
    ];
    foreach ($uaPatterns as $pattern) {
        if (stripos($userAgent, $pattern) !== false) return true;
    }
    $ipProxyPrefixes = [
        '66.249.', '64.233.', '209.85.',
        '40.92.',  '40.107.', '52.100.',
        '17.172.', '17.57.',
    ];
    foreach ($ipProxyPrefixes as $prefix) {
        if (str_starts_with($ip, $prefix)) return true;
    }
    if (empty(trim($userAgent))) return true;
    return false;
}

function obtenerNombreCliente(array $cot): string
{
    return trim($cot['empresa'] ?? $cot['atencion'] ?? $cot['cliente_nombre'] ?? '');
}
