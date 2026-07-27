<?php

if (!function_exists('audit_ip')) {
    function audit_ip(): string {
        foreach (['HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','REMOTE_ADDR'] as $k) {
            if (!empty($_SERVER[$k])) {
                return substr(trim(explode(',', $_SERVER[$k])[0]), 0, 45);
            }
        }
        return '0.0.0.0';
    }
}

/** Registra un login o logout en sesiones_auditoria. */
if (!function_exists('audit_sesion')) {
    function audit_sesion(mysqli $db, int $uid, string $nombre, string $accion): void {
        $ip = audit_ip();
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $u  = $db->real_escape_string($nombre);
        $ua = $db->real_escape_string($ua);
        $ip = $db->real_escape_string($ip);
        $a  = $db->real_escape_string($accion);
        $db->query("INSERT INTO sesiones_auditoria (usuario_id, usuario_nombre, accion, ip, user_agent)
                    VALUES ($uid,'$u','$a','$ip','$ua')");
    }
}

/**
 * Registra un movimiento sobre una cotización.
 * $accion: 'crear' | 'editar' | 'eliminar' | 'cambio_estado' | 'ver'
 * $detalle: array asociativo con datos relevantes del cambio.
 */
if (!function_exists('audit_cotizacion')) {
    function audit_cotizacion(
        mysqli $db,
        int    $uid,
        string $nombre,
        string $accion,
        int    $cot_id   = 0,
        string $num_cot  = '',
        array  $detalle  = []
    ): void {
        $ip  = audit_ip();
        $u   = $db->real_escape_string($nombre);
        $a   = $db->real_escape_string($accion);
        $n   = $db->real_escape_string($num_cot);
        $ip  = $db->real_escape_string($ip);
        $det = $detalle ? "'" . $db->real_escape_string(json_encode($detalle, JSON_UNESCAPED_UNICODE)) . "'" : 'NULL';
        $cid = $cot_id > 0 ? $cot_id : 'NULL';
        $db->query("INSERT INTO auditoria_cotizaciones
                        (usuario_id, usuario_nombre, cotizacion_id, numero_cotizacion, accion, detalle, ip)
                    VALUES ($uid,'$u',$cid,'$n','$a',$det,'$ip')");
    }
}

/**
 * Notifica a todos los admins (excepto el propio actor) sobre un evento.
 */
if (!function_exists('notificar_admins')) {
    function notificar_admins(mysqli $db, int $actor_id, int $cot_id, string $mensaje): void {
        $admins = $db->query(
            "SELECT id FROM usuarios WHERE rol='admin' AND id <> $actor_id"
        );
        if (!$admins) return;
        $msg      = $db->real_escape_string($mensaje);
        $adminIds = [];
        while ($a = $admins->fetch_assoc()) {
            $db->query("INSERT INTO notificaciones (usuario_id, cotizacion_id, mensaje, creado_por)
                        VALUES ({$a['id']}, $cot_id, '$msg', $actor_id)");
            $adminIds[] = intval($a['id']);
        }
        // Disparar Web Push a cada admin notificado
        if (!empty($adminIds)) {
            $pushFile = __DIR__ . '/push_helper.php';
            if (file_exists($pushFile)) {
                require_once $pushFile;
                push_notificar_usuarios($db, $adminIds, '🔔 Cotizador', $mensaje);
            }
        }
    }
}