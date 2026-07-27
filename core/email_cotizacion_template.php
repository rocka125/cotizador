<?php
/**
 * email_cotizacion_template.php — Genera el HTML del correo de cotización.
 *
 * Función principal:
 *   buildEmailCotizacion(array $cot, array $items, array $empresa, string $mensaje): string
 *
 * CORRECCIÓN: el heredoc <<<HTML no ejecuta concatenaciones con "."
 * ni ternarios "? :". Todas las expresiones condicionales se pre-calculan
 * como variables antes del return <<<HTML para que la interpolación {$var}
 * funcione correctamente.
 */

/**
 * buildEmailCotizacionCorta — Cuerpo de correo CORTO para cuando la cotización
 * se adjunta como PDF real (ya no hace falta repetir toda la tabla en el HTML
 * del correo). Solo saludo + mensaje personalizado + aviso del adjunto + firma.
 */
function buildEmailCotizacionCorta(
    array  $cot,
    array  $empresa,
    string $mensaje = ''
): string {
    $numero      = htmlspecialchars($cot['numero_cotizacion'] ?? '—');
    $cliente     = htmlspecialchars(trim($cot['atencion'] ?? $cot['cliente_nombre'] ?? ''));
    $empresaNom  = htmlspecialchars(trim($cot['empresa'] ?? ''));

    $empNombre   = htmlspecialchars($empresa['nombre']    ?? 'Fortress8');
    $empEmail    = htmlspecialchars($empresa['email']     ?? '');
    $empTel      = htmlspecialchars($empresa['tel_o']     ?? '');
    $empWeb      = htmlspecialchars($empresa['web']       ?? '');
    $empDir      = htmlspecialchars($empresa['direccion'] ?? '');

    $firmaNombre = htmlspecialchars($cot['firma_nombre']  ?? '');
    $firmaPuesto = htmlspecialchars($cot['firma_puesto']  ?? '');

    $clienteDisplay = $cliente ?: 'cliente';
    $saludoEmpresa  = $empresaNom ? " &mdash; <strong>{$empresaNom}</strong>" : '';

    $mensajeHtml = $mensaje
        ? '<p style="margin:0 0 14px;color:#374151;font-size:14px;line-height:1.5;">'
          . nl2br(htmlspecialchars($mensaje)) . '</p>'
        : '';

    $firmaHtml = '';
    if ($firmaNombre) {
        $firmaPuestoHtml = $firmaPuesto
            ? '<p style="margin:2px 0 0;font-size:11px;color:#6b7280;">' . $firmaPuesto . '</p>'
            : '';
        $firmaHtml = '
        <p style="margin:24px 0 4px;font-size:12px;color:#374151;">Saludos,</p>
        <p style="margin:0;font-size:13px;font-weight:600;color:#111827;">' . $firmaNombre . '</p>
        ' . $firmaPuestoHtml;
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);">

  <tr>
    <td style="background:#D95A00;padding:18px 26px;">
      <p style="margin:0;font-size:11px;font-weight:700;color:rgba(255,255,255,.75);text-transform:uppercase;letter-spacing:.08em;">Cotización</p>
      <p style="margin:4px 0 0;font-size:20px;font-weight:700;color:#fff;">{$numero}</p>
    </td>
  </tr>

  <tr>
    <td style="padding:26px 28px;">
      <p style="margin:0 0 14px;font-size:14px;color:#374151;">Estimado/a {$clienteDisplay}{$saludoEmpresa},</p>
      {$mensajeHtml}
      <p style="margin:0;font-size:14px;color:#374151;line-height:1.5;">
        Adjuntamos en PDF nuestra cotización <strong>{$numero}</strong> con el detalle completo de
        productos, servicios, condiciones comerciales y totales.
      </p>
      <p style="margin:14px 0 0;font-size:14px;color:#374151;">
        Quedamos atentos a cualquier duda o comentario que tengas al respecto.
      </p>

      {$firmaHtml}

      <hr style="margin:28px 0 16px;border:none;border-top:1px solid #e5e7eb;">
      <p style="margin:0;font-size:11px;color:#9ca3af;">
        Este correo y sus adjuntos son confidenciales. Si lo recibiste por error, por favor elimínalo.<br>
        {$empNombre} &nbsp;|&nbsp; {$empDir} &nbsp;|&nbsp; <a href="http://{$empWeb}" style="color:#D95A00;">{$empWeb}</a>
        &nbsp;|&nbsp; {$empEmail} &nbsp;|&nbsp; {$empTel}
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>

</body>
</html>
HTML;
}

