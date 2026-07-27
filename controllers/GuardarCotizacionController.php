<?php

/**
 * GuardarCotizacionController — Orquesta el guardado de cotizaciones.
 *
 * Responsabilidades:
 *  - Verificar autenticación.
 *  - Leer y validar el JSON del body.
 *  - Sanitizar los items recibidos.
 *  - Calcular subtotal, IVA y total.
 *  - Despachar a actualizar() o crear() según el ID recibido.
 *  - Registrar auditoría y notificaciones.
 *  - Responder JSON con el resultado.
 *
 * No contiene HTML. No hace echo de HTML. Solo lógica y echo JSON.
 */
class GuardarCotizacionController
{
    /** Estados válidos de una cotización. */
    private const ESTADOS_VALIDOS = ['pendiente', 'aprobada', 'rechazada'];

    /** Estados que bloquean la edición para trabajadores. */
    private const ESTADOS_BLOQUEADOS = ['aprobada', 'rechazada'];

    private GuardarCotizacionModel $model;
    private Auth                   $auth;

    public function __construct(GuardarCotizacionModel $model, Auth $auth)
    {
        $this->model = $model;
        $this->auth  = $auth;
    }

    /**
     * Punto de entrada. Lee el JSON, valida, y despacha a actualizar o crear.
     */
    public function handle(): void
    {
        // 1. Autenticación
        if (!$this->auth->estaAutenticado()) {
            http_response_code(401);
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        // 2. Leer y validar body JSON
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!$data || !isset($data['items']) || !is_array($data['items'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos inválidos', 'raw' => substr($raw, 0, 200)]);
            return;
        }

        // 2a. Validación CSRF: sin este token, un sitio externo podría crear o
        // editar cotizaciones aprovechando solo la cookie de sesión de la víctima.
        if (!csrf_validar($data['csrf_token'] ?? null)) {
            http_response_code(403);
            echo json_encode(['error' => 'Sesión inválida o expirada. Recarga la página e intenta de nuevo.']);
            return;
        }

        // 2b. Validación de campos obligatorios (defensa en profundidad:
        // el frontend ya valida esto, pero el endpoint no debe confiar en el cliente)
        $errorValidacion = $this->validarCamposObligatorios($data);
        if ($errorValidacion !== null) {
            http_response_code(422);
            echo json_encode(['error' => $errorValidacion]);
            return;
        }

        // 3. Sanitizar items y calcular totales
        $items   = $this->sanitizarItems($data['items']);
        $ivaPct  = min(100.0, max(0.0, (float)($data['iva_pct'] ?? 16)));
        $totales = $this->calcularTotales($items, !empty($data['aplica_iva']), $ivaPct);

        // 4. Campos del formulario
        $campos = $this->extraerCampos($data, $items, $totales);

        // 4b. Imágenes de firma/sello (opcionales, solo si el usuario subió una nueva)
        $usuarioId = $this->auth->usuarioId();
        if (!empty($data['firma_base64'])) {
            $ruta = guardar_imagen_firma($data['firma_base64'], 'firma', $usuarioId);
            if ($ruta) $campos['firma_path'] = $ruta;
        }
        if (!empty($data['sello_base64'])) {
            $ruta = guardar_imagen_firma($data['sello_base64'], 'sello', $usuarioId);
            if ($ruta) $campos['sello_path'] = $ruta;
        }

        // 5. Despachar
        $id = intval($data['id'] ?? 0);
        if ($id > 0) {
            $this->actualizar($id, $campos, $data);
        } else {
            $this->crear($campos, $data);
        }
    }

    // ── Actualizar ────────────────────────────────────────────────────────────

    private function actualizar(int $id, array $campos, array $data): void
    {
        $cotActual = $this->model->getCotizacionActual($id);
        if (!$cotActual) {
            echo json_encode(['error' => 'Cotización no encontrada']);
            return;
        }

        $estadoPrev = normalizar_estado($cotActual['estado']);
        $duenoId    = intval($cotActual['usuario_id']);
        $numeroCot  = $cotActual['numero_cotizacion'];
        $esAdmin    = $this->auth->esAdmin();
        $usuarioId  = $this->auth->usuarioId();

        // Validar permisos para trabajador
        if (!$esAdmin) {
            if ($duenoId !== $usuarioId) {
                echo json_encode(['error' => 'No tienes permiso para modificar esta cotización']);
                return;
            }
            if (in_array($estadoPrev, self::ESTADOS_BLOQUEADOS)) {
                echo json_encode(['error' => 'No puedes modificar una cotización ya aprobada o rechazada']);
                return;
            }
        }

        // Determinar estado final y notificación (solo admin)
        $nuevoEstado         = $estadoPrev;
        $notificacionEnviada = false;

        if ($esAdmin) {
            [$nuevoEstado, $notificacionEnviada] = $this->gestionarEstadoAdmin(
                $id, $duenoId, $numeroCot, $estadoPrev, $data
            );
        }

        $campos['estado'] = $nuevoEstado;

        $ok = $this->model->actualizarCotizacion($id, $campos, $esAdmin, $usuarioId);
        if (!$ok) {
            echo json_encode(['error' => 'Error al actualizar la cotización']);
            return;
        }

        // Registro automático de seguimiento si el estado cambió
        if ($estadoPrev !== $nuevoEstado) {
            $etiquetas = [
                'aprobada'  => "✅ Cliente aprobó la cotización {$numeroCot}.",
                'rechazada' => "❌ Cotización {$numeroCot} marcada como rechazada.",
                'pendiente' => "🔄 Cotización {$numeroCot} regresada a pendiente para revisión.",
            ];
            $msgAuto = $etiquetas[$nuevoEstado]
                ?? "Estado cambiado de «{$estadoPrev}» a «{$nuevoEstado}» en {$numeroCot}.";
            $this->registrarSeguimientoAuto($id, $usuarioId, 'nota', $msgAuto);
        }

        // Auditoría
        $accionAudit = ($estadoPrev !== $nuevoEstado) ? 'cambio_estado' : 'editar';
        audit_cotizacion(
            $GLOBALS['conexion'],
            $usuarioId,
            $this->auth->usuarioNombre(),
            $accionAudit,
            $id,
            $numeroCot,
            ['cliente' => $campos['cliente_nombre'], 'total' => $campos['total'],
             'estado_anterior' => $estadoPrev, 'estado_nuevo' => $nuevoEstado]
        );

        // Notificar admins si editó un trabajador
        if (!$esAdmin) {
            $msg = "{$this->auth->usuarioNombre()} editó la cotización $numeroCot"
                 . " (Cliente: {$campos['cliente_nombre']}, Total: $" . number_format($campos['total'], 2) . ")";
            notificar_admins($GLOBALS['conexion'], $usuarioId, $id, $msg);
        }

        echo json_encode([
            'ok'                   => true,
            'id'                   => $id,
            'total'                => $campos['total'],
            'estado'               => $nuevoEstado,
            'notificacion_enviada' => $notificacionEnviada,
        ]);
    }

    // ── Crear ─────────────────────────────────────────────────────────────────

    private function crear(array $campos, array $data): void
    {
        $esAdmin   = $this->auth->esAdmin();
        $usuarioId = $this->auth->usuarioId();

        $numero = $this->model->generarNumeroCotizacion();

        // Estado inicial según rol
        if ($esAdmin) {
            $estadoRaw = $data['estado'] ?? null;
            $campos['estado'] = ($estadoRaw && in_array($estadoRaw, self::ESTADOS_VALIDOS))
                ? $estadoRaw
                : 'aprobada';
        } else {
            $campos['estado'] = 'pendiente';
        }

        $nuevoId = $this->model->insertarCotizacion($usuarioId, $numero, $campos);

        // Registro automático de seguimiento: cotización creada
        $this->registrarSeguimientoAuto(
            $nuevoId,
            $usuarioId,
            'nota',
            "Cotización {$numero} creada para el cliente «{$campos['cliente_nombre']}»."
        );

        // Auditoría de creación
        audit_cotizacion(
            $GLOBALS['conexion'],
            $usuarioId,
            $this->auth->usuarioNombre(),
            'crear',
            $nuevoId,
            $numero,
            ['cliente' => $campos['cliente_nombre'], 'total' => $campos['total'], 'estado' => $campos['estado']]
        );

        // Notificar admins
        $msg = "{$this->auth->usuarioNombre()} creó la cotización $numero"
             . " (Cliente: {$campos['cliente_nombre']}, Total: $" . number_format($campos['total'], 2) . ")";
        notificar_admins($GLOBALS['conexion'], $usuarioId, $nuevoId, $msg);

        echo json_encode([
            'ok'     => true,
            'id'     => $nuevoId,
            'numero' => $numero,
            'total'  => $campos['total'],
            'estado' => $campos['estado'],
        ]);
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    /**
     * Determina el estado final cuando el admin guarda y maneja la notificación al dueño.
     *
     * @return array{0: string, 1: bool}  [estado_final, notificacion_enviada]
     */
    private function gestionarEstadoAdmin(
        int    $cotId,
        int    $duenoId,
        string $numeroCot,
        string $estadoPrev,
        array  $data
    ): array {
        $mensajeAdmin    = trim($data['mensaje_notificacion'] ?? '');
        $marcarPendiente = !empty($data['marcar_pendiente']);
        $estadoRecibido  = $data['estado'] ?? null;
        $usuarioId       = $this->auth->usuarioId();

        $nuevoEstado = ($estadoRecibido !== null)
            ? normalizar_estado($estadoRecibido)
            : $estadoPrev;

        $esCotizacionAjena = ($duenoId > 0 && $duenoId !== $usuarioId);
        $notificacionEnviada = false;

        if ($esCotizacionAjena && ($mensajeAdmin !== '' || $marcarPendiente)) {
            $nuevoEstado = 'pendiente';
        }

        if ($esCotizacionAjena) {
            $msg = "El administrador editó tu cotización {$numeroCot}.";
            if ($mensajeAdmin !== '') $msg .= ' Mensaje: ' . $mensajeAdmin;
            if ($nuevoEstado === 'pendiente' && $estadoPrev !== 'pendiente')
                $msg .= ' La cotización pasó a estado "Pendiente" para que la revises.';

            $notificacionEnviada = $this->model->insertarNotificacion(
                $duenoId, $cotId, $msg, $usuarioId
            );
        }

        return [$nuevoEstado, $notificacionEnviada];
    }

    /**
     * Valida los campos mínimos indispensables de una cotización.
     * Devuelve un mensaje de error legible, o null si todo está correcto.
     */
    private function validarCamposObligatorios(array $data): ?string
    {
        $atencion = trim($data['atencion'] ?? $data['cliente_nombre'] ?? '');
        if ($atencion === '') {
            return 'Falta el nombre de contacto del cliente (Atención).';
        }

        $empresa = trim($data['empresa'] ?? '');
        if ($empresa === '') {
            return 'Falta la empresa del cliente.';
        }

        $email = trim($data['cliente_email'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'El correo proporcionado no es válido.';
        }

        $items = array_filter($data['items'], function ($item) {
            $cant   = (float)($item['cant']    ?? 0);
            $sku    = trim($item['sku']         ?? '');
            $desc   = trim($item['descripcion'] ?? '');
            $precio = (float)($item['precio']  ?? 0);
            return !($cant == 0 && $sku === '' && $desc === '' && $precio == 0);
        });
        if (count($items) === 0) {
            return 'Agrega al menos un ítem (producto o servicio) antes de guardar.';
        }

        return null;
    }

    /**
     * Sanitiza y filtra la lista de items del request.
     * Descarta filas completamente vacías.
     *
     * @return array[]
     */
    private function sanitizarItems(array $rawItems): array
    {
        $limpios = [];
        foreach ($rawItems as $item) {
            $cant      = max(0.0, (float)($item['cant']     ?? $item['cantidad']    ?? 0));
            $sku       = trim($item['sku']             ?? $item['codigo']      ?? '');
            $unidad    = trim($item['unidad']          ?? 'PZA');
            $desc      = trim($item['descripcion']     ?? $item['description'] ?? '');
            $precio    = max(0.0, (float)($item['precio']  ?? $item['p_unitario']  ?? 0));
            $descPct   = min(100.0, max(0.0, (float)($item['descuento'] ?? 0)));
            $extendido = $cant * $precio;
            $subtotal  = $extendido * (1 - $descPct / 100);

            if ($cant == 0 && $sku === '' && $desc === '' && $precio == 0) continue;

            $limpios[] = [
                'cant'        => $cant,
                'sku'         => $sku,
                'unidad'      => $unidad,
                'descripcion' => $desc,
                'precio'      => $precio,
                'extendido'   => round($extendido, 2),
                'descuento'   => $descPct,
                'subtotal'    => round($subtotal, 2),
            ];
        }
        return $limpios;
    }

    /**
     * Calcula subtotal, IVA y total a partir de los items sanitizados.
     * El porcentaje de IVA es el que el vendedor configuró en pantalla
     * (input #iva-pct), no un valor fijo — cotizador/lista_precios pueden
     * requerir tasas distintas a 16% según el cliente.
     *
     * @return array{subtotal:float, iva:float, total:float, iva_pct:float, aplica_iva:bool}
     */
    private function calcularTotales(array $items, bool $aplicaIva, float $ivaPct): array
    {
        $subtotal = array_sum(array_column($items, 'subtotal'));
        $iva      = $aplicaIva ? round($subtotal * ($ivaPct / 100), 2) : 0.0;
        return [
            'subtotal'   => round($subtotal, 2),
            'iva'        => $iva,
            'total'      => round($subtotal + $iva, 2),
            'iva_pct'    => $ivaPct,
            'aplica_iva' => $aplicaIva,
        ];
    }

    /**
     * Extrae y normaliza todos los campos del formulario desde el payload.
     * Produce el array $campos listo para el model.
     */
    private function extraerCampos(array $data, array $items, array $totales): array
    {
        $tipoCambioRaw = (float)($data['tipo_cambio'] ?? 1.0);
        $tipoCambio    = $tipoCambioRaw > 0 ? $tipoCambioRaw : 1.0;

        return [
            'fecha'              => $data['fecha']              ?? date('Y-m-d'),
            'cliente_nombre'     => $data['cliente_nombre']     ?? '',
            'cliente_telefono'   => $data['cliente_telefono']   ?? '',
            'cliente_email'      => $data['cliente_email']      ?? '',
            'atencion'           => $data['atencion']           ?? '',
            'puesto'             => $data['puesto']             ?? '',
            'empresa'            => $data['empresa']            ?? '',
            'telefono_o'         => $data['telefono_o']         ?? '',
            'telefono_m'         => $data['telefono_m']         ?? '',
            'vigencia_dias'      => $data['vigencia_dias']      ?? '30',
            'moneda'             => $data['moneda_code']        ?? 'USD',
            'moneda_code'        => $data['moneda_code']        ?? 'USD',
            'tipo_cambio'        => $tipoCambio,
            'tiempo_entrega'     => $data['tiempo_entrega']     ?? '',
            'condiciones_pago'   => $data['condiciones_pago']   ?? '',
            'vigencia_servicios' => $data['vigencia_servicios'] ?? '',
            'lugar_entrega'      => $data['lugar_entrega']      ?? '',
            'firma_nombre'       => $data['firma_nombre']       ?? '',
            'firma_puesto'       => $data['firma_puesto']       ?? '',
            'firma_telefono'     => $data['firma_telefono']     ?? '',
            'items_json'         => json_encode($items, JSON_UNESCAPED_UNICODE),
            'subtotal'           => $totales['subtotal'],
            'iva'                => $totales['iva'],
            'iva_porcentaje'     => $totales['iva_pct'],
            'aplica_iva'         => $totales['aplica_iva'] ? 1 : 0,
            'total'              => $totales['total'],
            'estado'             => 'pendiente', // se sobreescribe después según rol
        ];
    }

    /**
     * Inserta un seguimiento automático sin exponer el SeguimientoModel
     * como dependencia completa del controller.
     * Solo se llama internamente desde crear() y actualizar().
     */
    private function registrarSeguimientoAuto(
        int    $cotizacionId,
        int    $usuarioId,
        string $tipo,
        string $descripcion
    ): void {
        $fecha = date('Y-m-d H:i:s');
        $db    = $GLOBALS['conexion'];
        $stmt  = $db->prepare('
            INSERT INTO seguimiento_cotizacion
                (cotizacion_id, usuario_id, tipo, descripcion, fecha_contacto)
            VALUES (?, ?, ?, ?, ?)
        ');
        if ($stmt) {
            $stmt->bind_param('iisss', $cotizacionId, $usuarioId, $tipo, $descripcion, $fecha);
            $stmt->execute();
        }
    }
}