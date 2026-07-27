<?php
/**
 * descargar_pdf.php — Genera y descarga el PDF real de una cotización.
 *
 * Reemplaza a window.print() para el botón "Exportar PDF": en lugar de
 * abrir el diálogo de impresión del navegador (que agrega URL, título de
 * la pestaña, fecha y número de página como encabezado/pie), este endpoint
 * genera el PDF en el servidor con Dompdf — el mismo motor que ya se usa
 * para adjuntar el PDF en el envío por correo (ver API/api_enviar_correo.php).
 *
 * El resultado es un PDF limpio, sin nada agregado por el navegador.
 *
 * Uso: descargar_pdf.php?id=98
 */

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/models/VerCotizacionModel.php';
require_once __DIR__ . '/controllers/VerCotizacionController.php';
require_once __DIR__ . '/core/CotizacionPdf.php';

$auth  = Auth::init();
$model = new VerCotizacionModel($conexion, $auth->usuarioId(), $auth->esAdmin());
$ctrl  = new VerCotizacionController($model, $auth);
$ctrl->handle(); // ya valida permisos/existencia y redirige si algo falla

try {
    $pdfBytes = generarCotizacionPdf(
        $ctrl->cotizacion,
        $ctrl->items,
        $ctrl->empresa,
        $ctrl->condiciones,
        $ctrl->firma
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo 'No se pudo generar el PDF: ' . htmlspecialchars($e->getMessage());
    exit;
}

$nombreArchivo = 'Cotizacion-' . preg_replace('/[^A-Za-z0-9\-_]/', '', $ctrl->numeroCotizacion) . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
header('Content-Length: ' . strlen($pdfBytes));
echo $pdfBytes;
