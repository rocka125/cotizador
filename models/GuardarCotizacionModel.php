<?php

/**
 * GuardarCotizacionModel — Queries SQL del endpoint de guardado.
 *
 * Responsabilidades:
 *  - Buscar una cotización existente por ID.
 *  - Actualizar una cotización existente (admin o trabajador).
 *  - Insertar una nueva cotización.
 *  - Generar el número de cotización correlativo.
 *  - Insertar notificaciones.
 *
 * No contiene lógica de negocio ni HTTP.
 */
class GuardarCotizacionModel
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    // ── Lectura ───────────────────────────────────────────────────────────────

    /**
     * Datos básicos de una cotización (estado, dueño, número).
     *
     * @return array|null
     */
    public function getCotizacionActual(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT estado, usuario_id, numero_cotizacion FROM cotizaciones WHERE id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Siguiente número correlativo para el año en curso.
     * Formato: COT-YYYY-NNNN
     *
     * Usa un contador atómico en `contador_cotizaciones` (truco estándar de
     * MySQL con LAST_INSERT_ID en un UPSERT) en vez de "SELECT COUNT(*)+1":
     * ese enfoque tenía una condición de carrera — dos guardados simultáneos
     * podían leer el mismo COUNT antes de que ninguno insertara, generando
     * el mismo número de cotización dos veces.
     */
    public function generarNumeroCotizacion(): string
    {
        $anio = (int) date('Y');
        $stmt = $this->db->prepare('
            INSERT INTO contador_cotizaciones (anio, ultimo)
            VALUES (?, LAST_INSERT_ID(1))
            ON DUPLICATE KEY UPDATE ultimo = LAST_INSERT_ID(ultimo + 1)
        ');
        $stmt->bind_param('i', $anio);
        $stmt->execute();
        $n = (int) $this->db->insert_id;
        return "COT-$anio-" . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    // ── Escritura ─────────────────────────────────────────────────────────────

    /**
     * Actualiza una cotización. El admin puede cambiar el estado;
     * el trabajador solo actualiza campos y mantiene su estado.
     *
     * @return bool
     */
    public function actualizarCotizacion(
        int    $id,
        array  $campos,
        bool   $esAdmin,
        int    $usuarioId
    ): bool {
        // firma_path / sello_path solo se sobreescriben si vino una imagen nueva;
        // si no, COALESCE conserva el valor que ya estaba guardado en la fila.
        $firmaPath = $campos['firma_path'] ?? null;
        $selloPath = $campos['sello_path'] ?? null;

        if ($esAdmin) {
            $stmt = $this->db->prepare('
                UPDATE cotizaciones SET
                    fecha = ?, cliente_nombre = ?, cliente_telefono = ?, cliente_email = ?,
                    atencion = ?, puesto = ?, empresa = ?, telefono_o = ?, telefono_m = ?,
                    vigencia_dias = ?, moneda = ?, moneda_code = ?, tiempo_entrega = ?, condiciones_pago = ?,
                    vigencia_servicios = ?, lugar_entrega = ?, items = ?,
                    firma_nombre = ?, firma_puesto = ?, firma_telefono = ?,
                    firma_path = COALESCE(?, firma_path), sello_path = COALESCE(?, sello_path),
                    subtotal = ?, iva = ?, iva_porcentaje = ?, aplica_iva = ?, total = ?, tipo_cambio = ?, estado = ?
                WHERE id = ?
            ');
            if (!$stmt) {
                throw new RuntimeException('Error al preparar UPDATE (admin): ' . $this->db->error);
            }
            $stmt->bind_param(
                str_repeat('s', 22) . 'dddiddsi',
                $campos['fecha'], $campos['cliente_nombre'], $campos['cliente_telefono'],
                $campos['cliente_email'], $campos['atencion'], $campos['puesto'],
                $campos['empresa'], $campos['telefono_o'], $campos['telefono_m'],
                $campos['vigencia_dias'], $campos['moneda'], $campos['moneda_code'], $campos['tiempo_entrega'],
                $campos['condiciones_pago'], $campos['vigencia_servicios'],
                $campos['lugar_entrega'], $campos['items_json'],
                $campos['firma_nombre'], $campos['firma_puesto'], $campos['firma_telefono'],
                $firmaPath, $selloPath,
                $campos['subtotal'], $campos['iva'], $campos['iva_porcentaje'], $campos['aplica_iva'],
                $campos['total'], $campos['tipo_cambio'],
                $campos['estado'],
                $id
            );
        } else {
            // Trabajador: no cambia el estado y debe ser el dueño
            $stmt = $this->db->prepare('
                UPDATE cotizaciones SET
                    fecha = ?, cliente_nombre = ?, cliente_telefono = ?, cliente_email = ?,
                    atencion = ?, puesto = ?, empresa = ?, telefono_o = ?, telefono_m = ?,
                    vigencia_dias = ?, moneda = ?, moneda_code = ?, tiempo_entrega = ?, condiciones_pago = ?,
                    vigencia_servicios = ?, lugar_entrega = ?, items = ?,
                    firma_nombre = ?, firma_puesto = ?, firma_telefono = ?,
                    firma_path = COALESCE(?, firma_path), sello_path = COALESCE(?, sello_path),
                    subtotal = ?, iva = ?, iva_porcentaje = ?, aplica_iva = ?, total = ?, tipo_cambio = ?
                WHERE id = ? AND usuario_id = ?
            ');
            if (!$stmt) {
                throw new RuntimeException('Error al preparar UPDATE (trabajador): ' . $this->db->error);
            }
            $stmt->bind_param(
                str_repeat('s', 22) . 'dddiddii',
                $campos['fecha'], $campos['cliente_nombre'], $campos['cliente_telefono'],
                $campos['cliente_email'], $campos['atencion'], $campos['puesto'],
                $campos['empresa'], $campos['telefono_o'], $campos['telefono_m'],
                $campos['vigencia_dias'], $campos['moneda'], $campos['moneda_code'], $campos['tiempo_entrega'],
                $campos['condiciones_pago'], $campos['vigencia_servicios'],
                $campos['lugar_entrega'], $campos['items_json'],
                $campos['firma_nombre'], $campos['firma_puesto'], $campos['firma_telefono'],
                $firmaPath, $selloPath,
                $campos['subtotal'], $campos['iva'], $campos['iva_porcentaje'], $campos['aplica_iva'],
                $campos['total'], $campos['tipo_cambio'],
                $id, $usuarioId
            );
        }

        // affected_rows puede ser 0 aun sin error si los valores no cambiaron
        // respecto a la fila actual (guardar dos veces sin editar nada);
        // el controller ya verificó que la fila existe (y su dueño) antes de
        // llamar aquí, así que "sin error de SQL" es la señal de éxito correcta.
        $stmt->execute();
        return $stmt->errno === 0;
    }

    /**
     * Inserta una nueva cotización y devuelve el ID generado.
     */
    public function insertarCotizacion(int $usuarioId, string $numero, array $campos): int
    {
        $firmaPath = $campos['firma_path'] ?? null;
        $selloPath = $campos['sello_path'] ?? null;

        $stmt = $this->db->prepare('
            INSERT INTO cotizaciones (
                usuario_id, numero_cotizacion, fecha,
                cliente_nombre, cliente_telefono, cliente_email,
                atencion, puesto, empresa, telefono_o, telefono_m,
                vigencia_dias, moneda, moneda_code, tiempo_entrega, condiciones_pago,
                vigencia_servicios, lugar_entrega,
                firma_nombre, firma_puesto, firma_telefono, firma_path, sello_path,
                items, subtotal, iva, iva_porcentaje, aplica_iva, total, tipo_cambio, estado
            ) VALUES (?,?,?, ?,?,?, ?,?,?,?,?, ?,?,?,?,?, ?,?, ?,?,?,?,?, ?,?,?,?,?,?,?, ?)
        ');
        if (!$stmt) {
            throw new RuntimeException('Error al preparar INSERT: ' . $this->db->error);
        }
        $stmt->bind_param(
            'i' . str_repeat('s', 23) . 'dddidds',
            $usuarioId, $numero, $campos['fecha'],
            $campos['cliente_nombre'], $campos['cliente_telefono'], $campos['cliente_email'],
            $campos['atencion'], $campos['puesto'], $campos['empresa'],
            $campos['telefono_o'], $campos['telefono_m'],
            $campos['vigencia_dias'], $campos['moneda'], $campos['moneda_code'], $campos['tiempo_entrega'],
            $campos['condiciones_pago'], $campos['vigencia_servicios'], $campos['lugar_entrega'],
            $campos['firma_nombre'], $campos['firma_puesto'], $campos['firma_telefono'],
            $firmaPath, $selloPath,
            $campos['items_json'], $campos['subtotal'], $campos['iva'], $campos['iva_porcentaje'],
            $campos['aplica_iva'], $campos['total'], $campos['tipo_cambio'],
            $campos['estado']
        );

        if (!$stmt->execute()) {
            throw new RuntimeException('Error al insertar: ' . $stmt->error);
        }

        return $this->db->insert_id;
    }

    /**
     * Inserta una notificación para un usuario.
     */
    public function insertarNotificacion(
        int    $destinatarioId,
        int    $cotizacionId,
        string $mensaje,
        int    $creadoPor
    ): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO notificaciones (usuario_id, cotizacion_id, mensaje, creado_por) VALUES (?,?,?,?)'
        );
        if (!$stmt) return false;
        $stmt->bind_param('iisi', $destinatarioId, $cotizacionId, $mensaje, $creadoPor);
        return $stmt->execute();
    }
}