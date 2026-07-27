<?php
/**
 * core/tracking_helper.php — Helpers de display para el email tracking.
 *
 * Incluir en seguimiento_split.view.php o en SeguimientoModel cuando
 * se quiera mostrar el estado de apertura de correo en la UI.
 *
 * Uso:
 *   require_once __DIR__ . '/../core/tracking_helper.php';
 *   echo tracking_badge($cotizacion);
 *   echo tracking_stats($cotizacionId, $conexion);
 */

/**
 * Genera el HTML del badge de apertura de email para la lista/detalle.
 *
 * Ejemplos de output:
 *   👁 Abierto  (verde, con fecha y número de veces)
 *   📧 Enviado  (azul, sin abrir)
 *   —           (sin correo enviado)
 */
function tracking_badge(array $cot): string
{
    $tieneToken  = !empty($cot['email_token']);
    $abierto     = !empty($cot['email_opened_at']);
    $openCount   = (int)($cot['email_open_count'] ?? 0);

    if ($abierto) {
        $fecha    = date('d/m H:i', strtotime($cot['email_opened_at']));
        $veces    = $openCount > 1 ? " ({$openCount}×)" : '';
        return '<span style="font-size:10px;color:#4ade80;display:inline-flex;align-items:center;gap:3px;">'
             . '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12S5 4 12 4s11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>'
             . " {$fecha}{$veces}</span>";
    }

    if ($tieneToken) {
        return '<span style="font-size:10px;color:#60a5fa;display:inline-flex;align-items:center;gap:3px;">'
             . '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 7 10-7"/></svg>'
             . ' Enviado</span>';
    }

    return '<span style="font-size:10px;color:var(--ink3,#5C5478);">—</span>';
}

/**
 * Devuelve el array de estadísticas de tracking para una cotización.
 * Útil para el panel derecho del split view.
 *
 * @return array{
 *   enviado: bool,
 *   abierto: bool,
 *   primera_apertura: string|null,
 *   total_aperturas: int,
 *   aperturas_reales: int,
 *   aperturas_proxy: int,
 *   ultima_apertura: string|null,
 * }
 */
function tracking_stats(int $cotizacionId, mysqli $db): array
{
    $stmt = $db->prepare('
        SELECT
            COUNT(*)                          AS total,
            SUM(es_proxy = 0)                 AS reales,
            SUM(es_proxy = 1)                 AS proxies,
            MAX(CASE WHEN es_proxy = 0 THEN opened_at END) AS ultima_real
        FROM email_opens
        WHERE cotizacion_id = ?
    ');

    if (!$stmt) {
        return ['enviado' => false, 'abierto' => false, 'primera_apertura' => null,
                'total_aperturas' => 0, 'aperturas_reales' => 0,
                'aperturas_proxy' => 0, 'ultima_apertura' => null];
    }

    $stmt->bind_param('i', $cotizacionId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Obtener datos básicos de la cotización
    $stmtCot = $db->prepare('SELECT email_token, email_opened_at, email_open_count FROM cotizaciones WHERE id = ?');
    $stmtCot->bind_param('i', $cotizacionId);
    $stmtCot->execute();
    $cot = $stmtCot->get_result()->fetch_assoc() ?? [];
    $stmtCot->close();

    return [
        'enviado'          => !empty($cot['email_token']),
        'abierto'          => !empty($cot['email_opened_at']),
        'primera_apertura' => $cot['email_opened_at'] ?? null,
        'total_aperturas'  => (int)($row['total'] ?? 0),
        'aperturas_reales' => (int)($row['reales'] ?? 0),
        'aperturas_proxy'  => (int)($row['proxies'] ?? 0),
        'ultima_apertura'  => $row['ultima_real'] ?? null,
    ];
}

/**
 * Genera el HTML del bloque de tracking para mostrar en el panel derecho.
 * Úsalo en seguimiento_split.view.php dentro del right-header.
 */
function tracking_block_html(array $stats): string
{
    if (!$stats['enviado']) return '';

    $html = '<div style="margin-top:8px;padding:8px 12px;border-radius:7px;'
          . 'background:rgba(255,255,255,.03);border:0.5px solid rgba(255,255,255,.06);">';
    $html .= '<div style="font-size:9px;text-transform:uppercase;letter-spacing:.05em;'
           . 'color:var(--ink3,#5C5478);margin-bottom:5px;">Estado del correo</div>';

    if ($stats['abierto']) {
        $fecha = date('d M Y · H:i', strtotime($stats['primera_apertura']));
        $html .= '<div style="display:flex;align-items:center;gap:6px;font-size:11px;">';
        $html .= '<span style="color:#4ade80;">👁 Abierto por primera vez</span>';
        $html .= '<span style="color:var(--ink3,#5C5478);">' . htmlspecialchars($fecha) . '</span>';
        $html .= '</div>';

        if ($stats['aperturas_reales'] > 1) {
            $html .= '<div style="font-size:10px;color:var(--ink3,#5C5478);margin-top:2px;">'
                   . 'Visto ' . $stats['aperturas_reales'] . ' veces en total</div>';
        }
    } else {
        $html .= '<div style="font-size:11px;color:#60a5fa;">📧 Enviado — pendiente de apertura</div>';
    }

    if ($stats['aperturas_proxy'] > 0) {
        $html .= '<div style="font-size:9px;color:var(--ink3,#5C5478);margin-top:3px;font-style:italic;">'
               . $stats['aperturas_proxy'] . ' request(s) de proxy detectado(s)</div>';
    }

    $html .= '</div>';
    return $html;
}
