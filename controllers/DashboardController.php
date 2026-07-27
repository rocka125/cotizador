<?php

/**
 * DashboardController — Orquesta la lógica del dashboard.
 * ACTUALIZADO: expone calendarioMes, barrasSemana y waveData.
 */
class DashboardController
{
    public array  $notificaciones   = [];
    public int    $notifNoLeidas    = 0;
    public array  $cotizaciones     = [];
    public array  $stats            = ['total' => 0, 'suma' => 0.0];
    public int    $cotizacionesMes  = 0;
    public array  $estadosCount     = ['pendiente' => 0, 'aprobada' => 0, 'rechazada' => 0];
    public array  $estadosSuma      = ['pendiente' => 0.0, 'aprobada' => 0.0, 'rechazada' => 0.0];
    public float  $tasaConversion   = 0.0;
    public float  $totalUsd         = 0.0;
    public float  $totalMxn         = 0.0;
    public array  $proximasVencer      = [];
    public array  $seguimientoAlertas  = [];  // backward compat — se mantiene vacío
    public int    $diasSinSeguimiento  = 5;
    public array  $resumenSeguimiento  = [];  // para el widget de anillos
    public array  $topProductos     = [];
    public array  $chartLabels      = [];
    public array  $chartMontos      = [];
    public array  $chartCounts      = [];
    public bool   $showSuccessToast = false;

    // ── NUEVOS ───────────────────────────────────────────────────────────────
    /** [dia => count]  — cotizaciones del mes por día */
    public array  $calendarioMes    = [];
    /** [0..6] — cotizaciones de la semana actual por día (Lun=0) */
    public array  $barrasSemana     = [0,0,0,0,0,0,0];
    /** Wave SVG: labels, counts, montos, normalized */
    public array  $waveData         = [];

    private DashboardModel $model;
    private Auth           $auth;

    public function __construct(DashboardModel $model, Auth $auth)
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
        require_once __DIR__ . '/../core/auditoria_helper.php';
        header('Content-Type: application/json');

        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['ok' => false, 'error' => 'ID inválido']);
            return;
        }

        $datosAuditoria = $this->model->getCotizacionParaAuditoria($id);
        $numCot         = $datosAuditoria['numero_cotizacion'] ?? '';
        $ok             = $this->model->eliminarCotizacion($id);

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

        echo json_encode(['ok' => $ok]);
    }

    private function loadViewData(): void
    {
        $notif              = $this->model->getNotificaciones();
        $this->notificaciones = $notif['list'];
        $this->notifNoLeidas  = $notif['noLeidas'];

        $this->cotizaciones   = $this->model->getCotizaciones();
        $this->stats          = $this->model->getStats();
        $this->cotizacionesMes= $this->model->getCotizacionesMes();

        $estados              = $this->model->getEstados();
        $this->estadosCount   = $estados['count'];
        $this->estadosSuma    = $estados['suma'];
        $this->tasaConversion = $estados['tasaConversion'];

        $monedas        = $this->model->getMonedas();
        $this->totalUsd = $monedas['USD'];
        $this->totalMxn = $monedas['MXN'];

        $this->proximasVencer    = $this->model->getProximasVencer();
        $this->resumenSeguimiento = $this->model->getResumenSeguimientoWidget();
        $this->seguimientoAlertas = []; // ya no se usa en dashboard
        $this->topProductos   = $this->model->getTopProductos();

        $chart               = $this->model->getChartMensual();
        $this->chartLabels   = $chart['labels'];
        $this->chartMontos   = $chart['montos'];
        $this->chartCounts   = $chart['counts'];

        // ── NUEVOS ──────────────────────────────────────────────────────────
        $this->calendarioMes = $this->model->getCalendarioMes();
        $this->barrasSemana  = $this->model->getBarrasSemana();
        $this->waveData      = $this->model->getWaveData();

        $this->showSuccessToast = isset($_GET['success']);
    }
}