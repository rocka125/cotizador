<?php

/**
 * ListaPreciosController — Orquesta la lógica de la lista de precios.
 *
 * Responsabilidades:
 *  - Interceptar el AJAX ?api=productos y responder JSON.
 *  - Cargar versiones y categorías para la vista principal.
 *  - Exponer los datos como propiedades públicas.
 *
 * No contiene HTML. No hace echo de HTML. Solo lógica.
 */
class ListaPreciosController
{
    // ── Datos listos para la vista ────────────────────────────────────────────
    public array  $versiones     = [];
    public ?array $versionActiva = null;
    public array  $categorias    = [];
    public int    $totalActual   = 0;

    private ListaPreciosModel $model;

    public function __construct(ListaPreciosModel $model)
    {
        $this->model = $model;
    }

    /**
     * Punto de entrada principal.
     * Si es una petición AJAX de productos, responde JSON y termina.
     * Si no, carga los datos para la vista principal.
     */
    public function handle(): void
    {
        if ($this->esAjaxProductos()) {
            $this->handleAjaxProductos();
            exit;
        }

        $this->loadViewData();
    }

    // ── Privados ──────────────────────────────────────────────────────────────

    private function esAjaxProductos(): bool
    {
        return ($_GET['api'] ?? '') === 'productos';
    }

    private function handleAjaxProductos(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $cat = trim($_GET['cat'] ?? '');
        $vid = intval($_GET['vid'] ?? 0);

        // Antes esto devolvía [] en silencio, indistinguible de "categoría sin
        // productos". Ahora avisa explícitamente cuál de los dos parámetros
        // llegó vacío/inválido, para poder diagnosticar el problema real.
        if ($cat === '' || $vid === 0) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Parámetros inválidos recibidos por el servidor.',
                'debug' => ['cat_recibido' => $cat, 'vid_recibido' => $vid],
            ]);
            return;
        }

        try {
            $productos = $this->model->getProductosPorCategoria($vid, $cat);
            echo json_encode($productos);
        } catch (\Throwable $e) {
            http_response_code(500);
            error_log('[ListaPrecios] Error al obtener productos: ' . $e->getMessage());
            echo json_encode([
                'error' => 'Error del servidor al consultar productos.',
                'debug' => $e->getMessage(),
            ]);
        }
    }

    private function loadViewData(): void
    {
        $this->versiones = $this->model->getVersiones();

        // Identificar versión activa
        foreach ($this->versiones as $v) {
            if ($v['activa']) {
                $this->versionActiva = $v;
                break;
            }
        }

        // Categorías solo si hay versión activa
        if ($this->versionActiva) {
            $this->categorias  = $this->model->getCategorias((int)$this->versionActiva['id']);
            $this->totalActual = array_sum(array_column($this->categorias, 'n'));
        }
    }
}