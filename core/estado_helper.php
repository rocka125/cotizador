<?php

// ── Helpers de estado de cotización ─────────────────────────────────────────
if (!defined('ESTADOS_VALIDOS')) {
    define('ESTADOS_VALIDOS', ['pendiente', 'aprobada', 'rechazada']);
}

if (!function_exists('normalizar_estado')) {
    /**
     * Devuelve siempre un estado válido en minúsculas.
     * Cualquier valor vacío, NULL o corrupto (ej. "0") se convierte en 'pendiente'.
     * Los estados legados 'activa' y 'aceptada' se equiparan a 'aprobada', y
     * 'cancelada' se equipara a 'rechazada', para que registros antiguos con
     * esos valores sigan clasificándose correctamente.
     */
    function normalizar_estado($estado) {
        $e = strtolower(trim((string)($estado ?? '')));
        if (in_array($e, ['activa', 'aceptada'], true)) {
            return 'aprobada';
        }
        if ($e === 'cancelada') {
            return 'rechazada';
        }
        if (!in_array($e, ESTADOS_VALIDOS, true)) {
            return 'pendiente';
        }
        return $e;
    }
}

if (!function_exists('estado_label')) {
    /** Etiqueta legible para mostrar en la UI. */
    function estado_label($estado) {
        $labels = [
            'pendiente' => 'Pendiente',
            'aprobada'  => 'Aprobada',
            'rechazada' => 'Rechazada',
        ];
        $e = normalizar_estado($estado);
        return $labels[$e] ?? ucfirst($e);
    }
}
