<?php

/**
 * CompararListasModel — Queries SQL del comparador de versiones.
 *
 * No contiene lógica HTTP ni presentación.
 * La lógica de comparación (diff entre mapas de SKUs) vive
 * en el controller, no aquí — este modelo solo lee filas de BD.
 */
class CompararListasModel
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
        $res = $this->db->query(
            'SELECT id, nombre, total_skus, activa, created_at
             FROM versiones_lista
             ORDER BY created_at DESC'
        );
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Todos los SKUs activos de una versión, con filtro opcional de categoría.
     * Devuelve un array indexado por código (SKU) para comparación O(1).
     *
     * @return array<string, array{codigo:string, categoria:string, descripcion:string, precio:string}>
     */
    public function getSkusIndexados(int $versionId, string $categoria = ''): array
    {
        if ($categoria !== '') {
            $stmt = $this->db->prepare(
                'SELECT codigo, categoria, descripcion, precio
                 FROM lista_precios
                 WHERE activo = 1 AND version_id = ? AND categoria = ?
                 ORDER BY codigo ASC'
            );
            $stmt->bind_param('is', $versionId, $categoria);
        } else {
            $stmt = $this->db->prepare(
                'SELECT codigo, categoria, descripcion, precio
                 FROM lista_precios
                 WHERE activo = 1 AND version_id = ?
                 ORDER BY codigo ASC'
            );
            $stmt->bind_param('i', $versionId);
        }

        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Indexar por código para búsqueda O(1) en el controller
        $map = [];
        foreach ($rows as $r) {
            $map[$r['codigo']] = $r;
        }
        return $map;
    }

    /**
     * Categorías de una versión con su conteo de productos.
     * Usado en el AJAX ?api=cats para poblar el selector de filtro.
     *
     * @return array[]  [['categoria'=>string, 'n'=>int], …]
     */
    public function getCategorias(int $versionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT categoria, COUNT(*) AS n
             FROM lista_precios
             WHERE activo = 1 AND version_id = ?
             GROUP BY categoria
             ORDER BY categoria ASC'
        );
        $stmt->bind_param('i', $versionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}