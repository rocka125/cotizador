<?php
/**
 * push_helper.php — Función auxiliar para enviar Push Notifications
 *
 * Uso en cualquier controlador o modelo:
 *
 *   require_once __DIR__ . '/push_helper.php';
 *   push_notificar($conexion, $usuario_id, 'Título', 'Mensaje', '/cotizador/dashboard.php');
 *
 * También puedes enviar a múltiples usuarios:
 *   push_notificar_usuarios($conexion, [1, 2, 3], 'Título', 'Mensaje');
 */

function push_notificar(
    mysqli $db,
    int    $usuario_id,
    string $title,
    string $body,
    string $url  = '/cotizador/dashboard.php',
    string $tag  = '',
    array  $data = []
): void {
    push_notificar_usuarios($db, [$usuario_id], $title, $body, $url, $tag, $data);
}

function push_notificar_usuarios(
    mysqli $db,
    array  $usuario_ids,
    string $title,
    string $body,
    string $url  = '/cotizador/dashboard.php',
    string $tag  = '',
    array  $data = []
): void {
    if (empty($usuario_ids)) return;

    // Cargar librerías
    $vapidConfig = __DIR__ . '/vapid_config.php';
    $webPushLib  = __DIR__ . '/WebPushLib.php';

    if (!file_exists($vapidConfig) || !file_exists($webPushLib)) return;

    require_once $vapidConfig;
    require_once $webPushLib;

    $push = new WebPushLib(VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY, VAPID_SUBJECT);

    // Obtener suscripciones de los usuarios indicados
    $placeholders = implode(',', array_fill(0, count($usuario_ids), '?'));
    $types        = str_repeat('i', count($usuario_ids));

    $stmt = $db->prepare("
        SELECT id, usuario_id, endpoint, p256dh, auth
        FROM push_subscriptions
        WHERE usuario_id IN ($placeholders)
    ");
    $stmt->bind_param($types, ...$usuario_ids);
    $stmt->execute();
    $subs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($subs)) return;

    $payload = json_encode([
        'title'   => $title,
        'body'    => $body,
        'icon'    => '/cotizador/assets/img/logoss.png',
        'badge'   => '/cotizador/assets/img/logoss.png',
        'tag'     => $tag ?: 'cotizador-' . time(),
        'url'     => $url,
        'vibrate' => [200, 100, 200],
        'data'    => $data,
    ]);

    $toDelete = [];

    foreach ($subs as $sub) {
        $result = $push->send([
            'endpoint' => $sub['endpoint'],
            'p256dh'   => $sub['p256dh'],
            'auth'     => $sub['auth'],
        ], $payload);

        // 410 Gone = el dispositivo se desuscribió; limpiar BD
        if (!$result['ok'] && in_array($result['status'], [404, 410])) {
            $toDelete[] = $sub['id'];
        }
    }

    // Limpiar suscripciones expiradas
    if (!empty($toDelete)) {
        $ids = implode(',', array_map('intval', $toDelete));
        $db->query("DELETE FROM push_subscriptions WHERE id IN ($ids)");
    }
}

/**
 * Enviar push a todos los administradores del sistema.
 */
function push_notificar_admins(mysqli $db, string $title, string $body, string $url = '/cotizador/dashboard.php'): void
{
    $result = $db->query("SELECT id FROM usuarios WHERE rol = 'admin'");
    if (!$result) return;

    $adminIds = [];
    while ($row = $result->fetch_assoc()) {
        $adminIds[] = intval($row['id']);
    }

    if (!empty($adminIds)) {
        push_notificar_usuarios($db, $adminIds, $title, $body, $url, 'admin-notif');
    }
}