function buildEmailCotizacion(
    array  $cot,
    array  $items,
    array  $empresa,
    string $mensaje = ''
): string {

    // ── Datos básicos ─────────────────────────────────────────────────────
    $numero      = htmlspecialchars($cot['numero_cotizacion'] ?? '—');
    $fecha       = !empty($cot['fecha']) ? date('d/m/Y', strtotime($cot['fecha'])) : date('d/m/Y');
    $vigencia    = !empty($cot['vigencia_dias']) ? $cot['vigencia_dias'] . ' días' : '30 días';
    $cliente     = htmlspecialchars(trim($cot['atencion'] ?? $cot['cliente_nombre'] ?? ''));
    $empresa_nom = htmlspecialchars(trim($cot['empresa'] ?? ''));
    $puesto      = htmlspecialchars(trim($cot['puesto']  ?? ''));
    $sym         = in_array($cot['moneda'] ?? 'USD', ['EUR']) ? '€' : '$';
    $moneda      = htmlspecialchars($cot['moneda'] ?? 'USD');
    $subtotal    = (float)($cot['subtotal'] ?? 0);
    $iva         = (float)($cot['iva']      ?? 0);
    $total       = (float)($cot['total']    ?? 0);
    $aplicaIva   = !empty($cot['aplica_iva']) && $iva > 0;

    $empNombre   = htmlspecialchars($empresa['nombre']    ?? 'Fortress8');
    $empEmail    = htmlspecialchars($empresa['email']     ?? '');
    $empTel      = htmlspecialchars($empresa['tel_o']     ?? '');
    $empWeb      = htmlspecialchars($empresa['web']       ?? '');
    $empDir      = htmlspecialchars($empresa['direccion'] ?? '');
    $firmaNombre = htmlspecialchars($cot['firma_nombre']  ?? '');
    $firmaPuesto = htmlspecialchars($cot['firma_puesto']  ?? '');

    $condPago  = htmlspecialchars($cot['condiciones_pago']  ?? '');
    $tEntrega  = htmlspecialchars($cot['tiempo_entrega']    ?? '');
    $lEntrega  = htmlspecialchars($cot['lugar_entrega']     ?? '');

    // ── Pre-calcular expresiones condicionales para el heredoc ────────────
    // El heredoc solo interpola {$variable}, NO ejecuta ternarios ni "."

    // Saludo: "Estimado/a Rodrigo — <strong>Empresa</strong>,"
    $clienteDisplay = $cliente ?: 'cliente';
    $saludoEmpresa  = $empresa_nom
        ? " &mdash; <strong>{$empresa_nom}</strong>"
        : '';

    // Mensaje personalizado
    $mensajeHtml = $mensaje
        ? '<p style="margin:0 0 6px;color:#374151;font-size:14px;">'
          . nl2br(htmlspecialchars($mensaje)) . '</p>'
        : '';

    // Tabla de datos del cliente (empresa / puesto)
    $clienteTablaHtml = '';
    if ($empresa_nom || $puesto) {
        $filaEmpresa = $empresa_nom
            ? '<tr>
                 <td style="padding:7px 10px;font-size:11px;font-weight:600;color:#374151;background:#f9fafb;border-bottom:1px solid #e5e7eb;width:120px;">Empresa</td>
                 <td style="padding:7px 10px;font-size:12px;color:#374151;border-bottom:1px solid #e5e7eb;">' . $empresa_nom . '</td>
               </tr>'
            : '';
        $filaPuesto = $puesto
            ? '<tr>
                 <td style="padding:7px 10px;font-size:11px;font-weight:600;color:#374151;background:#f9fafb;width:120px;">Puesto</td>
                 <td style="padding:7px 10px;font-size:12px;color:#374151;">' . $puesto . '</td>
               </tr>'
            : '';
        $clienteTablaHtml = '<table width="100%" cellpadding="0" cellspacing="0"
            style="border:1px solid #e5e7eb;border-radius:4px;margin-bottom:20px;">
            ' . $filaEmpresa . $filaPuesto . '
          </table>';
    }

    // ── Tabla de productos ────────────────────────────────────────────────
    $rowsHtml = '';
    $alt = false;
    foreach ($items as $item) {
        $cant     = (float)($item['cant']      ?? 0);
        $sku      = htmlspecialchars($item['sku']         ?? '');
        $desc     = htmlspecialchars($item['descripcion'] ?? '');
        $precio   = (float)($item['precio']    ?? 0);
        $descPct  = (float)($item['descuento'] ?? 0);
        $subtItem = (float)($item['subtotal']  ?? ($cant * $precio * (1 - $descPct / 100)));
        $rowBg    = $alt ? '#f9fafb' : '#ffffff';
        $alt      = !$alt;
        $cantStr  = ($cant == intval($cant)) ? intval($cant) : $cant;
        $descStr  = $descPct > 0 ? number_format($descPct, 0) . '%' : '&mdash;';

        $rowsHtml .= '
        <tr style="background:' . $rowBg . ';">
            <td style="padding:8px 10px;font-size:12px;text-align:center;border-bottom:1px solid #e5e7eb;">' . $cantStr . '</td>
            <td style="padding:8px 10px;font-size:11px;font-family:monospace;border-bottom:1px solid #e5e7eb;">' . $sku . '</td>
            <td style="padding:8px 10px;font-size:12px;line-height:1.4;border-bottom:1px solid #e5e7eb;">' . $desc . '</td>
            <td style="padding:8px 10px;font-size:12px;text-align:right;border-bottom:1px solid #e5e7eb;">' . $sym . number_format($precio, 2, '.', ',') . '</td>
            <td style="padding:8px 10px;font-size:12px;text-align:center;border-bottom:1px solid #e5e7eb;">' . $descStr . '</td>
            <td style="padding:8px 10px;font-size:12px;text-align:right;font-weight:500;border-bottom:1px solid #e5e7eb;">' . $sym . number_format($subtItem, 2, '.', ',') . '</td>
        </tr>';
    }

    if (!$rowsHtml) {
        $rowsHtml = '<tr><td colspan="6" style="padding:16px;text-align:center;color:#9ca3af;font-size:12px;">Sin productos registrados</td></tr>';
    }

    // ── Totales ───────────────────────────────────────────────────────────
    $totalesHtml = '
        <tr>
            <td colspan="5" style="text-align:right;padding:8px 10px;font-size:12px;color:#6b7280;">Subtotal</td>
            <td style="text-align:right;padding:8px 10px;font-size:12px;">'
            . $sym . number_format($subtotal, 2, '.', ',') . ' ' . $moneda . '</td>
        </tr>';

    if ($aplicaIva) {
        $totalesHtml .= '
        <tr>
            <td colspan="5" style="text-align:right;padding:6px 10px;font-size:12px;color:#6b7280;">IVA (16%)</td>
            <td style="text-align:right;padding:6px 10px;font-size:12px;">'
            . $sym . number_format($iva, 2, '.', ',') . ' ' . $moneda . '</td>
        </tr>';
    }

    $totalesHtml .= '
        <tr style="background:#D95A00;">
            <td colspan="5" style="text-align:right;padding:10px;font-size:13px;font-weight:700;color:#fff;">TOTAL</td>
            <td style="text-align:right;padding:10px;font-size:14px;font-weight:700;color:#fff;">'
            . $sym . number_format($total, 2, '.', ',') . ' ' . $moneda . '</td>
        </tr>';

    // ── Condiciones comerciales ───────────────────────────────────────────
    $condHtml = '';
    $conds = array_filter([
        'Vigencia de oferta'   => $vigencia,
        'Tiempo de entrega'    => $tEntrega,
        'Condiciones de pago'  => $condPago,
        'Lugar de entrega'     => $lEntrega,
    ]);
    if ($conds) {
        $condRows = '';
        $esUltima = false;
        $total_conds = count($conds);
        $i = 0;
        foreach ($conds as $label => $val) {
            $i++;
            $border = ($i < $total_conds) ? 'border-bottom:1px solid #e5e7eb;' : '';
            $condRows .= '
            <tr>
                <td style="padding:6px 10px;font-size:11px;font-weight:600;color:#374151;background:#f9fafb;' . $border . 'width:160px;">'
                    . htmlspecialchars($label) . '</td>
                <td style="padding:6px 10px;font-size:12px;color:#374151;' . $border . '">'
                    . htmlspecialchars($val) . '</td>
            </tr>';
        }
        $condHtml = '
        <h3 style="margin:28px 0 8px;font-size:12px;font-weight:700;color:#D95A00;text-transform:uppercase;letter-spacing:.06em;">Condiciones comerciales</h3>
        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e5e7eb;border-radius:4px;">
            ' . $condRows . '
        </table>';
    }

    // ── Firma ─────────────────────────────────────────────────────────────
    $firmaHtml = '';
    if ($firmaNombre) {
        $firmaPuestoHtml = $firmaPuesto
            ? '<p style="margin:2px 0 0;font-size:11px;color:#6b7280;">' . $firmaPuesto . '</p>'
            : '';
        $firmaHtml = '
        <p style="margin:28px 0 4px;font-size:12px;color:#374151;">Atentamente,</p>
        <p style="margin:0;font-size:13px;font-weight:600;color:#111827;">' . $firmaNombre . '</p>
        ' . $firmaPuestoHtml;
    }

    // ── HTML final via heredoc ────────────────────────────────────────────
    // Todas las variables son strings simples: {$var} funciona correctamente.
    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:'Segoe UI',Arial,sans-serif;">

<!-- Wrapper -->
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 0;">
<tr><td align="center">
<table width="680" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);">

  <!-- Header naranja -->
  <tr>
    <td style="background:#D95A00;padding:20px 28px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td>
            <p style="margin:0;font-size:11px;font-weight:700;color:rgba(255,255,255,.75);text-transform:uppercase;letter-spacing:.08em;">Cotización</p>
            <p style="margin:4px 0 0;font-size:22px;font-weight:700;color:#fff;">{$numero}</p>
            <p style="margin:4px 0 0;font-size:12px;color:rgba(255,255,255,.8);">{$fecha} &nbsp;&middot;&nbsp; Vigencia: {$vigencia}</p>
          </td>
          <td align="right">
            <p style="margin:0;font-size:14px;font-weight:700;color:#fff;">{$empNombre}</p>
            <p style="margin:3px 0 0;font-size:11px;color:rgba(255,255,255,.8);">{$empEmail}</p>
            <p style="margin:2px 0 0;font-size:11px;color:rgba(255,255,255,.8);">{$empTel}</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- Cuerpo -->
  <tr>
    <td style="padding:28px;">

      <!-- Saludo -->
      <p style="margin:0 0 12px;font-size:14px;color:#374151;">Estimado/a {$clienteDisplay}{$saludoEmpresa},</p>
      {$mensajeHtml}
      <p style="margin:0 0 20px;font-size:14px;color:#374151;">Adjuntamos nuestra propuesta comercial con los siguientes detalles:</p>

      <!-- Datos del cliente -->
      {$clienteTablaHtml}

      <!-- Tabla de productos -->
      <h3 style="margin:0 0 8px;font-size:12px;font-weight:700;color:#D95A00;text-transform:uppercase;letter-spacing:.06em;">Productos / Servicios</h3>
      <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e5e7eb;font-size:12px;">
        <thead>
          <tr style="background:#D95A00;">
            <th style="padding:9px 10px;color:#fff;font-size:10px;font-weight:600;text-align:center;width:44px;">Cant.</th>
            <th style="padding:9px 10px;color:#fff;font-size:10px;font-weight:600;text-align:left;width:90px;">SKU</th>
            <th style="padding:9px 10px;color:#fff;font-size:10px;font-weight:600;text-align:left;">Descripción</th>
            <th style="padding:9px 10px;color:#fff;font-size:10px;font-weight:600;text-align:right;width:80px;">P. Unit.</th>
            <th style="padding:9px 10px;color:#fff;font-size:10px;font-weight:600;text-align:center;width:56px;">Desc.</th>
            <th style="padding:9px 10px;color:#fff;font-size:10px;font-weight:600;text-align:right;width:88px;">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          {$rowsHtml}
        </tbody>
        <tfoot>
          {$totalesHtml}
        </tfoot>
      </table>

      {$condHtml}
      {$firmaHtml}

      <!-- Footer del correo -->
      <hr style="margin:28px 0 16px;border:none;border-top:1px solid #e5e7eb;">
      <p style="margin:0;font-size:11px;color:#9ca3af;">
        Este correo y sus adjuntos son confidenciales. Si lo recibiste por error, por favor elimínalo.<br>
        {$empNombre} &nbsp;|&nbsp; {$empDir} &nbsp;|&nbsp; <a href="http://{$empWeb}" style="color:#D95A00;">{$empWeb}</a>
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>

</body>
</html>
HTML;
}