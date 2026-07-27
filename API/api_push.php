<?php
/**
 * API/api_push.php — Gestión de suscripciones Push y envío de notificaciones
 *
 * GET  ?action=vapid_key         → devuelve la clave pública VAPID
 * POST action=subscribe          → guarda o actualiza una suscripción
 * POST action=unsubscribe        → elimina una suscripción
 * POST action=send_test          → envía notificación de prueba al usuario actual
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/vapid_config.php';

$usuario_id = intval($_SESSION['id']);
$metodo     = $_SERVER['REQUEST_METHOD'];

// Leer body JSON si aplica (subscribe/unsubscribe/send_test lo envían así)
$bodyRaw  = file_get_contents('php://input');
$bodyJson = $bodyRaw ? (json_decode($bodyRaw, true) ?? []) : [];

// La acción puede venir por GET, por POST form-encoded, o dentro del JSON
$accion = $_GET['action']
       ?? $_POST['action']
       ?? $bodyJson['action']
       ?? '';

// ── GET: devolver clave pública VAPID ─────────────────────────────────────
if ($metodo === 'GET' && $accion === 'vapid_key') {
    echo json_encode(['ok' => true, 'publicKey' => VAPID_PUBLIC_KEY]);
    exit;
}

// ── POST: suscribir dispositivo ───────────────────────────────────────────
if ($metodo === 'POST' && $accion === 'subscribe') {
    $endpoint = $bodyJson['endpoint'] ?? '';
    $p256dh   = $bodyJson['keys']['p256dh'] ?? '';
    $auth     = $bodyJson['keys']['auth'] ?? '';
    $ua       = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

    if (!$endpoint || !$p256dh || !$auth) {
        echo json_encode(['error' => 'Datos de suscripción incompletos']);
        exit;
    }

    // INSERT OR UPDATE (un endpoint = un dispositivo)
    $stmt = $conexion->prepare("
        INSERT INTO push_subscriptions (usuario_id, endpoint, p256dh, auth, user_agent)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            usuario_id = VALUES(usuario_id),
            p256dh     = VALUES(p256dh),
            auth       = VALUES(auth),
            user_agent = VALUES(user_agent),
            updated_at = NOW()
    ");
    $stmt->bind_param('issss', $usuario_id, $endpoint, $p256dh, $auth, $ua);
    $stmt->execute();

    echo json_encode(['ok' => true, 'msg' => 'Suscripción guardada']);
    exit;
}

// ── POST: desuscribir dispositivo ─────────────────────────────────────────
if ($metodo === 'POST' && $accion === 'unsubscribe') {
    $endpoint = $bodyJson['endpoint'] ?? '';

    if ($endpoint) {
        $stmt = $conexion->prepare("DELETE FROM push_subscriptions WHERE endpoint = ? AND usuario_id = ?");
        $stmt->bind_param('si', $endpoint, $usuario_id);
        $stmt->execute();
    }

    echo json_encode(['ok' => true]);
    exit;
}

// ── POST: enviar notificación de prueba ───────────────────────────────────
if ($metodo === 'POST' && $accion === 'send_test') {
    require_once __DIR__ . '/../core/WebPushLib.php';

    $push = new WebPushLib(VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY, VAPID_SUBJECT);

    $payload = json_encode([
        'title'   => '✅ ¡Notificaciones activas!',
        'body'    => 'Las notificaciones push del Cotizador están funcionando correctamente.',
        'icon'    => '/cotizador/assets/img/logoss.png',
        'badge'   => '/cotizador/assets/img/logoss.png',
        'tag'     => 'test-' . time(),
        'url'     => '/cotizador/dashboard.php',
        'vibrate' => [100, 50, 100],
    ]);

    // Obtener suscripciones del usuario actual
    $stmt = $conexion->prepare("SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE usuario_id = ?");
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $subs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($subs)) {
        echo json_encode(['ok' => false, 'error' => 'No hay dispositivos suscritos. Activa las notificaciones primero.']);
        exit;
    }

    $results = [];
    foreach ($subs as $sub) {
        $results[] = $push->send($sub, $payload);
    }

    $allOk = array_filter($results, fn($r) => $r['ok']);
    echo json_encode([
        'ok'      => count($allOk) > 0,
        'sent'    => count($allOk),
        'total'   => count($subs),
        'results' => $results,
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Acción no reconocida']);
