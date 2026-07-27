<?php

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

require 'db.php';

// ── Obtener la versión activa ─────────────────────────────────────────────
$resV = $conexion->query(
    "SELECT id, nombre, total_skus, created_at
     FROM versiones_lista
     WHERE activa = 1
     LIMIT 1"
);
$version = $resV ? $resV->fetch_assoc() : null;

// ── Endpoint: solo metadatos ──────────────────────────────────────────────
if (isset($_GET['meta'])) {
    echo json_encode([
        'ok'      => true,
        'version' => $version
            ? [
                'id'         => (int)$version['id'],
                'nombre'     => $version['nombre'],
                'total_skus' => (int)$version['total_skus'],
                'fecha'      => date('d/m/Y H:i', strtotime($version['created_at'])),
              ]
            : null,
    ]);
    exit;
}

// ── Sin versión activa ────────────────────────────────────────────────────
if (!$version) {
    echo json_encode([
        'ok'       => true,
        'version'  => null,
        'productos'=> [],
        'total'    => 0,
    ]);
    exit;
}

$vid = (int)$version['id'];

// ── Filtro de búsqueda opcional ───────────────────────────────────────────
$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $conexion->prepare(
        "SELECT categoria AS sheet, codigo AS sku, producto,
                descripcion AS `desc`, precio AS price
         FROM lista_precios
         WHERE activo = 1
           AND version_id = ?
           AND (codigo LIKE ? OR descripcion LIKE ? OR producto LIKE ?)
         ORDER BY categoria, producto
         LIMIT 500"
    );
    $stmt->bind_param('isss', $vid, $like, $like, $like);
} else {
    $stmt = $conexion->prepare(
        "SELECT categoria AS sheet, codigo AS sku, producto,
                descripcion AS `desc`, precio AS price
         FROM lista_precios
         WHERE activo = 1
           AND version_id = ?
         ORDER BY categoria, producto"
    );
    $stmt->bind_param('i', $vid);
}

$stmt->execute();
$productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Asegurar tipos numéricos correctos
foreach ($productos as &$p) {
    $p['price'] = (float)$p['price'];
}
unset($p);

echo json_encode([
    'ok'      => true,
    'version' => [
        'id'         => $vid,
        'nombre'     => $version['nombre'],
        'total_skus' => (int)$version['total_skus'],
        'fecha'      => date('d/m/Y H:i', strtotime($version['created_at'])),
    ],
    'productos' => $productos,
    'total'     => count($productos),
]);