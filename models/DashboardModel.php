<?php

/**
 * DashboardModel — Todas las consultas SQL del dashboard.
 * ACTUALIZADO: añade getCalendarioMes(), getBarrasSemana() y getSvgWave().
 */
class DashboardModel
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

    private function queryByRol(string $sqlAdmin, string $sqlVendedor): mysqli_result
    {
        if ($this->esAdmin) {
            $stmt = $this->db->prepare($sqlAdmin);
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare($sqlVendedor);
            $stmt->bind_param('i', $this->usuarioId);
            $stmt->execute();
        }
        return $stmt->get_result();
    }

    // ── Métodos públicos ─────────────────────────────────────────────────────

    public function getNotificaciones(): array
    {
        $stmt = $this->db->prepare("
            SELECT n.id, n.cotizacion_id, n.mensaje, n.leido, n.created_at,
                   c.numero_cotizacion
            FROM notificaciones n
            LEFT JOIN cotizaciones c ON c.id = n.cotizacion_id
            WHERE n.usuario_id = ?
            ORDER BY n.created_at DESC
            LIMIT 15
        ");
        $stmt->bind_param('i', $this->usuarioId);
        $stmt->execute();
        $list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $noLeidas = 0;
        foreach ($list as $n) {
            if (!$n['leido']) $noLeidas++;
        }

        return ['list' => $list, 'noLeidas' => $noLeidas];
    }

    public function getCotizaciones(): array
    {
        $result = $this->queryByRol(
            "SELECT * FROM cotizaciones ORDER BY created_at DESC LIMIT 20",
            "SELECT * FROM cotizaciones WHERE usuario_id = ? ORDER BY created_at DESC LIMIT 20"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getStats(): array
    {
        $result = $this->queryByRol(
            "SELECT COUNT(*) AS total, SUM(total) AS suma FROM cotizaciones",
            "SELECT COUNT(*) AS total, SUM(total) AS suma FROM cotizaciones WHERE usuario_id = ?"
        );
        $row = $result->fetch_assoc();
        return [
            'total' => intval($row['total'] ?? 0),
            'suma'  => floatval($row['suma']  ?? 0),
        ];
    }

    public function getCotizacionesMes(): int
    {
        $result = $this->queryByRol(
            "SELECT COUNT(*) AS n FROM cotizaciones
             WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())",
            "SELECT COUNT(*) AS n FROM cotizaciones
             WHERE usuario_id = ?
               AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())"
        );
        return intval($result->fetch_assoc()['n'] ?? 0);
    }

    public function getEstados(): array
    {
        $result = $this->queryByRol(
            "SELECT COALESCE(NULLIF(TRIM(estado),''), 'pendiente') AS estado_norm,
                    COUNT(*) AS n, SUM(total) AS suma
             FROM cotizaciones GROUP BY estado_norm",
            "SELECT COALESCE(NULLIF(TRIM(estado),''), 'pendiente') AS estado_norm,
                    COUNT(*) AS n, SUM(total) AS suma
             FROM cotizaciones WHERE usuario_id = ? GROUP BY estado_norm"
        );

        $count = ['pendiente' => 0, 'aprobada' => 0, 'rechazada' => 0];
        $suma  = ['pendiente' => 0.0, 'aprobada' => 0.0, 'rechazada' => 0.0];

        foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
            $e = strtolower($row['estado_norm']);
            if (in_array($e, ['aprobada', 'activa', 'aceptada']))  $k = 'aprobada';
            elseif (in_array($e, ['rechazada', 'cancelada']))      $k = 'rechazada';
            else                                                    $k = 'pendiente';
            $count[$k] += intval($row['n']);
            $suma[$k]  += floatval($row['suma']);
        }

        $total = array_sum($count);
        $tasa  = $total > 0 ? round(($count['aprobada'] / $total) * 100, 1) : 0.0;

        return ['count' => $count, 'suma' => $suma, 'tasaConversion' => $tasa];
    }

    public function getMonedas(): array
    {
        $result = $this->queryByRol(
            "SELECT UPPER(COALESCE(NULLIF(TRIM(moneda),''), 'USD')) AS moneda_norm,
                    SUM(total) AS suma
             FROM cotizaciones GROUP BY moneda_norm",
            "SELECT UPPER(COALESCE(NULLIF(TRIM(moneda),''), 'USD')) AS moneda_norm,
                    SUM(total) AS suma
             FROM cotizaciones WHERE usuario_id = ? GROUP BY moneda_norm"
        );

        $data = ['USD' => 0.0, 'MXN' => 0.0];
        foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
            $m = $row['moneda_norm'];
            if (isset($data[$m])) {
                $data[$m] = floatval($row['suma']);
            }
        }
        return $data;
    }

    public function getProximasVencer(): array
    {
        $campos = "id, numero_cotizacion, empresa, atencion, cliente_nombre,
                   fecha, vigencia_dias, total, moneda, estado";
        $filtroFecha = "
            AND vigencia_dias > 0
            AND DATE_ADD(COALESCE(fecha, DATE(created_at)), INTERVAL CAST(vigencia_dias AS UNSIGNED) DAY) >= CURDATE()
            AND DATE_ADD(COALESCE(fecha, DATE(created_at)), INTERVAL CAST(vigencia_dias AS UNSIGNED) DAY) <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ORDER BY DATE_ADD(COALESCE(fecha, DATE(created_at)), INTERVAL CAST(vigencia_dias AS UNSIGNED) DAY) ASC
            LIMIT 5";
        $filtroEstado = "COALESCE(NULLIF(TRIM(estado),''), 'pendiente')
                         NOT IN ('aprobada','activa','aceptada','rechazada','cancelada')";

        $result = $this->queryByRol(
            "SELECT $campos FROM cotizaciones WHERE $filtroEstado $filtroFecha",
            "SELECT $campos FROM cotizaciones WHERE usuario_id = ? AND $filtroEstado $filtroFecha"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getTopProductos(): array
    {
        // 1. Obtener los SKUs de cotizaciones (según rol)
        $result = $this->queryByRol(
            "SELECT items FROM cotizaciones WHERE items IS NOT NULL AND items != '[]'",
            "SELECT items FROM cotizaciones WHERE usuario_id = ? AND items IS NOT NULL AND items != '[]'"
        );

        // Acumular frecuencia por SKU
        $porSku = [];
        foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
            $items = json_decode($row['items'], true) ?? [];
            foreach ($items as $it) {
                $sku = trim($it['sku'] ?? '');
                if ($sku === '') continue;
                if (!isset($porSku[$sku])) {
                    $porSku[$sku] = ['count' => 0, 'total' => 0.0];
                }
                $porSku[$sku]['count'] += intval($it['cant'] ?? 1);
                $porSku[$sku]['total'] += floatval($it['subtotal'] ?? 0);
            }
        }

        if (empty($porSku)) return [];

        // 2. Resolver categoría de cada SKU via lista_precios (versión activa)
        $skus        = array_keys($porSku);
        $placeholders = implode(',', array_fill(0, count($skus), '?'));
        $types        = str_repeat('s', count($skus));

        $stmt = $this->db->prepare("
            SELECT lp.codigo, lp.categoria
            FROM lista_precios lp
            INNER JOIN versiones_lista vl ON vl.id = lp.version_id AND vl.activa = 1
            WHERE lp.codigo IN ($placeholders)
              AND lp.activo = 1
        ");
        $stmt->bind_param($types, ...$skus);
        $stmt->execute();
        $mapa = [];   // sku => categoria
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
            $mapa[$r['codigo']] = $r['categoria'];
        }

        // 3. Agrupar por categoría
        $freq = [];
        foreach ($porSku as $sku => $data) {
            $cat = $mapa[$sku] ?? 'Sin categoría';
            if (!isset($freq[$cat])) {
                $freq[$cat] = ['count' => 0, 'total' => 0.0];
            }
            $freq[$cat]['count'] += $data['count'];
            $freq[$cat]['total'] += $data['total'];
        }

        uasort($freq, fn($a, $b) => $b['count'] <=> $a['count']);
        return array_slice($freq, 0, 6, true);
    }

    public function getChartMensual(): array
    {
        $result = $this->queryByRol(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mes,
                    COUNT(*) AS total_cotizaciones, SUM(total) AS monto_total
             FROM cotizaciones GROUP BY mes ORDER BY mes DESC LIMIT 6",
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mes,
                    COUNT(*) AS total_cotizaciones, SUM(total) AS monto_total
             FROM cotizaciones WHERE usuario_id = ? GROUP BY mes ORDER BY mes DESC LIMIT 6"
        );

        $labels = [];
        $montos = [];
        $counts = [];

        foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
            $date     = DateTime::createFromFormat('Y-m', $row['mes']);
            $labels[] = $date ? $date->format('M Y') : $row['mes'];
            $montos[] = round(floatval($row['monto_total'] ?? 0), 2);
            $counts[] = intval($row['total_cotizaciones']);
        }

        return [
            'labels' => array_reverse($labels),
            'montos' => array_reverse($montos),
            'counts' => array_reverse($counts),
        ];
    }

    // ── NUEVO: días del mes actual con cotizaciones ──────────────────────────

    /**
     * Devuelve un array [dia => count] para el mes y año actuales.
     * Ejemplo: [3 => 1, 7 => 2, 15 => 1]
     */
    public function getCalendarioMes(): array
    {
        $result = $this->queryByRol(
            "SELECT DAY(COALESCE(fecha, DATE(created_at))) AS dia, COUNT(*) AS n
             FROM cotizaciones
             WHERE MONTH(COALESCE(fecha, DATE(created_at))) = MONTH(NOW())
               AND YEAR(COALESCE(fecha, DATE(created_at)))  = YEAR(NOW())
             GROUP BY dia",
            "SELECT DAY(COALESCE(fecha, DATE(created_at))) AS dia, COUNT(*) AS n
             FROM cotizaciones
             WHERE usuario_id = ?
               AND MONTH(COALESCE(fecha, DATE(created_at))) = MONTH(NOW())
               AND YEAR(COALESCE(fecha, DATE(created_at)))  = YEAR(NOW())
             GROUP BY dia"
        );

        $mapa = [];
        foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
            $mapa[(int)$row['dia']] = (int)$row['n'];
        }
        return $mapa;
    }

    // ── NUEVO: cotizaciones por día de la semana actual ──────────────────────

    /**
     * Devuelve un array de 7 posiciones [0=Lun … 6=Dom]
     * con el conteo de cotizaciones de la semana ISO actual.
     */
    public function getBarrasSemana(): array
    {
        $result = $this->queryByRol(
            "SELECT WEEKDAY(COALESCE(fecha, DATE(created_at))) AS dow, COUNT(*) AS n
             FROM cotizaciones
             WHERE YEARWEEK(COALESCE(fecha, DATE(created_at)), 1) = YEARWEEK(NOW(), 1)
             GROUP BY dow",
            "SELECT WEEKDAY(COALESCE(fecha, DATE(created_at))) AS dow, COUNT(*) AS n
             FROM cotizaciones
             WHERE usuario_id = ?
               AND YEARWEEK(COALESCE(fecha, DATE(created_at)), 1) = YEARWEEK(NOW(), 1)
             GROUP BY dow"
        );

        $barras = array_fill(0, 7, 0);
        foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
            $barras[(int)$row['dow']] = (int)$row['n'];
        }
        return $barras;
    }

    // ── NUEVO: evolución DIARIA desde el 1 de julio + período anterior ───────

    /**
     * Devuelve cotizaciones POR DÍA desde el 1 de julio del año en curso hasta
     * hoy, desglosadas por estado (pendiente / aprobada / rechazada), usando
     * COALESCE(fecha, DATE(created_at)) — la MISMA expresión de fecha que
     * getCalendarioMes() — para que la gráfica coincida con el calendario.
     * La normalización de estados es la misma que getEstados().
     *
     * @return array{labels: string[], counts: int[], montos: float[],
     *               pendientes: int[], aprobadas: int[], rechazadas: int[],
     *               normalized: float[]}
     */
    public function getWaveData(): array
    {
        $inicio = new DateTime(date('Y') . '-07-01');
        $hoy    = new DateTime('today');
        // Si aún no llega julio de este año, usar julio del año pasado
        if ($hoy < $inicio) {
            $inicio->modify('-1 year');
        }
        $nDias = (int)$inicio->diff($hoy)->days + 1; // inclusive de hoy

        $desde = $inicio->format('Y-m-d');
        $hasta = $hoy->format('Y-m-d');

        // Mapa fecha => ['pendiente'=>n, 'aprobada'=>n, 'rechazada'=>n, 'monto'=>x]
        $mapa = $this->conteoDiarioPorEstado($desde, $hasta);

        $labels = [];
        $counts = [];
        $montos = [];
        $pend   = [];
        $aprob  = [];
        $rech   = [];

        for ($i = 0; $i < $nDias; $i++) {
            $fecha = (clone $inicio)->modify("+{$i} days");
            $k     = $fecha->format('Y-m-d');
            $d     = $mapa[$k] ?? ['pendiente' => 0, 'aprobada' => 0, 'rechazada' => 0, 'monto' => 0.0];

            $labels[] = $fecha->format('j M');
            $pend[]   = $d['pendiente'];
            $aprob[]  = $d['aprobada'];
            $rech[]   = $d['rechazada'];
            $counts[] = $d['pendiente'] + $d['aprobada'] + $d['rechazada'];
            $montos[] = $d['monto'];
        }

        $max        = max(max($counts ?: [1]), 1);
        $normalized = array_map(fn($v) => round($v / $max * 100, 1), $counts);

        return [
            'labels'     => $labels,
            'counts'     => $counts,
            'montos'     => $montos,
            'pendientes' => $pend,
            'aprobadas'  => $aprob,
            'rechazadas' => $rech,
            'normalized' => $normalized,
        ];
    }

    /**
     * Conteo de cotizaciones por día y por estado normalizado en un rango
     * [desde, hasta] ('Y-m-d' inclusive), usando COALESCE(fecha, DATE(created_at))
     * igual que el calendario. Respeta el rol (admin ve todo).
     * Normalización de estados idéntica a getEstados():
     *   aprobada/activa/aceptada → aprobada; rechazada/cancelada → rechazada;
     *   cualquier otro (o vacío) → pendiente.
     *
     * @return array<string, array{pendiente:int, aprobada:int, rechazada:int, monto:float}>
     */
    private function conteoDiarioPorEstado(string $desde, string $hasta): array
    {
        if ($this->esAdmin) {
            $stmt = $this->db->prepare(
                "SELECT COALESCE(fecha, DATE(created_at)) AS dia,
                        COALESCE(NULLIF(TRIM(estado),''), 'pendiente') AS estado_norm,
                        COUNT(*) AS n, SUM(total) AS monto
                 FROM cotizaciones
                 WHERE COALESCE(fecha, DATE(created_at)) BETWEEN ? AND ?
                 GROUP BY dia, estado_norm"
            );
            $stmt->bind_param('ss', $desde, $hasta);
        } else {
            $stmt = $this->db->prepare(
                "SELECT COALESCE(fecha, DATE(created_at)) AS dia,
                        COALESCE(NULLIF(TRIM(estado),''), 'pendiente') AS estado_norm,
                        COUNT(*) AS n, SUM(total) AS monto
                 FROM cotizaciones
                 WHERE usuario_id = ?
                   AND COALESCE(fecha, DATE(created_at)) BETWEEN ? AND ?
                 GROUP BY dia, estado_norm"
            );
            $stmt->bind_param('iss', $this->usuarioId, $desde, $hasta);
        }
        $stmt->execute();

        $mapa = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $dia = $row['dia'];
            if (!isset($mapa[$dia])) {
                $mapa[$dia] = ['pendiente' => 0, 'aprobada' => 0, 'rechazada' => 0, 'monto' => 0.0];
            }
            $e = strtolower($row['estado_norm']);
            if (in_array($e, ['aprobada', 'activa', 'aceptada']))  $k = 'aprobada';
            elseif (in_array($e, ['rechazada', 'cancelada']))      $k = 'rechazada';
            else                                                    $k = 'pendiente';

            $mapa[$dia][$k]      += (int)$row['n'];
            $mapa[$dia]['monto'] += (float)($row['monto'] ?? 0);
        }
        return $mapa;
    }

    // ── SEGUIMIENTO: cotizaciones sin contacto en N días ────────────────────

    /**
     * Cotizaciones activas/pendientes sin seguimiento en los últimos $dias días.
     * Admin ve todas; vendedor solo las suyas.
     *
     * @return array[]  [{id, numero_cotizacion, cliente_nombre, empresa, atencion,
     *                    total, moneda, estado, ultimo_contacto, dias_sin_contacto}]
     */
    // ── SEGUIMIENTO CENTRO ────────────────────────────────────────────────────

    /**
     * Cotizaciones activas con su estado de seguimiento para el centro unificado.
     * Incluye datos de correo enviado y días transcurridos.
     *
     * @return array[]
     */
    public function getCotizacionesSeguimiento(string $filtro = 'activas'): array
    {
        $whereEstado = match($filtro) {
            'enviadas'   => "AND s_email.id IS NOT NULL",
            'sin_correo' => "AND s_email.id IS NULL AND c.estado IN ('pendiente','activa')",
            default      => "AND c.estado IN ('pendiente','activa')",
        };

        $whereRol = $this->esAdmin ? '' : 'AND c.usuario_id = ' . (int)$this->usuarioId;

        $sql = "
            SELECT
                c.id,
                c.numero_cotizacion,
                c.cliente_nombre,
                c.empresa,
                c.atencion,
                c.cliente_email,
                c.total,
                c.moneda,
                c.estado,
                c.created_at,
                -- Último seguimiento
                MAX(s.fecha_contacto)  AS ultimo_contacto,
                MAX(s.tipo)            AS ultimo_tipo,
                DATEDIFF(NOW(), COALESCE(MAX(s.fecha_contacto), c.created_at)) AS dias_sin_contacto,
                -- Primer correo enviado
                MIN(CASE WHEN s_email.tipo = 'email' THEN s_email.fecha_contacto END) AS primer_email,
                -- Vendedor
                u.nombre               AS vendedor_nombre
            FROM cotizaciones c
            LEFT JOIN seguimiento_cotizacion s       ON s.cotizacion_id = c.id
            LEFT JOIN seguimiento_cotizacion s_email ON s_email.cotizacion_id = c.id AND s_email.tipo = 'email'
            LEFT JOIN usuarios u                      ON u.id = c.usuario_id
            WHERE 1=1 {$whereEstado} {$whereRol}
            GROUP BY c.id
            ORDER BY
                CASE c.estado WHEN 'activa' THEN 0 WHEN 'pendiente' THEN 1 ELSE 2 END,
                dias_sin_contacto DESC
            LIMIT 50
        ";

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Estadísticas para el widget de barras horizontales del dashboard.
     * Incluye datos de email tracking (email_opened_at) además del seguimiento manual.
     */
    public function getResumenSeguimientoWidget(): array
    {
        $whereRol = $this->esAdmin ? '' : 'AND c.usuario_id = ' . (int)$this->usuarioId;

        $sql = "
            SELECT
                -- Total activas/pendientes
                COUNT(DISTINCT CASE WHEN c.estado IN ('pendiente','activa') THEN c.id END)
                    AS total_activas,

                -- Enviadas por correo: tienen email_token (nuevo) o seguimiento tipo email (legacy)
                COUNT(DISTINCT CASE
                      WHEN (c.email_token IS NOT NULL OR se.cotizacion_id IS NOT NULL)
                           AND c.estado IN ('pendiente','activa') THEN c.id END)
                    AS enviadas,

                -- Correo abierto por el cliente (pixel de rastreo)
                COUNT(DISTINCT CASE
                      WHEN c.email_opened_at IS NOT NULL
                           AND c.estado IN ('pendiente','activa') THEN c.id END)
                    AS abiertos,

                -- Con seguimiento activo en los últimos 3 días
                COUNT(DISTINCT CASE
                      WHEN DATEDIFF(NOW(), COALESCE(su.ultimo, c.created_at)) <= 3
                           AND c.estado IN ('pendiente','activa') THEN c.id END)
                    AS con_seguimiento,

                -- Sin ningún contacto en más de 5 días (urgente)
                COUNT(DISTINCT CASE
                      WHEN DATEDIFF(NOW(), COALESCE(su.ultimo, c.created_at)) > 5
                           AND c.estado IN ('pendiente','activa') THEN c.id END)
                    AS sin_contacto,

                -- Urgentes: más de 7 días sin respuesta
                COUNT(DISTINCT CASE
                      WHEN DATEDIFF(NOW(), COALESCE(su.ultimo, c.created_at)) > 7
                           AND c.estado IN ('pendiente','activa') THEN c.id END)
                    AS urgentes,

                -- Cerradas (aprobadas) este mes
                COUNT(DISTINCT CASE
                      WHEN c.estado = 'aprobada'
                           AND MONTH(c.created_at) = MONTH(NOW())
                           AND YEAR(c.created_at)  = YEAR(NOW()) THEN c.id END)
                    AS cerradas_mes
            FROM cotizaciones c
            LEFT JOIN (
                SELECT cotizacion_id, MAX(fecha_contacto) AS ultimo
                FROM seguimiento_cotizacion GROUP BY cotizacion_id
            ) su ON su.cotizacion_id = c.id
            LEFT JOIN (
                SELECT DISTINCT cotizacion_id
                FROM seguimiento_cotizacion WHERE tipo = 'email'
            ) se ON se.cotizacion_id = c.id
            WHERE 1=1 {$whereRol}
        ";

        $row = $this->db->query($sql)?->fetch_assoc();
        return $row ?: [
            'total_activas'  => 0,
            'enviadas'       => 0,
            'abiertos'       => 0,
            'con_seguimiento'=> 0,
            'sin_contacto'   => 0,
            'urgentes'       => 0,
            'cerradas_mes'   => 0,
        ];
    }

    public function eliminarCotizacion(int $id): bool
    {
        if ($this->esAdmin) {
            $stmt = $this->db->prepare("DELETE FROM cotizaciones WHERE id = ?");
            $stmt->bind_param('i', $id);
        } else {
            $stmt = $this->db->prepare("DELETE FROM cotizaciones WHERE id = ? AND usuario_id = ?");
            $stmt->bind_param('ii', $id, $this->usuarioId);
        }
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function getCotizacionParaAuditoria(int $id): ?array
    {
        $row = $this->db->query(
            "SELECT numero_cotizacion, cliente_nombre, total FROM cotizaciones WHERE id = $id"
        )->fetch_assoc();
        return $row ?: null;
    }
}