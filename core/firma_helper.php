<?php

/**
 * firma_helper.php — Guarda en disco las imágenes de firma y sello
 * que llegan como Data URL (base64) desde el editor de cotizaciones.
 *
 * Convención de nombre de archivo (ya usada por archivos preexistentes
 * en uploads/firmas/): {tipo}_{usuarioId}_{timestamp}.{ext}
 * Ejemplo: uploads/firmas/firma_5_1781034489.png
 */

if (!function_exists('guardar_imagen_firma')) {
    /**
     * Decodifica un Data URL (data:image/png;base64,....) y lo guarda en
     * uploads/firmas/, devolviendo la ruta relativa para guardar en BD.
     *
     * @param string $dataUrl   Data URL completo recibido del frontend.
     * @param string $tipo      'firma' | 'sello'
     * @param int    $usuarioId ID del usuario propietario (para el nombre de archivo).
     * @return string|null      Ruta relativa (ej. 'uploads/firmas/firma_5_123.png') o null si falla.
     */
    function guardar_imagen_firma(string $dataUrl, string $tipo, int $usuarioId): ?string
    {
        // Solo rasterizadas (png/jpg/webp): se excluye SVG deliberadamente,
        // ya que un SVG puede contener <script> embebido (riesgo de XSS al servirlo).
        if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/i', $dataUrl, $m)) {
            return null;
        }

        $extMap = ['png' => 'png', 'jpg' => 'jpg', 'jpeg' => 'jpg', 'webp' => 'webp'];
        $ext    = $extMap[strtolower($m[1])] ?? 'png';
        $bin    = base64_decode($m[2], true);

        if ($bin === false || strlen($bin) === 0) {
            return null;
        }

        // Límite de seguridad: 3MB decodificado (el frontend ya valida 2MB del archivo original).
        if (strlen($bin) > 3 * 1024 * 1024) {
            return null;
        }

        // Verificación adicional: confirmar que los bytes realmente correspondan a una imagen
        // soportada (evita que un archivo renombrado con MIME falso llegue a disco).
        $info = @getimagesizefromstring($bin);
        if ($info === false || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) {
            return null;
        }

        $dir = __DIR__ . '/../uploads/firmas';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $nombreArchivo = $tipo . '_' . $usuarioId . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        $rutaAbsoluta  = $dir . '/' . $nombreArchivo;

        if (file_put_contents($rutaAbsoluta, $bin) === false) {
            return null;
        }

        return 'uploads/firmas/' . $nombreArchivo;
    }

    /**
     * Elimina el archivo físico de una ruta de firma/sello previamente guardada,
     * sin lanzar error si el archivo ya no existe.
     */
    function eliminar_imagen_firma(?string $rutaRelativa): void
    {
        if (!$rutaRelativa) return;
        $rutaAbsoluta = __DIR__ . '/../' . ltrim($rutaRelativa, '/');
        if (is_file($rutaAbsoluta)) {
            @unlink($rutaAbsoluta);
        }
    }
}
