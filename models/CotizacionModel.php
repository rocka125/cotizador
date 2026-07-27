<?php

/**
 * CotizacionModel — Queries SQL del editor de cotizaciones.
 *
 * No contiene lógica HTTP ni presentación.
 */
class CotizacionModel
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
     * Obtiene una cotización para editar.
     * Admin puede acceder a cualquiera; vendedor solo a las propias.
     *
     * @return array|null
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

    /**
     * Nombre del propietario de una cotización ajena (para aviso al admin).
     */
    public function getNombrePropietario(int $usuarioId): string
    {
        $stmt = $this->db->prepare('SELECT nombre FROM usuarios WHERE id = ?');
        $stmt->bind_param('i', $usuarioId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['nombre'] ?? 'el vendedor';
    }

    /**
     * Versión activa de la lista de precios.
     *
     * @return array|null
     */
    public function getVersionActiva(): ?array
    {
        $res = $this->db->query(
            'SELECT id, nombre, created_at FROM versiones_lista WHERE activa = 1 LIMIT 1'
        );
        return ($res && $row = $res->fetch_assoc()) ? $row : null;
    }

    /**
     * Todos los productos de la versión activa, ordenados por categoría.
     *
     * @return array[]
     */
    public function getPriceList(int $versionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT categoria AS sheet, codigo AS sku, producto,
                    descripcion AS `desc`, precio AS price
             FROM lista_precios
             WHERE activo = 1 AND version_id = ?
             ORDER BY categoria, producto'
        );
        $stmt->bind_param('i', $versionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
