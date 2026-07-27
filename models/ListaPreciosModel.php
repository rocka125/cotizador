<?php

/**
 * ListaPreciosModel — Todas las queries SQL de la lista de precios.
 *
 * No contiene lógica HTTP ni presentación.
 */
class ListaPreciosModel
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    /**
     * Todas las versiones ordenadas de más reciente a más antigua.
     *
     * @return array[]
     */
    public function getVersiones(): array
    {
        $res = $this->db->query('SELECT * FROM versiones_lista ORDER BY created_at DESC');
        return ($res) ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Categorías de la versión activa con su conteo de productos,
     * ordenadas de mayor a menor cantidad.
     *
     * @return array[]  [['categoria' => string, 'n' => int], …]
     */
    public function getCategorias(int $versionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT categoria, COUNT(*) AS n
             FROM lista_precios
             WHERE activo = 1 AND version_id = ?
             GROUP BY categoria
             ORDER BY n DESC'
        );
        $stmt->bind_param('i', $versionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Productos de una categoría y versión, para el drawer AJAX.
     *
     * @return array[]
     */
    public function getProductosPorCategoria(int $versionId, string $categoria): array
    {
        $stmt = $this->db->prepare(
            'SELECT codigo, producto, descripcion, precio
             FROM lista_precios
             WHERE activo = 1 AND version_id = ? AND categoria = ?
             ORDER BY producto ASC'
        );
        $stmt->bind_param('is', $versionId, $categoria);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
