<?php

/**
 * ListaCotizacionesController — Orquesta la lógica del listado de cotizaciones.
 */
class ListaCotizacionesController
{
    public array  $cotizaciones     = [];
    public int    $totalRegistros   = 0;
    public int    $totalPaginas     = 1;
    public int    $paginaActual     = 1;
    public string $busqueda         = '';
    public bool   $showSuccessToast = false;

    public const POR_PAGINA = 15;

    private ListaCotizacionesModel $model;
    private Auth                   $auth;

    public function __construct(ListaCotizacionesModel $model, Auth $auth)
    {
        $this->model = $model;
        $this->auth  = $auth;
    }

    public function handle(): void
    {
        if ($this->esAjaxEliminar()) {
            $this->handleEliminar();
            exit;
        }

        $this->loadViewData();
    }

    private function esAjaxEliminar(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST'
            && ($_POST['action'] ?? '') === 'eliminar';
    }

    private function handleEliminar(): void
    {
        // Header PRIMERO — antes de cualquier require o lógica que pueda fallar
        header('Content-Type: application/json; charset=utf-8');

        // Capturar cualquier error/warning de PHP y convertirlo en JSON limpio
        set_error_handler(function($errno, $errstr) {
            echo json_encode(['ok' => false, 'error' => "PHP [$errno]: $errstr"]);
            exit;
        });

        require_once __DIR__ . '/../core/auditoria_helper.php';

        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            restore_error_handler();
            echo json_encode(['ok' => false, 'error' => 'ID inválido']);
            return;
        }

        $datosAuditoria = $this->model->getCotizacionParaAuditoria($id);
        $numCot         = $datosAuditoria['numero_cotizacion'] ?? '';

        $ok = $this->model->eliminarCotizacion($id);

        // A partir de aquí la fila YA se borró (si $ok=true) — restauramos el
        // manejador de errores normal para que un warning/notice dentro de
        // audit_cotizacion()/notificar_admins() (p. ej. algo en push_helper.php)
        // no aborte la respuesta con "ok:false" cuando el borrado sí funcionó.
        restore_error_handler();

        if ($ok && $datosAuditoria) {
            $db = $GLOBALS['conexion'];
            audit_cotizacion(
                $db,
                $this->auth->usuarioId(),
                $this->auth->usuarioNombre(),
                'eliminar',
                0,
                $numCot,
                [
                    'cliente' => $datosAuditoria['cliente_nombre'] ?? '',
                    'total'   => $datosAuditoria['total'] ?? 0,
                ]
            );

            if (!$this->auth->esAdmin()) {
                notificar_admins(
                    $db,
                    $this->auth->usuarioId(),
                    0,
                    "{$this->auth->usuarioNombre()} eliminó la cotización $numCot"
                );
            }
        }

        echo json_encode(
            $ok
                ? ['ok' => true]
                : ['ok' => false, 'error' => 'No se encontró la cotización o no tienes permiso']
        );
    }

    private function loadViewData(): void
    {
        $this->busqueda = trim($_GET['buscar'] ?? '');

        $this->totalRegistros = $this->model->contarCotizaciones($this->busqueda);
        $this->totalPaginas   = max(1, (int) ceil($this->totalRegistros / self::POR_PAGINA));

        // Clamp: una página fuera de rango (bookmark viejo, o cambiar el
        // filtro de búsqueda a uno con menos resultados) ya no debe caer en
        // un OFFSET vacío que muestre "sin resultados" habiendo resultados.
        $paginaSolicitada    = max(1, intval($_GET['pagina'] ?? 1));
        $this->paginaActual = min($paginaSolicitada, $this->totalPaginas);

        $offset             = ($this->paginaActual - 1) * self::POR_PAGINA;
        $this->cotizaciones = $this->model->getCotizaciones($this->busqueda, self::POR_PAGINA, $offset);
        $this->showSuccessToast = isset($_GET['success']);
    }
}
