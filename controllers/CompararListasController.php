<?php

/**
 * CompararListasController — Orquesta la lógica del comparador de versiones.
 *
 * Responsabilidades:
 *  - Interceptar los dos endpoints AJAX (?api=comparar y ?api=cats).
 *  - Ejecutar el algoritmo de diff (nuevos / eliminados / cambios / iguales).
 *  - Cargar las versiones disponibles para el selector de la vista.
 *
 * No contiene HTML. No hace echo de HTML. Solo lógica.
 */
class CompararListasController
{
    // ── Datos listos para la vista principal ──────────────────────────────
    public array $versiones = [];

    private CompararListasModel $model;

    public function __construct(CompararListasModel $model)
    {
        $this->model = $model;
    }

    /**
     * Punto de entrada principal.
     * Despacha al handler correcto según ?api o carga la vista normal.
     */
    public function handle(): void
    {
        $api = $_GET['api'] ?? '';

        if ($api === 'comparar') {
            $this->handleAjaxComparar();
            exit;
        }

        if ($api === 'cats') {
            $this->handleAjaxCats();
            exit;
        }

        $this->versiones = $this->model->getVersiones();
    }

    // ── AJAX: comparar dos versiones ──────────────────────────────────────

    private function handleAjaxComparar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $vidA = intval($_GET['a'] ?? 0);
        $vidB = intval($_GET['b'] ?? 0);
        $cat  = trim($_GET['cat'] ?? '');

        if ($vidA <= 0 || $vidB <= 0 || $vidA === $vidB) {
            echo json_encode(['ok' => false, 'error' => 'Selecciona dos versiones distintas']);
            return;
        }

        $mapA = $this->model->getSkusIndexados($vidA, $cat);
        $mapB = $this->model->getSkusIndexados($vidB, $cat);

        echo json_encode([
            'ok' => true,
            ...$this->diff($mapA, $mapB),
            'total_a' => count($mapA),
            'total_b' => count($mapB),
        ]);
    }

    // ── AJAX: categorías de una versión ───────────────────────────────────

    private function handleAjaxCats(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $vid = intval($_GET['vid'] ?? 0);
        if ($vid <= 0) {
            echo json_encode([]);
            return;
        }

        echo json_encode($this->model->getCategorias($vid));
    }

    // ── Algoritmo de diff ─────────────────────────────────────────────────

    /**
     * Compara dos mapas de SKUs y devuelve nuevos, eliminados, cambios e iguales.
     *
     * @param array<string,array> $mapA  SKUs de la versión base (A)
     * @param array<string,array> $mapB  SKUs de la versión nueva (B)
     *
     * @return array{nuevos:array[], eliminados:array[], cambios:array[], iguales:int}
     */
    private function diff(array $mapA, array $mapB): array
    {
        $nuevos    = [];
        $eliminados = [];
        $cambios   = [];
        $iguales   = 0;

        // Recorrer B: detectar nuevos y cambios de precio
        foreach ($mapB as $sku => $b) {
            if (!isset($mapA[$sku])) {
                $nuevos[] = [
                    'sku'         => $sku,
                    'categoria'   => $b['categoria'],
                    'descripcion' => $b['descripcion'],
                    'precio_b'    => (float)$b['precio'],
                ];
            } else {
                $pA = round((float)$mapA[$sku]['precio'], 2);
                $pB = round((float)$b['precio'], 2);

                if ($pA !== $pB) {
                    $diff = $pB - $pA;
                    $cambios[] = [
                        'sku'         => $sku,
                        'categoria'   => $b['categoria'],
                        'descripcion' => $b['descripcion'],
                        'precio_a'    => $pA,
                        'precio_b'    => $pB,
                        'diff'        => round($diff, 2),
                        'pct'         => $pA > 0 ? round(($diff / $pA) * 100, 2) : null,
                    ];
                } else {
                    $iguales++;
                }
            }
        }

        // Recorrer A: detectar eliminados (solo en A)
        foreach ($mapA as $sku => $a) {
            if (!isset($mapB[$sku])) {
                $eliminados[] = [
                    'sku'         => $sku,
                    'categoria'   => $a['categoria'],
                    'descripcion' => $a['descripcion'],
                    'precio_a'    => (float)$a['precio'],
                ];
            }
        }

        // Ordenar cambios por variación porcentual descendente (mayor impacto primero)
        usort($cambios, fn($x, $y) => abs($y['pct'] ?? 0) <=> abs($x['pct'] ?? 0));

        return compact('nuevos', 'eliminados', 'cambios', 'iguales');
    }
}