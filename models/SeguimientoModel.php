<?php

/**
 * SeguimientoModel — Queries SQL del módulo de seguimiento de clientes.
 *
 * Responsabilidades:
 *  - Insertar un nuevo registro de contacto.
 *  - Obtener el historial de seguimiento de una cotización.
 *  - Obtener cotizaciones sin contacto en N días (para dashboard y alertas).
 *
 * No contiene lógica HTTP ni presentación.
 */
class SeguimientoModel
{
    private mysqli $db;
    private int    $usuarioId;
    private bool   $esAdmin;

    /** Tipos válidos de contacto. */
    public const TIPOS_VALIDOS = ['llamada', 'email', 'visita', 'whatsapp', 'nota', 'apertura_email'];

    public function __construct(mysqli $db, int $usuarioId, bool $esAdmin)
    {
        $this->db        = $db;
        $this->usuarioId = $usuarioId;
        $this->esAdmin   = $esAdmin;
    }

    // ── Escritura ─────────────────────────────────────────────────────────────

    /**
     * Inserta un registro de seguimiento.
     * Devuelve el ID insertado o 0 si falló.
     */
    public function insertar(
        int     $cotizacionId,
        string  $tipo,
        string  $descripcion,
        string  $fechaContacto,
        ?string $proximoContacto = null
    ): int {
        $stmt = $this->db->prepare('
            INSERT INTO seguimiento_cotizacion
                (cotizacion_id, usuario_id, tipo, descripcion, fecha_contacto, proximo_contacto)
            VALUES (?, ?, ?, ?, ?, ?)
        ');

        $stmt->bind_param(
            'iissss',
            $cotizacionId,
            $this->usuarioId,
            $tipo,
            $descripcion,
            $fechaContacto,
            $proximoContacto
        );

        $stmt->execute();
        return $stmt->affected_rows > 0 ? (int)$this->db->insert_id : 0;
    }

    /**
     * Inserta un registro automático (sin validación de permisos).
     * Usado internamente por los controllers al detectar eventos del sistema.
     */
    public function insertarAutomatico(
        int    $cotizacionId,
        int    $autorId,
        string $tipo,
        string $descripcion
    ): void {
        $fecha = date('Y-m-d H:i:s');
        $stmt  = $this->db->prepare('
            INSERT INTO seguimiento_cotizacion
                (cotizacion_id, usuario_id, tipo, descripcion, fecha_contacto)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->bind_param('iisss', $cotizacionId, $autorId, $tipo, $descripcion, $fecha);
        $stmt->execute();
    }

    // ── Lectura ───────────────────────────────────────────────────────────────

    /**
     * Historial completo de seguimiento de una cotización, más reciente primero.
     *
     * @return array[]
     */
    public function getHistorial(int $cotizacionId): array
    {
        $stmt = $this->db->prepare('
            SELECT
                s.id,
                s.tipo,
                s.descripcion,
                s.fecha_contacto,
                s.proximo_contacto,
                u.nombre AS usuario_nombre
            FROM seguimiento_cotizacion s
            JOIN usuarios u ON u.id = s.usuario_id
            WHERE s.cotizacion_id = ?
            ORDER BY s.fecha_contacto DESC
        ');
        $stmt->bind_param('i', $cotizacionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Cotizaciones con estado de seguimiento según filtro.
     * Filtros: 'activas' (pendiente/activa), 'todas'
     * Respeta el rol: admin ve todas, vendedor solo las suyas.
     * Solo incluye cotizaciones que ya fueron enviadas por correo
     * (tienen al menos un registro de seguimiento con tipo = 'email').
     *
     * @return array[]
     */
    public function getCotizacionesSeguimiento(string $filtro = 'activas'): array
    {
        $estadosFiltro = match($filtro) {
            'todas'      => "'pendiente','activa','aprobada','aceptada','rechazada','cancelada'",
            default      => "'pendiente','activa'",   // 'activas'
        };

        $whereRol = $this->esAdmin ? '' : 'AND c.usuario_id = ?';

        $sql = "
            SELECT
                c.id,
                c.numero_cotizacion,
                c.cliente_nombre,
                c.empresa,
                c.atencion,
                c.total,
                c.moneda,
                c.estado,
                c.created_at,
                c.email_token,
                c.email_opened_at,
                c.email_open_count,
                COALESCE(MAX(s.fecha_contacto), c.created_at) AS ultimo_contacto,
                DATEDIFF(NOW(), COALESCE(MAX(s.fecha_contacto), c.created_at)) AS dias_sin_contacto,
                u.nombre AS vendedor_nombre,
                MIN(CASE WHEN s.tipo = 'email' THEN s.fecha_contacto END) AS primer_email
            FROM cotizaciones c
            LEFT JOIN seguimiento_cotizacion s ON s.cotizacion_id = c.id
            LEFT JOIN usuarios u ON u.id = c.usuario_id
            WHERE COALESCE(NULLIF(TRIM(c.estado), ''), 'pendiente') IN ($estadosFiltro)
            $whereRol
            GROUP BY c.id
            HAVING primer_email IS NOT NULL
            ORDER BY dias_sin_contacto DESC
        ";

        $stmt = $this->db->prepare($sql);
        if (!$this->esAdmin) {
            $stmt->bind_param('i', $this->usuarioId);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Cotizaciones en estado activo que llevan >= $dias sin ningún seguimiento.
     * Respeta el rol: admin ve todas, vendedor solo las suyas.
     *
     * @return array[]
     */
    public function getCotizacionesSinSeguimiento(int $dias = 5): array
    {
        $sql = '
            SELECT
                c.id,
                c.numero_cotizacion,
                c.cliente_nombre,
                c.empresa,
                c.atencion,
                c.total,
                c.moneda,
                c.estado,
                COALESCE(MAX(s.fecha_contacto), c.created_at) AS ultimo_contacto,
                DATEDIFF(NOW(), COALESCE(MAX(s.fecha_contacto), c.created_at)) AS dias_sin_contacto
            FROM cotizaciones c
            LEFT JOIN seguimiento_cotizacion s ON s.cotizacion_id = c.id
            WHERE COALESCE(NULLIF(TRIM(c.estado), \'\'), \'pendiente\')
                  IN (\'pendiente\', \'activa\')
            %s
            GROUP BY c.id
            HAVING dias_sin_contacto >= ?
            ORDER BY dias_sin_contacto DESC
            LIMIT 10
        ';

        if ($this->esAdmin) {
            $stmt = $this->db->prepare(sprintf($sql, ''));
            $stmt->bind_param('i', $dias);
        } else {
            $stmt = $this->db->prepare(sprintf($sql, 'AND c.usuario_id = ?'));
            $stmt->bind_param('ii', $this->usuarioId, $dias);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Cuenta cotizaciones activas sin seguimiento en N días.
     * Útil para el KPI del dashboard.
     */
    public function contarSinSeguimiento(int $dias = 5): int
    {
        return count($this->getCotizacionesSinSeguimiento($dias));
    }

    /**
     * Verifica que la cotización exista y que el usuario tenga permiso de acceso.
     */
    public function cotizacionAccesible(int $cotizacionId): bool
    {
        if ($this->esAdmin) {
            $stmt = $this->db->prepare('SELECT id FROM cotizaciones WHERE id = ?');
            $stmt->bind_param('i', $cotizacionId);
        } else {
            $stmt = $this->db->prepare(
                'SELECT id FROM cotizaciones WHERE id = ? AND usuario_id = ?'
            );
            $stmt->bind_param('ii', $cotizacionId, $this->usuarioId);
        }
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    /**
     * Datos resumidos de una cotización para mostrar en el encabezado del detalle.
     */
    public function getCotizacionResumen(int $id): array
    {
        $stmt = $this->db->prepare('
            SELECT
                c.id, c.numero_cotizacion, c.cliente_nombre, c.empresa,
                c.atencion, c.cliente_email, c.cliente_telefono,
                c.total, c.moneda, c.estado,
                c.fecha, c.vigencia_dias, c.created_at,
                c.email_token,
                c.email_opened_at,
                c.email_open_count,
                u.nombre AS vendedor_nombre
            FROM cotizaciones c
            LEFT JOIN usuarios u ON u.id = c.usuario_id
            WHERE c.id = ?
        ');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: [];
    }
}