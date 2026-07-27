<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');

// Intentar obtener tasa en vivo desde open.er-api.com (gratuita, sin API key)
$url = 'https://open.er-api.com/v6/latest/USD';
$ctx = stream_context_create([
    'http' => [
        'timeout'        => 5,
        'ignore_errors'  => true,
        'user_agent'     => 'Fortress8Cotizador/1.0',
    ]
]);

$raw = @file_get_contents($url, false, $ctx);

if (!$raw) {
    // Fallback: tasas aproximadas si no hay conexión
    echo json_encode([
        'ok'     => true,
        'MXN'    => 18.00,
        'EUR'    => 0.92,
        'fecha'  => 'Sin conexión — tasa aproximada',
        'fuente' => 'fallback',
    ]);
    exit;
}

$data = json_decode($raw, true);

if (!$data || ($data['result'] ?? '') !== 'success') {
    echo json_encode([
        'ok'    => false,
        'error' => 'Respuesta inválida de la API de tipo de cambio',
    ]);
    exit;
}

echo json_encode([
    'ok'     => true,
    'MXN'    => round($data['rates']['MXN'] ?? 18.00, 4),
    'EUR'    => round($data['rates']['EUR'] ?? 0.92,  4),
    'fecha'  => $data['time_last_update_utc'] ?? '',
    'fuente' => 'open.er-api.com',
]);