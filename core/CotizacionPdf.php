<?php
/**
 * CotizacionPdf.php — Genera el PDF real (en el servidor) de una cotización,
 * replicando el mismo diseño visual que el usuario ve/edita en pantalla
 * (ver_cotizacion.view.php) y que antes solo se podía "exportar" con
 * window.print() del navegador.
 *
 * Requiere Dompdf (https://github.com/dompdf/dompdf), instalado vía Composer:
 *
 *      composer require dompdf/dompdf
 *
 * Uso:
 *   require_once __DIR__ . '/CotizacionPdf.php';
 *   $pdfBytes = generarCotizacionPdf($cot, $items, $empresa, $condiciones, $firma);
 *   file_put_contents('cotizacion.pdf', $pdfBytes);
 *
 * Todas las imágenes (logo, firma, sello) se embeben como base64 dentro del
 * HTML para que Dompdf no dependa de rutas/URLs — así el PDF se genera igual
 * sin importar el dominio o si "isRemoteEnabled" está activo.
 */

require_once __DIR__ . '/pdf_image_helper.php';

/**
 * Construye el HTML "listo para imprimir" de la cotización.
 * Esta es la ÚNICA fuente de verdad del diseño del PDF: si el día de mañana
 * quieres cambiar el diseño del PDF, es aquí donde se edita.
 *
 * @param array $cot         Fila completa de la tabla `cotizaciones` (o compatible).
 * @param array $items       Array de items ya decodificado (json_decode de $cot['items']).
 * @param array $empresa     ['nombre','rfc','direccion','web','email','tel_o','tel_m']
 * @param array $condiciones ['t_entrega','v_servicios','cond_pago','l_entrega']
 * @param array $firma       ['nombre','puesto','tel','firma_path','sello_path']
 */
