<?php

/**
 * CambiarEstadoController — Orquesta el cambio de estado de una cotización.
 *
 * Responsabilidades:
 *  - Verificar que el usuario sea admin.
 *  - Validar los parámetros POST (id y estado).
 *  - Llamar al model para leer, actualizar y notificar.
 *  - Registrar auditoría.
 *  - Responder JSON.
 *
 * No contiene HTML. No hace echo de HTML. Solo lógica y echo JSON.
 */
class CambiarEstadoController
{
    private const ESTADOS_VALIDOS = [
        'pendiente', 'aprobada', 'rechazada',
    ];

    private const ESTADOS_LABEL = [
        'aprobada'  => 'aprobada ✓',
        'rechazada' => 'rechazada ✗',
        'pendiente' => 'regresada a pendiente',
    ];

    private CambiarEstadoModel $model;
    private Auth               $auth;

    public function __construct(CambiarEstadoModel $model, Auth $auth)
    {
        $this->model = $model;
        $this->auth  = $auth;
    }

    /**
     * Punto de entrada: valida, actualiza, audita y responde.
     */
    public function handle(): void
    {
        // 1. Solo admin
        if (!$this->auth->esAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        // 2. Validar parámetros
        $id          = intval($_POST['id']     ?? 0);
        $nuevoEstado = trim($_POST['estado']   ?? '');

        if ($id <= 0 || !in_array($nuevoEstado, self::ESTADOS_VALIDOS, true)) {
            echo json_encode(['error' => 'Parámetros inválidos']);
            return;
        }

        // 3. Leer cotización actual
        $cot = $this->model->getCotizacion($id);
        if (!$cot) {
            echo json_encode(['error' => 'Cotización no encontrada']);
            return;
        }

        $estadoAnterior = normalizar_estado($cot['estado']);
        $numeroCot      = $cot['numero_cotizacion'];
        $duenoId        = intval($cot['usuario_id']);
        $usuarioId      = $this->auth->usuarioId();
        $nombreActor    = $this->auth->usuarioNombre();

        // 4. Actualizar estado
        $ok = $this->model->actualizarEstado($id, $nuevoEstado);
        if (!$ok) {
            echo json_encode(['error' => 'No se pudo actualizar el estado']);
            return;
        }

        // 5. Auditoría
        audit_cotizacion(
            $GLOBALS['conexion'],
            $usuarioId,
            $nombreActor,
            'cambio_estado',
            $id,
            $numeroCot,
            ['estado_anterior' => $estadoAnterior, 'estado_nuevo' => $nuevoEstado]
        );

        // 6. Notificar al dueño (solo si la cotización no es del propio admin)
        if ($duenoId !== $usuarioId) {
            $label = self::ESTADOS_LABEL[$nuevoEstado] ?? $nuevoEstado;
            $msg   = "El administrador cambió el estado de tu cotización $numeroCot a \"$label\".";
            $this->model->notificarDueno($duenoId, $id, $msg, $usuarioId);
        }

        // 7. Notificar a los demás admins
        $msgAdm = "$nombreActor cambió el estado de $numeroCot"
                . " de \"$estadoAnterior\" a \"$nuevoEstado\"";
        notificar_admins($GLOBALS['conexion'], $usuarioId, $id, $msgAdm);

        echo json_encode(['ok' => true, 'estado' => $nuevoEstado]);
    }
}
