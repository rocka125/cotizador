<?php

/**
 * NotificacionesModel — Todas las consultas SQL del centro de notificaciones.
 *
 * No contiene lógica HTTP ni presentación.
 * Recibe la conexión y el usuario en el constructor.
 */
class NotificacionesModel
{
    private mysqli $db;
    private int    $usuarioId;

    public function __construct(mysqli $db, int $usuarioId)
    {
        $this->db        = $db;
        $this->usuarioId = $usuarioId;
    }

    /**
     * Últimas notificaciones del usuario, más recientes primero.
     *
     * @return array[]
     */
    public function listar(int $limite = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT n.id, n.cotizacion_id, n.mensaje, n.leido, n.created_at,
                   c.numero_cotizacion
            FROM notificaciones n
            LEFT JOIN cotizaciones c ON c.id = n.cotizacion_id
            WHERE n.usuario_id = ?
            ORDER BY n.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("ii", $this->usuarioId, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Cantidad de notificaciones sin leer del usuario.
     */
    public function contarNoLeidas(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS n FROM notificaciones WHERE usuario_id = ? AND leido = 0");
        $stmt->bind_param("i", $this->usuarioId);
        $stmt->execute();

        return intval($stmt->get_result()->fetch_assoc()['n'] ?? 0);
    }

    /**
     * Marca una notificación puntual como leída (solo si pertenece al usuario).
     *
     * @return bool true si se actualizó algún registro
     */
    public function marcarLeida(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE notificaciones SET leido = 1 WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $id, $this->usuarioId);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }

    /**
     * Marca todas las notificaciones sin leer del usuario como leídas.
     */
    public function marcarTodas(): bool
    {
        $stmt = $this->db->prepare("UPDATE notificaciones SET leido = 1 WHERE usuario_id = ? AND leido = 0");
        $stmt->bind_param("i", $this->usuarioId);

        return $stmt->execute();
    }
}