function buildCotizacionPdfHtml(
    array $cot,
    array $items,
    array $empresa,
    array $condiciones,
    array $firma
): string {

    // ── Datos básicos ───────────────────────────────────────────────────
    $numero   = htmlspecialchars($cot['numero_cotizacion'] ?? '—');
    $fechaRaw = $cot['fecha'] ?? null;
    $fecha    = $fechaRaw
        ? htmlspecialchars((new DateTime($fechaRaw))->format('d \d\e F \d\e Y'))
        : htmlspecialchars(date('d \d\e F \d\e Y'));

    $atencion = htmlspecialchars(trim($cot['atencion'] ?? $cot['cliente_nombre'] ?? ''));
    $puesto   = htmlspecialchars(trim($cot['puesto']   ?? ''));
    $empCliente = htmlspecialchars(trim($cot['empresa'] ?? ''));

    $monedaCode = $cot['moneda_code'] ?? $cot['moneda'] ?? 'USD';
    $monedasMap = [
        'USD' => ['sym' => '$', 'label' => 'Dólar americano'],
        'MXN' => ['sym' => '$', 'label' => 'Peso mexicano'],
        'EUR' => ['sym' => '€', 'label' => 'Euro'],
    ];
    $monedaInfo = $monedasMap[$monedaCode] ?? ['sym' => '$', 'label' => $monedaCode];
    $sym    = $monedaInfo['sym'];

    $vigenciaDias = htmlspecialchars($cot['vigencia_dias'] ?? '30');

    $ivaPct    = (float)($cot['iva_porcentaje'] ?? 16);
    $subtotal  = (float)($cot['subtotal'] ?? 0);
    $iva       = (float)($cot['iva'] ?? 0);
    $total     = (float)($cot['total'] ?? 0);
    $aplicaIva = !empty($cot['aplica_iva']) || $iva > 0.009;

    // ── Logo, firma y sello embebidos en base64 ─────────────────────────
    $logoDataUri  = pdf_imagen_a_data_uri(__DIR__ . '/../assets/img/logo.png');
    $firmaDataUri = pdf_imagen_a_data_uri(__DIR__ . '/../' . ltrim($firma['firma_path'] ?? '', '/'));
    $selloDataUri = pdf_imagen_a_data_uri(__DIR__ . '/../' . ltrim($firma['sello_path'] ?? '', '/'));

    $logoHtml = $logoDataUri
        ? '<img src="' . $logoDataUri . '" style="height:46px;object-fit:contain">'
        : '<div style="font-size:20px;font-weight:800;color:#D95A00;">FORTRESS8</div>';

    // ── Filas de productos ───────────────────────────────────────────────
    $filasHtml = '';
    foreach ($items as $item) {
        $cant    = $item['cant']        ?? $item['cantidad']      ?? 0;
        $sku     = $item['sku']         ?? $item['codigo']        ?? '';
        $unidad  = $item['unidad']      ?? 'PZA';
        $desc    = $item['descripcion'] ?? '';
        $precio  = (float)($item['precio'] ?? $item['precioUnitario'] ?? 0);
        $descPct = (float)($item['descuento'] ?? 0);
        $ext     = (float)($item['extendido'] ?? ((float)$cant * $precio));
        $subIt   = (float)($item['subtotal'] ?? ($ext * (1 - $descPct / 100)));

        $cantStr = ($cant == intval($cant)) ? (string)intval($cant) : (string)$cant;

        $filasHtml .= '
        <tr>
            <td class="c-cant">' . htmlspecialchars($cantStr) . '</td>
            <td class="c-sku">'  . htmlspecialchars($sku) . '</td>
            <td class="c-unidad">' . htmlspecialchars($unidad) . '</td>
            <td class="c-desc">' . nl2br(htmlspecialchars($desc)) . '</td>
            <td class="c-num">' . number_format($precio, 2) . '</td>
            <td class="c-num">$' . number_format($ext, 2) . '</td>
            <td class="c-cant">' . ($descPct > 0 ? number_format($descPct, 0) . '%' : '0') . '</td>
            <td class="c-num strong">$' . number_format($subIt, 2) . '</td>
        </tr>';
    }
    if (!$filasHtml) {
        $filasHtml = '<tr><td colspan="8" style="text-align:center;padding:14px;color:#9ca3af;">Sin productos registrados</td></tr>';
    }

    // ── Condiciones comerciales ──────────────────────────────────────────
    $tEntrega   = htmlspecialchars($condiciones['t_entrega']   ?? ($cot['tiempo_entrega']     ?? ''));
    $vServicios = htmlspecialchars($condiciones['v_servicios'] ?? ($cot['vigencia_servicios'] ?? ''));
    $condPago   = htmlspecialchars($condiciones['cond_pago']   ?? ($cot['condiciones_pago']   ?? ''));
    $lEntrega   = htmlspecialchars($condiciones['l_entrega']   ?? ($cot['lugar_entrega']      ?? ''));

    // ── Firma ─────────────────────────────────────────────────────────────
    $firmaNombre = htmlspecialchars($firma['nombre'] ?? '');
    $firmaPuesto = htmlspecialchars($firma['puesto'] ?? '');
    $firmaTel    = htmlspecialchars($firma['tel']    ?? '');

    $firmaImgHtml = $firmaDataUri
        ? '<img src="' . $firmaDataUri . '" style="max-height:55px;max-width:180px;object-fit:contain">'
        : '';
    $selloImgHtml = $selloDataUri
        ? '<img src="' . $selloDataUri . '" style="max-height:80px;max-width:140px;object-fit:contain">'
        : '';

    $subtotalStr = number_format($subtotal, 2);
    $totalStr    = number_format($total, 2);

    // ── Fila de IVA (condicional) ────────────────────────────────────────
    $filaIva = '';
    if ($aplicaIva) {
        $filaIva = '
        <tr class="tot-row-shaded">
            <td class="tot-label">IVA <span style="font-size:9px;">' . number_format($ivaPct, 0) . '%</span></td>
            <td class="tot-val">' . $sym . number_format($iva, 2) . ' ' . htmlspecialchars($monedaCode) . '</td>
        </tr>';
    }

    // ── Bloque empresa (encabezado derecho) ──────────────────────────────
    $empNombre = htmlspecialchars($empresa['nombre'] ?? '');
    $empRfc    = htmlspecialchars($empresa['rfc'] ?? '');
    $empDir    = htmlspecialchars($empresa['direccion'] ?? '');
    $empWeb    = htmlspecialchars($empresa['web'] ?? '');
    $empEmail  = htmlspecialchars($empresa['email'] ?? '');
    $empTelO   = htmlspecialchars($empresa['tel_o'] ?? '');
    $empTelM   = htmlspecialchars($empresa['tel_m'] ?? '');

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 28px 34px; }
    * { box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; color:#1f2937; font-size:11px; }

    .header-table { width:100%; border-collapse:collapse; margin-bottom:14px; }
    .header-table td { vertical-align:top; }
    .cot-label { font-size:10px; font-weight:700; color:#D95A00; letter-spacing:.06em; text-transform:uppercase; margin-top:8px; }
    .cot-num   { font-size:19px; font-weight:800; color:#111827; }
    .cot-fecha { font-size:10px; color:#6b7280; }

    .emp-nombre { font-size:11px; font-weight:700; color:#111827; text-align:right; }
    .emp-line   { font-size:9.5px; color:#6b7280; text-align:right; line-height:1.5; }

    .seccion-titulo {
        font-size:10px; font-weight:700; color:#D95A00; text-transform:uppercase;
        letter-spacing:.06em; border-bottom:2px solid #D95A00; padding-bottom:4px; margin:16px 0 8px;
    }

    table.datos-cliente { width:100%; border-collapse:collapse; margin-bottom:6px; }
    table.datos-cliente td {
        border:1px solid #e5e7eb; padding:6px 10px; font-size:10.5px;
    }
    table.datos-cliente td.label {
        background:#f3f4f6; font-weight:700; color:#374151; width:120px; text-transform:uppercase; font-size:9px;
    }

    table.items { width:100%; border-collapse:collapse; margin-top:6px; }
    table.items thead { display: table-header-group; }
    table.items tbody tr { page-break-inside: avoid; page-break-after: auto; }
    table.items thead th {
        background:#D95A00; color:#fff; font-size:9px; font-weight:700; text-transform:uppercase;
        padding:6px 6px; text-align:left; border:1px solid #D95A00;
    }
    table.items tbody td {
        border:1px solid #e5e7eb; padding:6px 6px; font-size:9.5px; vertical-align:top;
        word-wrap: break-word; overflow-wrap: break-word;
    }
    table.items tbody tr:nth-child(even) { background:#fafafa; }
    .c-cant   { text-align:center; width:32px; }
    .c-sku    { width:118px; font-family:monospace; font-size:8px; color:#4b5563; word-break:break-all; }
    .c-unidad { text-align:center; width:42px; }
    .c-desc   { line-height:1.35; }
    .c-num    { text-align:right; white-space:nowrap; width:58px; }
    .strong   { font-weight:700; }

    .resumen-table { width:100%; border-collapse:collapse; margin-top:16px; page-break-inside: avoid; }
    .resumen-table td { vertical-align:top; }

    .moneda-box {
        background:#f9fafb; border-radius:4px; padding:12px 14px; font-size:9.5px; color:#374151;
    }
    .moneda-box .m-label { font-size:8px; text-transform:uppercase; letter-spacing:.05em; color:#9ca3af; margin-bottom:1px; }
    .moneda-box .m-val   { font-size:10.5px; color:#111827; margin-bottom:8px; }

    .totales-table { width:100%; border-collapse:collapse; page-break-inside: avoid; }
    .totales-table tr.tot-row-shaded td { background:#f9fafb; }
    .tot-label { text-align:right; padding:7px 12px; font-size:10px; color:#6b7280; }
    .tot-val   { text-align:right; padding:7px 12px; font-size:10.5px; color:#111827; width:120px; font-weight:600; }
    .tot-total-label { text-align:right; padding:10px 12px; font-size:11px; font-weight:800; color:#fff; background:#D95A00; }
    .tot-total-val   {
        text-align:right; padding:8px 10px; font-size:14px; font-weight:800; color:#D95A00;
        width:120px; background:#ffffff; border:2px solid #D95A00;
    }

    table.condiciones { width:100%; border-collapse:collapse; margin-top:8px; }
    table.condiciones td {
        padding:5px 4px; font-size:10px; width:50%; vertical-align:top;
    }
    table.condiciones .label {
        font-weight:700; color:#374151;
    }
    table.condiciones .val {
        color:#374151;
    }

    .firma-table { width:100%; border-collapse:collapse; margin-top:40px; page-break-inside: avoid; }
    .firma-cell { vertical-align:bottom; }
    .firma-linea { border-top:1px solid #9ca3af; width:200px; margin-top:8px; }
    .firma-nombre { font-size:11px; font-weight:700; color:#111827; margin-top:5px; }
    .firma-puesto { font-size:9.5px; color:#6b7280; margin-top:1px; }
    .sello-caption { font-size:8.5px; color:#9ca3af; font-style:italic; text-align:center; margin-top:4px; }
</style>
</head>
<body>

<table class="header-table">
  <tr>
    <td style="width:55%;">
      {$logoHtml}
      <div class="cot-label">Cotización</div>
      <div class="cot-num">{$numero}</div>
      <div class="cot-fecha">{$fecha}</div>
    </td>
    <td style="width:45%;">
      <div class="emp-nombre">{$empNombre}</div>
      <div class="emp-line">RFC: {$empRfc}</div>
      <div class="emp-line">{$empDir}</div>
      <div class="emp-line">{$empWeb} &nbsp;·&nbsp; {$empEmail}</div>
      <div class="emp-line">O: {$empTelO} &nbsp;·&nbsp; M: {$empTelM}</div>
    </td>
  </tr>
</table>

<div class="seccion-titulo">Datos del cliente</div>
<table class="datos-cliente">
  <tr><td class="label">Atención</td><td>{$atencion}</td></tr>
  <tr><td class="label">Puesto</td><td>{$puesto}</td></tr>
  <tr><td class="label">Empresa</td><td>{$empCliente}</td></tr>
</table>

<div class="seccion-titulo">Productos / Servicios</div>
<table class="items">
  <thead>
    <tr>
      <th class="c-cant">Cant.</th>
      <th class="c-sku">SKU</th>
      <th class="c-unidad">Unidad</th>
      <th class="c-desc">Descripción</th>
      <th class="c-num">P. Unit.</th>
      <th class="c-num">P. Ext.</th>
      <th class="c-cant">Desc. %</th>
      <th class="c-num">Subtotal</th>
    </tr>
  </thead>
  <tbody>
    {$filasHtml}
  </tbody>
</table>

<table class="resumen-table">
  <tr>
    <td style="width:52%;padding-right:14px;">
      <div class="moneda-box">
        <div class="m-label">Moneda</div>
        <div class="m-val">{$monedaCode} — {$monedaInfo['label']}</div>
        <div class="m-label">Vigencia</div>
        <div class="m-val" style="margin-bottom:0;">{$vigenciaDias} días naturales</div>
      </div>
    </td>
    <td style="width:48%;">
      <table class="totales-table">
        <tr class="tot-row-shaded">
          <td class="tot-label">Subtotal</td>
          <td class="tot-val">{$sym}{$subtotalStr}</td>
        </tr>
        {$filaIva}
        <tr>
          <td class="tot-total-label">TOTAL</td>
          <td class="tot-total-val">{$sym}{$totalStr}</td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<div class="seccion-titulo">Condiciones comerciales</div>
<table class="condiciones">
  <tr>
    <td><span class="label">Tiempo de entrega:</span> <span class="val">{$tEntrega}</span></td>
    <td><span class="label">Vigencia de servicios:</span> <span class="val">{$vServicios}</span></td>
  </tr>
  <tr>
    <td><span class="label">Condiciones de pago:</span> <span class="val">{$condPago}</span></td>
    <td><span class="label">Lugar de entrega:</span> <span class="val">{$lEntrega}</span></td>
  </tr>
</table>

<div style="border-top:1px solid #e5e7eb;margin-top:26px;"></div>
<table class="firma-table">
  <tr>
    <td style="width:60%;" class="firma-cell">
      {$firmaImgHtml}
      <div class="firma-linea"></div>
      <div class="firma-nombre">{$firmaNombre}</div>
      <div class="firma-puesto">{$firmaPuesto}</div>
      <div class="firma-puesto">{$firmaTel}</div>
    </td>
    <td style="width:40%;text-align:center;" class="firma-cell">
      {$selloImgHtml}
      <div class="sello-caption">Sello de la empresa</div>
    </td>
  </tr>
</table>

</body>
</html>
HTML;
}

/**
 * Genera el PDF (bytes binarios) de una cotización usando Dompdf.
 *
 * @throws RuntimeException si Dompdf no está instalado (falta composer install).
 */
function generarCotizacionPdf(
    array $cot,
    array $items,
    array $empresa,
    array $condiciones,
    array $firma
): string {
    $autoload = __DIR__ . '/../vendor/autoload.php';

    if (!file_exists($autoload)) {
        throw new RuntimeException(
            'Falta instalar Dompdf. Ejecuta en la carpeta del proyecto: composer require dompdf/dompdf'
        );
    }

    require_once $autoload;

    if (!class_exists(\Dompdf\Dompdf::class)) {
        throw new RuntimeException('Dompdf no se cargó correctamente. Revisa vendor/autoload.php');
    }

    $html = buildCotizacionPdfHtml($cot, $items, $empresa, $condiciones, $firma);

    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', false); // no se necesita: todo va embebido en base64
    $options->set('defaultFont', 'Helvetica');

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->output();
}