<?php

/**
 * SeguimientoListaController — Orquesta la pantalla de seguimiento.
 *
 * Modos:
 *  - Sin ?id  → muestra la lista de cotizaciones con estado de seguimiento.
 *  - Con ?id  → muestra el historial completo de una cotización.
 *
 * Responde JSON si recibe POST (registro manual de contacto).
 */
class SeguimientoListaController
{
    // Datos para la vista
    public string $modo             = 'lista';   // 'lista' | 'detalle'
    public array  $alertas          = [];        // cotizaciones sin seguimiento
    public array  $historial        = [];        // seguimientos de una cotización
    public array  $cotizacion       = [];        // datos de la cotización activa
    public int    $cotizacionId     = 0;
    public int    $diasUmbral       = 5;
    public string $busqueda         = '';
    public int    $totalAlertas     = 0;

    private SeguimientoModel $model;
    private Auth             $auth;

    public function __construct(SeguimientoModel $model, Auth $auth)
    {
        $this->model = $model;
        $this->auth  = $auth;
    }

    public function handle(): void
    {
        // POST → registrar seguimiento manual (responde JSON y sale)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
            exit;
        }

        $id = intval($_GET['id'] ?? 0);

        // La vista (rail + JS) siempre necesita la lista completa de
        // cotizaciones, tenga o no la URL un "?id=" — antes, si la URL
        // traía "?id=" (por ejemplo tras seleccionar un cliente, que
        // actualiza la URL con history.pushState), este método SOLO
        // cargaba el detalle de esa cotización y dejaba $this->alertas
        // vacío. Al recargar la página (F5) en esa URL, la lista entera
        // desaparecía. Por eso ahora siempre se carga la lista.
        $this->cargarLista();

        if ($id > 0) {
            $this->modo         = 'detalle';
            $this->cotizacionId = $id;
            $this->cargarDetalle($id);
        } else {
            $this->modo = 'lista';
        }
    }

    // ── POST: guardar contacto manual ────────────────────────────────────────

    private function handlePost(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->auth->estaAutenticado()) {
            http_response_code(401);
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!$data) {
            // Intentar leer como form-data
            $data = $_POST;
        }

        $cotizacionId    = intval($data['cotizacion_id']    ?? 0);
        $tipo            = trim($data['tipo']               ?? '');
        $descripcion     = trim($data['descripcion']        ?? '');
        $fechaContacto   = trim($data['fecha_contacto']     ?? '');
        $proximoContacto = trim($data['proximo_contacto']   ?? '') ?: null;

        if ($cotizacionId <= 0 || !in_array($tipo, SeguimientoModel::TIPOS_VALIDOS) || strlen($descripcion) < 3) {
            echo json_encode(['error' => 'Datos incompletos o inválidos']);
            return;
        }

        if (!$this->model->cotizacionAccesible($cotizacionId)) {
            http_response_code(403);
            echo json_encode(['error' => 'Sin acceso a esta cotización']);
            return;
        }

        if (!$fechaContacto || !strtotime($fechaContacto)) {
            $fechaContacto = date('Y-m-d H:i:s');
        } elseif (strlen($fechaContacto) === 10) {
            $fechaContacto .= ' ' . date('H:i:s');
        }

        $nuevoId = $this->model->insertar(
            $cotizacionId,
            $tipo,
            $descripcion,
            $fechaContacto,
            $proximoContacto
        );

        if (!$nuevoId) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar']);
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

    // ── Modo lista ───────────────────────────────────────────────────────────

    private function cargarLista(): void
    {
        $filtro           = trim($_GET['filtro'] ?? 'todas');
        $this->busqueda   = trim($_GET['buscar'] ?? '');
        $this->alertas    = $this->model->getCotizacionesSeguimiento($filtro);
        $this->totalAlertas = count($this->alertas);
    }

    // ── Modo detalle ─────────────────────────────────────────────────────────

    private function cargarDetalle(int $id): void
    {
        if (!$this->model->cotizacionAccesible($id)) {
            header('Location: seguimiento.php');
            exit;
        }

        $this->historial  = $this->model->getHistorial($id);
        $this->cotizacion = $this->model->getCotizacionResumen($id);
    }
}