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
        $this->stats    = $this->model->getStats();
        $this->usuarios = $this->model->getUsuarios();

        // Primero los conteos (baratos), para poder acotar la página pedida
        // antes de pedir las filas — una "pagina" en la URL mayor al total
        // disponible (bookmark viejo, o cambiar el filtro a uno con menos
        // resultados) ya no debe caer en un OFFSET vacío.
        if ($this->filtroTipo !== 'cotizaciones') {
            $this->totalSesiones = $this->model->contarSesiones($this->filtroUsuario);
            $this->totalPagSes   = max(1, (int) ceil($this->totalSesiones / self::POR_PAGINA));
        }
        if ($this->filtroTipo !== 'sesiones') {
            $this->totalAudits = $this->model->contarAuditorias($this->filtroUsuario, $this->filtroAccion);
            $this->totalPagAud = max(1, (int) ceil($this->totalAudits / self::POR_PAGINA));
        }

        // Ambas secciones comparten el mismo número de página en la URL:
        // se acota contra la más larga de las que estén visibles, para no
        // cortar de más la que tenga más páginas.
        $maxPaginas   = max($this->totalPagSes, $this->totalPagAud);
        $this->pagina = min($this->pagina, $maxPaginas);
        $offset       = ($this->pagina - 1) * self::POR_PAGINA;

        if ($this->filtroTipo !== 'cotizaciones') {
            $this->sesiones = $this->model->getSesiones($this->filtroUsuario, self::POR_PAGINA, $offset);
        }
        if ($this->filtroTipo !== 'sesiones') {
            $this->auditorias = $this->model->getAuditorias($this->filtroUsuario, $this->filtroAccion, self::POR_PAGINA, $offset);
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
