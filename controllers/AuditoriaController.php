<?php

/**
 * AuditoriaController — Orquesta la lógica del log de auditoría.
 *
 * Responsabilidades:
 *  - Verificar que el usuario sea admin; redirigir si no.
 *  - Leer los filtros GET (tipo, accion, usuario, pagina).
 *  - Llamar al model y exponer los datos listos para la vista.
 *
 * No contiene HTML. No hace echo de HTML. Solo lógica.
 */
class AuditoriaController
{
    // ── Datos listos para la vista ────────────────────────────────────────────
    public array  $stats          = [];
    public array  $usuarios       = [];

    // Sesiones
    public array  $sesiones       = [];
    public int    $totalSesiones  = 0;
    public int    $totalPagSes    = 1;

    // Cotizaciones
    public array  $auditorias     = [];
    public int    $totalAudits    = 0;
    public int    $totalPagAud    = 1;

    // Filtros activos (para repoblar el formulario y construir URLs de paginación)
    public string $filtroTipo     = 'todo';
    public string $filtroAccion   = '';
    public int    $filtroUsuario  = 0;
    public int    $pagina         = 1;

    private const POR_PAGINA = 30;

    private AuditoriaModel $model;
    private Auth           $auth;

    public function __construct(AuditoriaModel $model, Auth $auth)
    {
        $this->model = $model;
        $this->auth  = $auth;
    }

    /**
     * Punto de entrada principal.
     * Protege la ruta, lee filtros y carga datos para la vista.
     */
    public function handle(): void
    {
        // Solo administradores
        if (!$this->auth->esAdmin()) {
            header('Location: dashboard.php');
            exit;
        }

        $this->leerFiltros();
        $this->loadViewData();
    }

    // ── Privados ──────────────────────────────────────────────────────────────

    private function leerFiltros(): void
    {
        $this->filtroTipo    = $_GET['tipo']    ?? 'todo';
        $this->filtroAccion  = $_GET['accion']  ?? '';
        $this->filtroUsuario = intval($_GET['usuario'] ?? 0);
        $this->pagina        = max(1, intval($_GET['pagina'] ?? 1));
    }

    private function loadViewData(): void
    {
        $offset = ($this->pagina - 1) * self::POR_PAGINA;

        $this->stats    = $this->model->getStats();
        $this->usuarios = $this->model->getUsuarios();

        // Sesiones — solo si la sección visible las incluye
        if ($this->filtroTipo !== 'cotizaciones') {
            $this->totalSesiones = $this->model->contarSesiones($this->filtroUsuario);
            $this->totalPagSes   = (int) ceil($this->totalSesiones / self::POR_PAGINA);
            $this->sesiones      = $this->model->getSesiones($this->filtroUsuario, self::POR_PAGINA, $offset);
        }

        // Cotizaciones — solo si la sección visible las incluye
        if ($this->filtroTipo !== 'sesiones') {
            $this->totalAudits = $this->model->contarAuditorias($this->filtroUsuario, $this->filtroAccion);
            $this->totalPagAud = (int) ceil($this->totalAudits / self::POR_PAGINA);
            $this->auditorias  = $this->model->getAuditorias($this->filtroUsuario, $this->filtroAccion, self::POR_PAGINA, $offset);
        }
    }

    /**
     * Genera la URL de paginación con los filtros activos.
     * Disponible para la vista mediante llamada directa.
     */
    public function urlPagina(int $pagina): string
    {
        return '?tipo='    . urlencode($this->filtroTipo)
             . '&usuario=' . $this->filtroUsuario
             . '&accion='  . urlencode($this->filtroAccion)
             . '&pagina='  . $pagina;
    }
}
