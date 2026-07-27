<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

set_error_handler(function($errno, $errstr) {
    echo json_encode(['ok' => false, 'error' => "PHP [$errno]: $errstr"]);
    exit;
});
ini_set('display_errors', '0');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

require '../core/db.php';

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// ── Acción: activar versión existente ────────────────────────────────────
if (isset($data['action']) && $data['action'] === 'activar') {
    $vid = intval($data['version_id'] ?? 0);
    if ($vid <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID de versión inválido']);
        exit;
    }

    // Verificar que la versión existe y obtener su nombre
    $chk = $conexion->prepare("SELECT id, nombre, total_skus FROM versiones_lista WHERE id = ?");
    $chk->bind_param('i', $vid);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$row) {
        echo json_encode(['ok' => false, 'error' => 'Versión no encontrada']);
        exit;
    }

    $conexion->begin_transaction();
    try {
        $conexion->query("UPDATE versiones_lista SET activa = 0");
        $upd = $conexion->prepare("UPDATE versiones_lista SET activa = 1 WHERE id = ?");
        $upd->bind_param('i', $vid);
        $upd->execute();
        $upd->close();
        $conexion->commit();

        echo json_encode([
            'ok'        => true,
            'version_id'=> $vid,
            'nombre'    => $row['nombre'],
            'total_skus'=> $row['total_skus'],
        ]);
    } catch (\Exception $e) {
        $conexion->rollback();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── Acción: eliminar versión archivada ───────────────────────────────────
if (isset($data['action']) && $data['action'] === 'eliminar') {
    $vid = intval($data['version_id'] ?? 0);
    if ($vid <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID inválido']);
        exit;
    }

    // No se puede eliminar la versión activa
    $chk = $conexion->prepare("SELECT activa, nombre FROM versiones_lista WHERE id = ?");
    $chk->bind_param('i', $vid);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$row) {
        echo json_encode(['ok' => false, 'error' => 'Versión no encontrada']);
        exit;
    }
    if ($row['activa']) {
        echo json_encode(['ok' => false, 'error' => 'No puedes eliminar la versión activa']);
        exit;
    }

    $conexion->begin_transaction();
    try {
        $del1 = $conexion->prepare("DELETE FROM lista_precios WHERE version_id = ?");
        $del1->bind_param('i', $vid);
        $del1->execute();
        $del1->close();

        $del2 = $conexion->prepare("DELETE FROM versiones_lista WHERE id = ?");
        $del2->bind_param('i', $vid);
        $del2->execute();
        $del2->close();

        $conexion->commit();
        echo json_encode(['ok' => true, 'eliminado' => $vid, 'nombre' => $row['nombre']]);
    } catch (\Exception $e) {
        $conexion->rollback();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── Acción: iniciar import por lotes (crea versión SIN activar todavía) ──
if (isset($data['action']) && $data['action'] === 'iniciar') {
    $nombre      = trim($data['nombre'] ?? '');
    $descripcion = trim($data['descripcion'] ?? '');
    if ($nombre === '') {
        echo json_encode(['ok' => false, 'error' => 'Falta el nombre de la versión']);
        exit;
    }

    $stmt = $conexion->prepare(
        "INSERT INTO versiones_lista (nombre, descripcion, activa, total_skus) VALUES (?, ?, 0, 0)"
    );
    $stmt->bind_param('ss', $nombre, $descripcion);
    $stmt->execute();
    $version_id = $conexion->insert_id;
    $stmt->close();

    echo json_encode(['ok' => true, 'version_id' => $version_id]);
    exit;
}

// ── Acción: insertar un lote de productos en una versión ya creada ───────
if (isset($data['action']) && $data['action'] === 'lote') {
    $version_id = intval($data['version_id'] ?? 0);
    $productos  = $data['productos'] ?? null;

    if ($version_id <= 0 || !is_array($productos)) {
        echo json_encode(['ok' => false, 'error' => 'Payload de lote inválido']);
        exit;
    }

    // La versión debe existir y estar todavía inactiva (recién creada por 'iniciar')
    $chk = $conexion->prepare("SELECT id FROM versiones_lista WHERE id = ? AND activa = 0");
    $chk->bind_param('i', $version_id);
    $chk->execute();
    $existe = $chk->get_result()->fetch_assoc();
    $chk->close();
    if (!$existe) {
        echo json_encode(['ok' => false, 'error' => 'Versión inválida o ya activa']);
        exit;
    }

    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare(
            "INSERT INTO lista_precios (version_id, categoria, codigo, producto, descripcion, precio, activo)
             VALUES (?, ?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE
                 categoria   = VALUES(categoria),
                 producto    = VALUES(producto),
                 descripcion = VALUES(descripcion),
                 precio      = VALUES(precio),
                 activo      = 1"
        );

        $insertados = 0;
        foreach ($productos as $p) {
            $cat    = mb_substr(trim($p['categoria']   ?? ''), 0, 100);
            $sku    = mb_substr(trim($p['sku']         ?? ''), 0, 255);
            $prod   = mb_substr(trim($p['producto']    ?? $sku), 0, 255);
            $desc   = trim($p['descripcion'] ?? '');
            $precio = round((float)($p['precio'] ?? 0), 2);

            if ($sku === '' || $precio <= 0) continue;

            $stmt->bind_param('issssd', $version_id, $cat, $sku, $prod, $desc, $precio);
            $stmt->execute();
            $insertados++;
        }
        $stmt->close();
        $conexion->commit();

        echo json_encode(['ok' => true, 'insertados' => $insertados]);
    } catch (\Exception $e) {
        $conexion->rollback();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── Acción: finalizar import por lotes (activa la versión) ───────────────
if (isset($data['action']) && $data['action'] === 'finalizar') {
    $version_id = intval($data['version_id'] ?? 0);
    if ($version_id <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID de versión inválido']);
        exit;
    }

    $conexion->begin_transaction();
    try {
        $cnt = $conexion->prepare("SELECT COUNT(DISTINCT codigo) AS n FROM lista_precios WHERE version_id = ?");
        $cnt->bind_param('i', $version_id);
        $cnt->execute();
        $total = (int)($cnt->get_result()->fetch_assoc()['n'] ?? 0);
        $cnt->close();

        if ($total === 0) {
            $conexion->rollback();
            echo json_encode(['ok' => false, 'error' => 'No se insertó ningún producto en esta versión, no se activará']);
            exit;
        }

        $conexion->query("UPDATE versiones_lista SET activa = 0");

        $upd = $conexion->prepare("UPDATE versiones_lista SET activa = 1, total_skus = ? WHERE id = ?");
        $upd->bind_param('ii', $total, $version_id);
        $upd->execute();
        $upd->close();

        $conexion->commit();
        echo json_encode(['ok' => true, 'version_id' => $version_id, 'total_skus' => $total]);
    } catch (\Exception $e) {
        $conexion->rollback();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── Acción default: importar nuevo Excel (payload único, se mantiene por compatibilidad) ──
if (!$data || empty($data['nombre']) || empty($data['productos']) || !is_array($data['productos'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Payload inválido']);
    exit;
}

$nombre      = trim($data['nombre']);
$descripcion = trim($data['descripcion'] ?? '');
$productos   = $data['productos'];

if (count($productos) === 0) {
    echo json_encode(['ok' => false, 'error' => 'No se detectaron productos en el archivo']);
    exit;
}

// ── Transacción ───────────────────────────────────────────────────────────
$conexion->begin_transaction();

try {
    // 1. Desactivar todas las versiones anteriores
    $conexion->query("UPDATE versiones_lista SET activa = 0");

    // 2. Crear la nueva versión
    $stmt = $conexion->prepare(
        "INSERT INTO versiones_lista (nombre, descripcion, activa, total_skus) VALUES (?, ?, 1, ?)"
    );
    $total = count($productos);
    $stmt->bind_param('ssi', $nombre, $descripcion, $total);
    $stmt->execute();
    $version_id = $conexion->insert_id;
    $stmt->close();

    // 3. Insertar productos
    $stmt = $conexion->prepare(
        "INSERT INTO lista_precios (version_id, categoria, codigo, producto, descripcion, precio, activo)
         VALUES (?, ?, ?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE
             categoria   = VALUES(categoria),
             producto    = VALUES(producto),
             descripcion = VALUES(descripcion),
             precio      = VALUES(precio),
             activo      = 1"
    );

    $insertados = 0;
    $vistos     = [];

    foreach ($productos as $p) {
        $cat    = mb_substr(trim($p['categoria']   ?? ''), 0, 100);
        $sku    = mb_substr(trim($p['sku']         ?? ''), 0, 255);
        $prod   = mb_substr(trim($p['producto']    ?? $sku), 0, 255);
        $desc   = trim($p['descripcion'] ?? '');
        $precio = round((float)($p['precio'] ?? 0), 2);

        if ($sku === '' || $precio <= 0) continue;

        $stmt->bind_param('issssd', $version_id, $cat, $sku, $prod, $desc, $precio);
        $stmt->execute();

        if (!isset($vistos[$sku])) {
            $vistos[$sku] = true;
            $insertados++;
        }
    }
    $stmt->close();

    // 4. Actualizar conteo real
    $upd = $conexion->prepare("UPDATE versiones_lista SET total_skus = ? WHERE id = ?");
    $upd->bind_param('ii', $insertados, $version_id);
    $upd->execute();
    $upd->close();

    $conexion->commit();

    echo json_encode([
        'ok'         => true,
        'version_id' => $version_id,
        'nombre'     => $nombre,
        'insertados' => $insertados,
    ]);

} catch (\Exception $e) {
    $conexion->rollback();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}