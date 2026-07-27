<?php

class CotizacionController
{
    // ── Datos listos para la vista ────────────────────────────────────────────
    public string $action            = 'nueva';
    public ?int   $cotizacionId      = null;
    public array  $cd                = [];   // campos del documento (empresa, cliente, etc.)
    public array  $itemsData         = [];   // productos de la cotización actual
    public ?array $versionActiva     = null;
    public array  $priceList         = [];
    public array  $porCategoria      = [];
    public string $numeroCotizacion  = '';
    public string $fechaTexto        = '';
    public string $estadoActual      = 'pendiente';
    public string $cotOwnerNombre    = '';   // nombre del propietario (admin viendo cotización ajena)
    public ?int   $cotOwnerId        = null;

    // JSON pre-encoded para el JS de la vista
    public string $initialItemsJson  = '[]';
    public string $priceListJson     = '[]';
    public string $versionActivaJson = 'null';

    private CotizacionModel $model;
    private Auth            $auth;

    public function __construct(CotizacionModel $model, Auth $auth)
    {
        $this->model = $model;
        $this->auth  = $auth;
    }

    public function handle(): void
    {
        $this->action       = $_GET['action'] ?? 'nueva';
        $this->cotizacionId = isset($_GET['id']) ? intval($_GET['id']) : null;

        if ($this->action === 'editar' && $this->cotizacionId) {
            $this->cargarEdicion();
        } else {
            $this->prepararNueva();
        }

        $this->cargarListaPrecios();
        $this->encoderJsonParaJs();
    }

    // ── Privados ──────────────────────────────────────────────────────────────

    private function cargarEdicion(): void
    {
        $cot = $this->model->getCotizacion($this->cotizacionId);

        if (!$cot) {
            header('Location: dashboard.php');
            exit;
        }

        $this->itemsData    = json_decode($cot['items'] ?? '[]', true) ?? [];
        $this->estadoActual = normalizar_estado($cot['estado'] ?? null);

        // Propietario (para aviso al admin)
        $this->cotOwnerId = isset($cot['usuario_id']) ? intval($cot['usuario_id']) : null;
        if ($this->auth->esAdmin() && $this->cotOwnerId && $this->cotOwnerId !== $this->auth->usuarioId()) {
            $this->cotOwnerNombre = $this->model->getNombrePropietario($this->cotOwnerId);
        }

        $this->numeroCotizacion = $cot['numero_cotizacion'] ?? ('COT-' . date('Y'));
        $fechaRaw               = $cot['fecha'] ?? null;
        $this->fechaTexto       = $fechaRaw
            ? (new DateTime($fechaRaw))->format('d \d\e F \d\e Y')
            : (new DateTime())->format('d \d\e F \d\e Y');

        $this->cd = $this->buildCd($cot);
    }

    private function prepararNueva(): void
    {
        $this->cotizacionId     = null;
        $this->numeroCotizacion = 'COT-' . date('Y');
        $this->fechaTexto       = (new DateTime())->format('d \d\e F \d\e Y');
        $this->cd               = $this->buildCd([]);
    }

    /**
     * Construye el array $cd (campos del documento) con fallbacks a los defaults de Fortress8.
     */
    private function buildCd(array $raw): array
    {
        return [
            'nombre'      => $raw['empresa_razon_social'] ?? 'Fortress8 Cibersecurity Services SA de CV',
            'rfc'         => $raw['empresa_rfc']          ?? 'FCS180507LBA',
            'direccion'   => $raw['empresa_direccion']    ?? 'Cerrada Montejo #190, El Cedro, Nacajuca, Tabasco. Cp. 86220',
            'web'         => $raw['empresa_web']          ?? 'www.fortress8.com',
            'email'       => $raw['empresa_email']        ?? 'contacto@fortress8.com',
            'tel_o'       => $raw['telefono_o']           ?? '9933179494',
            'tel_m'       => $raw['telefono_m']           ?? '9934581129',
            'atencion'    => $raw['atencion']             ?? '',
            'puesto_c'    => $raw['puesto']               ?? '',
            'empresa_c'   => $raw['empresa']              ?? '',
            'vigencia'    => $raw['vigencia_dias']        ?? '',
            'moneda_code' => $raw['moneda_code']          ?? 'USD',
            'tipo_cambio' => $raw['tipo_cambio']          ?? '1.0000',
            't_entrega'   => $raw['tiempo_entrega']       ?? '',
            'v_servicios' => $raw['vigencia_servicios']   ?? '',
            'cond_pago'   => $raw['condiciones_pago']     ?? '',
            'l_entrega'   => $raw['lugar_entrega']        ?? '',
            'f_nombre'    => $raw['firma_nombre']         ?? '',
            'f_puesto'    => $raw['firma_puesto']         ?? '',
            'f_tel'       => $raw['firma_telefono']       ?? '',
            'firma_path'  => $raw['firma_path']           ?? '',
            'sello_path'  => $raw['sello_path']           ?? '',
            'aplica_iva'  => !empty($raw['aplica_iva']),
            'iva_pct'     => $raw['iva_porcentaje']       ?? 16,
        ];
    }

    private function cargarListaPrecios(): void
    {
        $this->versionActiva = $this->model->getVersionActiva();

        if ($this->versionActiva) {
            $this->priceList = $this->model->getPriceList((int)$this->versionActiva['id']);
        }

        foreach ($this->priceList as $p) {
            $cat = $p['sheet'] ?? 'General';
            $this->porCategoria[$cat][] = $p;
        }
    }

    private function encoderJsonParaJs(): void
    {
        $this->initialItemsJson = json_encode($this->itemsData ?: []);
        $this->priceListJson    = json_encode($this->priceList);
        $this->versionActivaJson = json_encode(
            $this->versionActiva
                ? [
                    'id'     => (int)$this->versionActiva['id'],
                    'nombre' => $this->versionActiva['nombre'],
                    'fecha'  => date('d/m/Y H:i', strtotime($this->versionActiva['created_at'])),
                    'total'  => count($this->priceList),
                  ]
                : null
        );
    }
}
