<?php

/**
 * VerCotizacionModel — Queries SQL para ver una cotización.
 *
 * No contiene lógica HTTP ni presentación.
 * Recibe la conexión y el contexto del usuario en el constructor.
 */
class VerCotizacionModel
{
    private mysqli $db;
    private int    $usuarioId;
    private bool   $esAdmin;

    public function __construct(mysqli $db, int $usuarioId, bool $esAdmin)
    {
        $this->db        = $db;
        $this->usuarioId = $usuarioId;
        $this->esAdmin   = $esAdmin;
    }

    /**
     * Obtiene una cotización por ID.
     * Admin puede ver cualquiera; vendedor solo las propias.
     *
     * @return array|null  null si no existe o no tiene permiso
     */
    public function getCotizacion(int $id): ?array
    {
        if ($this->esAdmin) {
            $stmt = $this->db->prepare('SELECT * FROM cotizaciones WHERE id = ?');
            $stmt->bind_param('i', $id);
        } else {
            $stmt = $this->db->prepare('SELECT * FROM cotizaciones WHERE id = ? AND usuario_id = ?');
            $stmt->bind_param('ii', $id, $this->usuarioId);
        }

        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return $row ?: null;
    }
}
