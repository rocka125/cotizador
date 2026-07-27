<?php

/**
 * CambiarEstadoModel — Queries SQL del endpoint de cambio de estado.
 *
 * No contiene lógica de negocio ni HTTP.
 * Todas las queries usan prepared statements — sin concatenación de variables.
 */
class CambiarEstadoModel
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Datos actuales de la cotización (estado anterior, número, dueño).
     *
     * @return array{estado:string, numero_cotizacion:string, usuario_id:int}|null
     */
    public function getCotizacion(int $id): ?array
    {
        // Prepared statement — el original usaba concatenación directa (vulnerabilidad)
        $stmt = $this->db->prepare(
            'SELECT estado, numero_cotizacion, usuario_id FROM cotizaciones WHERE id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Actualiza el estado de la cotización.
     *
     * @return bool true si se modificó al menos una fila
     */
    public function actualizarEstado(int $id, string $nuevoEstado): bool
    {
        $stmt = $this->db->prepare('UPDATE cotizaciones SET estado = ? WHERE id = ?');
        $stmt->bind_param('si', $nuevoEstado, $id);
        $stmt->execute();
        return $stmt->affected_rows > 0 || $stmt->errno === 0;
    }

    /**
     * Inserta una notificación para el dueño de la cotización.
     * Usa prepared statement — el original usaba real_escape_string (frágil).
     */
    public function notificarDueno(
        int    $duenoId,
        int    $cotizacionId,
        string $mensaje,
        int    $creadoPor
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO notificaciones (usuario_id, cotizacion_id, mensaje, creado_por)
             VALUES (?, ?, ?, ?)'
        );
        if (!$stmt) return;
        $stmt->bind_param('iisi', $duenoId, $cotizacionId, $mensaje, $creadoPor);
        $stmt->execute();
        // Disparar Web Push al dueño de la cotización
        $pushFile = __DIR__ . '/../core/push_helper.php';
        if (file_exists($pushFile)) {
            require_once $pushFile;
            push_notificar($this->db, $duenoId, '🔔 Cotizador', $mensaje);
        }
    }
}
