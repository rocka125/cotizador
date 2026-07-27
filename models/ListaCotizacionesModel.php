<?php

/**
 * ListaCotizacionesModel — Todas las consultas SQL del listado de cotizaciones.
 *
 * No contiene lógica HTTP ni presentación.
 * Recibe la conexión y el contexto del usuario en el constructor.
 */
class ListaCotizacionesModel
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

    // ── Helpers internos ─────────────────────────────────────────────────────

    /**
     * Construye los tipos y parámetros del prepared statement según rol y búsqueda.
     *
     * @return array{condition: string, types: string, params: array}
     */
    private function buildFiltro(string $busqueda): array
    {
        $like = "%$busqueda%";

        if ($this->esAdmin) {
            if ($busqueda !== '') {
                return [
                    'condition' => 'WHERE (numero_cotizacion LIKE ? OR empresa LIKE ? OR atencion LIKE ? OR cliente_nombre LIKE ?)',
                    'types'     => 'ssss',
                    'params'    => [$like, $like, $like, $like],
                ];
            }
            return ['condition' => '', 'types' => '', 'params' => []];
        }

        // Vendedor: solo sus cotizaciones
        if ($busqueda !== '') {
            return [
                'condition' => 'WHERE usuario_id = ? AND (numero_cotizacion LIKE ? OR empresa LIKE ? OR atencion LIKE ? OR cliente_nombre LIKE ?)',
                'types'     => 'issss',
                'params'    => [$this->usuarioId, $like, $like, $like, $like],
            ];
        }
        return [
            'condition' => 'WHERE usuario_id = ?',
            'types'     => 'i',
            'params'    => [$this->usuarioId],
        ];
    }

    // ── Métodos públicos ─────────────────────────────────────────────────────

    /**
     * Total de registros que coinciden con la búsqueda (para paginación).
     */
    public function contarCotizaciones(string $busqueda): int
    {
        $filtro = $this->buildFiltro($busqueda);
        $stmt   = $this->db->prepare("SELECT COUNT(*) AS total FROM cotizaciones {$filtro['condition']}");

        if (!empty($filtro['params'])) {
            $stmt->bind_param($filtro['types'], ...$filtro['params']);
        }

        $stmt->execute();
        return intval($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    }

    /**
     * Cotizaciones de la página actual.
     *
     * @return array[]
     */
    public function getCotizaciones(string $busqueda, int $porPagina, int $offset): array
    {
        $filtro      = $this->buildFiltro($busqueda);
        $params      = array_merge($filtro['params'], [$porPagina, $offset]);
        $types       = $filtro['types'] . 'ii';

        $stmt = $this->db->prepare(
            "SELECT * FROM cotizaciones {$filtro['condition']} ORDER BY created_at DESC LIMIT ? OFFSET ?"
        );
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Datos básicos de una cotización para auditoría (antes de borrarla).
     */
    public function getCotizacionParaAuditoria(int $id): ?array
    {
        $row = $this->db->query(
            "SELECT numero_cotizacion, cliente_nombre, total FROM cotizaciones WHERE id = $id"
        )->fetch_assoc();

        return $row ?: null;
    }

    /**
     * Elimina una cotización respetando el rol:
     * admin puede borrar cualquiera, vendedor solo las propias.
     *
     * @return bool true si se eliminó algún registro
     */
    public function eliminarCotizacion(int $id): bool
    {
        if ($this->esAdmin) {
            $stmt = $this->db->prepare('DELETE FROM cotizaciones WHERE id = ?');
            $stmt->bind_param('i', $id);
        } else {
            $stmt = $this->db->prepare('DELETE FROM cotizaciones WHERE id = ? AND usuario_id = ?');
            $stmt->bind_param('ii', $id, $this->usuarioId);
        }

        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
}
