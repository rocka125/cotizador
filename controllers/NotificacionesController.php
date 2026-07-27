<?php

/**
 * NotificacionesController — Orquesta el centro de notificaciones del usuario.
 *
 * Responsabilidades:
 *  - Distinguir POST (marcar leída/todas) de GET (listar).
 *  - Validar los parámetros de entrada.
 *  - Llamar al model para leer/actualizar.
 *  - Responder JSON.
 *
 * No contiene HTML. No hace echo de HTML. Solo lógica y echo JSON.
 */
class NotificacionesController
{
    private NotificacionesModel $model;

    public function __construct(NotificacionesModel $model)
    {
        $this->model = $model;
    }

    /**
     * Punto de entrada: enruta según método HTTP y responde JSON.
     */
    public function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
            return;
        }

        $this->handleGet();
    }

    private function handlePost(): void
    {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'marcar_leida') {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['error' => 'Parámetros inválidos']);
                return;
            }
            $this->model->marcarLeida($id);
            echo json_encode(['ok' => true]);
            return;
        }

        if ($accion === 'marcar_todas') {
            $this->model->marcarTodas();
            echo json_encode(['ok' => true]);
            return;
        }

        echo json_encode(['error' => 'Acción no soportada']);
    }

    private function handleGet(): void
    {
        echo json_encode([
            'ok'             => true,
            'notificaciones' => $this->model->listar(),
            'no_leidas'      => $this->model->contarNoLeidas(),
        ]);
    }
}
