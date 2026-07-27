<?php

/**
 * AuditoriaModel — Todas las queries SQL del log de auditoría.
 *
 * No contiene lógica HTTP ni presentación.
 * Todas las consultas usan prepared statements o valores enteros
 * para evitar inyección SQL.
 */
class AuditoriaModel
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    // ── Usuarios ─────────────────────────────────────────────────────────────

    /**
     * Lista de usuarios para el selector de filtros.
     *
     * @return array[]  [['id'=>int, 'nombre'=>string], …]
     */
    public function getUsuarios(): array
    {
        $res = $this->db->query('SELECT id, nombre FROM usuarios ORDER BY nombre');
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    // ── Sesiones ─────────────────────────────────────────────────────────────

    /**
     * Total de registros de sesiones según filtro de usuario.
     */
    public function contarSesiones(int $usuarioId): int
    {
        $where = $usuarioId > 0 ? 'WHERE usuario_id = ?' : '';
        $stmt  = $this->db->prepare("SELECT COUNT(*) AS c FROM sesiones_auditoria $where");
        if ($usuarioId > 0) $stmt->bind_param('i', $usuarioId);
        $stmt->execute();
        return intval($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    }

    /**
     * Página de registros de sesiones (login/logout).
     *
     * @return array[]
     */
    public function getSesiones(int $usuarioId, int $limit, int $offset): array
    {
        $where = $usuarioId > 0 ? 'WHERE s.usuario_id = ?' : '';
        $stmt  = $this->db->prepare("
            SELECT s.*, u.correo
            FROM sesiones_auditoria s
            LEFT JOIN usuarios u ON u.id = s.usuario_id
            $where
            ORDER BY s.created_at DESC
            LIMIT ? OFFSET ?
        ");
        if ($usuarioId > 0) {
            $stmt->bind_param('iii', $usuarioId, $limit, $offset);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ── Auditoría de cotizaciones ─────────────────────────────────────────────

    /**
     * Total de movimientos de cotizaciones según filtros.
     */
    public function contarAuditorias(int $usuarioId, string $accion): int
    {
        [$where, $types, $params] = $this->buildAuditFilter($usuarioId, $accion);
        $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM auditoria_cotizaciones $where");
        if ($types) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return intval($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    }

    /**
     * Página de movimientos de cotizaciones.
     *
     * @return array[]
     */
    public function getAuditorias(int $usuarioId, string $accion, int $limit, int $offset): array
    {
        [$where, $types, $params] = $this->buildAuditFilter($usuarioId, $accion);
        $stmt = $this->db->prepare("
            SELECT a.*, u.correo
            FROM auditoria_cotizaciones a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            $where
            ORDER BY a.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $params[] = $limit;
        $params[] = $offset;
        $stmt->bind_param($types . 'ii', ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ── Estadísticas ─────────────────────────────────────────────────────────

    /**
     * Conteos rápidos para las tarjetas de estadísticas.
     *
     * @return array{logins_hoy:int, creadas_hoy:int, editadas_hoy:int, eliminadas:int}
     */
    public function getStats(): array
    {
        $q = fn(string $sql) => intval($this->db->query($sql)->fetch_assoc()['c'] ?? 0);
        return [
            'logins_hoy'   => $q("SELECT COUNT(*) c FROM sesiones_auditoria WHERE accion='login' AND DATE(created_at)=CURDATE()"),
            'creadas_hoy'  => $q("SELECT COUNT(*) c FROM auditoria_cotizaciones WHERE accion='crear' AND DATE(created_at)=CURDATE()"),
            'editadas_hoy' => $q("SELECT COUNT(*) c FROM auditoria_cotizaciones WHERE accion='editar' AND DATE(created_at)=CURDATE()"),
            'eliminadas'   => $q("SELECT COUNT(*) c FROM auditoria_cotizaciones WHERE accion='eliminar'"),
        ];
    }

    // ── Helpers internos ─────────────────────────────────────────────────────

    /**
     * Construye la cláusula WHERE, tipos y parámetros para las queries de auditoría.
     *
     * @return array{0:string, 1:string, 2:array}  [where, types, params]
     */
    private function buildAuditFilter(int $usuarioId, string $accion): array
    {
        $conditions = [];
        $types      = '';
        $params     = [];

        if ($usuarioId > 0) {
            $conditions[] = 'usuario_id = ?';
            $types       .= 'i';
            $params[]     = $usuarioId;
        }
        if ($accion !== '') {
            $conditions[] = 'accion = ?';
            $types       .= 's';
            $params[]     = $accion;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return [$where, $types, $params];
    }
}
