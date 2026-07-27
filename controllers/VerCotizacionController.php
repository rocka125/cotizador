<?php

/**
 * VerCotizacionController — Orquesta la lógica de ver una cotización.
 *
 * Responsabilidades:
 *  - Leer y validar el parámetro ?id de la URL.
 *  - Llamar al Model para obtener la cotización.
 *  - Redirigir si no existe o el usuario no tiene permiso.
 *  - Preparar y exponer todos los datos que necesita la vista.
 *
 * No contiene HTML. No hace echo de HTML. Solo lógica.
 */
class VerCotizacionController
{
    // ── Datos listos para la vista ────────────────────────────────────────────
    public int    $id;
    public array  $cotizacion          = [];
    public array  $items               = [];
    public array  $empresa             = [];
    public array  $condiciones         = [];
    public array  $firma               = [];
    public string $numeroCotizacion    = '—';
    public string $fechaTexto          = '—';
    public string $monedaCode          = 'USD';
    public string $monedaSym           = '$';
    public string $monedaLabel         = 'USD';
    public float  $tipoCambio          = 1.0;
    public bool   $aplicaIva           = false;
    public float  $subtotal            = 0.0;
    public float  $iva                 = 0.0;
    public float  $total               = 0.0;
    public string $vigenciaDias        = '';
    public string $estadoActual        = 'pendiente';
    public bool   $esAdminViendo       = false; // admin viendo cotización ajena

    private VerCotizacionModel $model;
    private Auth               $auth;

    /** Símbolos y etiquetas por código de moneda. */
    private const MONEDAS = [
        'USD' => ['sym' => '$', 'label' => 'USD'],
        'MXN' => ['sym' => '$', 'label' => 'MXN'],
        'EUR' => ['sym' => '€', 'label' => 'EUR'],
    ];

    public function __construct(VerCotizacionModel $model, Auth $auth)
    {
        $this->model = $model;
        $this->auth  = $auth;
    }

    /**
     * Punto de entrada principal.
     * Valida el ID, carga la cotización y prepara los datos para la vista.
     * Redirige si algo falla.
     */
    public function handle(): void
    {
        $this->id = intval($_GET['id'] ?? 0);

        if ($this->id <= 0) {
            header('Location: dashboard.php');
            exit;
        }

        $cotizacion = $this->model->getCotizacion($this->id);

        if (!$cotizacion) {
            header('Location: dashboard.php');
            exit;
        }

        $this->cotizacion = $cotizacion;
        $this->prepararDatos();
    }

    // ── Privados ──────────────────────────────────────────────────────────────

    private function prepararDatos(): void
    {
        $c = $this->cotizacion;

        // Items
        $this->items = json_decode($c['items'] ?? '[]', true) ?? [];

        // Moneda
        $this->monedaCode  = $c['moneda_code'] ?? 'USD';
        $this->tipoCambio  = (float)($c['tipo_cambio'] ?? 1);
        $moneda            = self::MONEDAS[$this->monedaCode] ?? ['sym' => '$', 'label' => $this->monedaCode];
        $this->monedaSym   = $moneda['sym'];
        $this->monedaLabel = $moneda['label'];

        // IVA y totales
        $this->aplicaIva = !empty($c['aplica_iva']);
        $this->subtotal  = (float)($c['subtotal'] ?? 0);
        $this->iva       = (float)($c['iva']      ?? 0);
        $this->total     = (float)($c['total']    ?? 0);

        // Número y fecha
        $this->numeroCotizacion = $c['numero_cotizacion'] ?? '—';
        $this->vigenciaDias     = $c['vigencia_dias'] ?? '';
        $this->estadoActual     = normalizar_estado($c['estado'] ?? 'pendiente');

        $fechaRaw          = $c['fecha'] ?? null;
        $this->fechaTexto  = $fechaRaw
            ? (new DateTime($fechaRaw))->format('d \d\e F \d\e Y')
            : '—';

        // Empresa (con fallback a defaults de Fortress8)
        $this->empresa = [
            'nombre'    => $c['empresa_razon_social'] ?? 'Fortress8 Cibersecurity Services SA de CV',
            'rfc'       => $c['empresa_rfc']          ?? 'FCS180507LBA',
            'direccion' => $c['empresa_direccion']    ?? 'Cerrada Montejo #190, El Cedro, Nacajuca, Tabasco. Cp. 86220',
            'web'       => $c['empresa_web']          ?? 'www.fortress8.com',
            'email'     => $c['empresa_email']        ?? 'contacto@fortress8.com',
            'tel_o'     => $c['telefono_o']           ?? '9933179494',
            'tel_m'     => $c['telefono_m']           ?? '9934581129',
        ];

        // Condiciones comerciales
        $this->condiciones = [
            't_entrega'   => $c['tiempo_entrega']     ?? '',
            'v_servicios' => $c['vigencia_servicios'] ?? '',
            'cond_pago'   => $c['condiciones_pago']   ?? '',
            'l_entrega'   => $c['lugar_entrega']      ?? '',
        ];

        // Firma
        $this->firma = [
            'nombre'     => $c['firma_nombre']   ?? '',
            'puesto'     => $c['firma_puesto']   ?? '',
            'tel'        => $c['firma_telefono'] ?? '',
            'firma_path' => $c['firma_path']     ?? '',
            'sello_path' => $c['sello_path']     ?? '',
        ];

        // Bandera: admin viendo cotización ajena
        $isOwner              = ((int)$c['usuario_id'] === $this->auth->usuarioId());
        $this->esAdminViendo  = $this->auth->esAdmin() && !$isOwner;
    }
}
