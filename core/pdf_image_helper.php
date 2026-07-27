<?php
/**
 * pdf_image_helper.php — Convierte una imagen en disco a un Data URI base64
 * para poder embeberla directamente en el HTML que se manda a Dompdf.
 * Esto evita depender de rutas relativas o de que "isRemoteEnabled" esté activo.
 */

if (!function_exists('pdf_imagen_a_data_uri')) {
    function pdf_imagen_a_data_uri(?string $rutaAbsoluta): ?string
    {
        if (!$rutaAbsoluta || !is_file($rutaAbsoluta)) {
            return null;
        }

        $info = @getimagesize($rutaAbsoluta);
        if ($info === false) {
            return null;
        }

        $mime = $info['mime'] ?? 'image/png';
        $data = @file_get_contents($rutaAbsoluta);
        if ($data === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}
