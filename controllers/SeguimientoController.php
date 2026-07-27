<?php

/**
 * SeguimientoController — Orquesta el guardado de registros de seguimiento.
 *
 * Responsabilidades:
 *  - Verificar autenticación.
 *  - Validar los campos recibidos (tipo, descripción, cotización).
 *  - Llamar al model para insertar.
 *  - Responder JSON.
 *
 * No contiene HTML. No hace echo de HTML. Solo lógica y echo JSON.
 */
class SeguimientoController
{
    private SeguimientoModel $model;
    private Auth             $auth;

    public function __construct(SeguimientoModel $model, Auth $auth)
    {
        $this->model = $model;
        $this->auth  = $auth;
    }

    /**
     * Punto de entrada. Lee JSON del body, valida y despacha.
     */
    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // 1. Autenticación
        if (!$this->auth->estaAutenticado()) {
            http_response_code(401);
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        // 2. Leer body JSON
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'JSON inválido']);
            return;
        }

        // 3. Validar campos obligatorios
        $cotizacionId = intval($data['cotizacion_id'] ?? 0);
        $tipo         = trim($data['tipo']            ?? '');
        $descripcion  = trim($data['descripcion']     ?? '');

        if ($cotizacionId <= 0) {
            echo json_encode(['error' => 'ID de cotización inválido']);
            return;
        }

        if (!in_array($tipo, SeguimientoModel::TIPOS_VALIDOS)) {
            echo json_encode(['error' => 'Tipo de seguimiento inválido']);
            return;
        }

        if (strlen($descripcion) < 3) {
            echo json_encode(['error' => 'La descripción es muy corta']);
            return;
        }

        // 4. Verificar acceso a la cotización
        if (!$this->model->cotizacionAccesible($cotizacionId)) {
            http_response_code(403);
            echo json_encode(['error' => 'No tienes acceso a esta cotización']);
            return;
        }

        // 5. Campos opcionales
        $fechaContacto = trim($data['fecha_contacto'] ?? '');
        if (!$fechaContacto || !strtotime($fechaContacto)) {
            $fechaContacto = date('Y-m-d H:i:s');
        } else {
            // Normalizar a datetime si viene solo fecha
            if (strlen($fechaContacto) === 10) {
                $fechaContacto .= ' ' . date('H:i:s');
            }
        }

        $proximoContacto = trim($data['proximo_contacto'] ?? '') ?: null;
        if ($proximoContacto && !strtotime($proximoContacto)) {
            $proximoContacto = null;
        }

        // 6. Insertar
        $nuevoId = $this->model->insertar(
            $cotizacionId,
            $tipo,
            $descripcion,
            $fechaContacto,
            $proximoContacto
        );

        if (!$nuevoId) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar el seguimiento']);
            return;
        }

        echo json_encode([
            'ok'               => true,
            'id'               => $nuevoId,
            'tipo'             => $tipo,
            'descripcion'      => $descripcion,
            'fecha_contacto'   => $fechaContacto,
            'proximo_contacto' => $proximoContacto,
            'usuario_nombre'   => $this->auth->usuarioNombre(),
        ]);
    }
}