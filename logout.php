<?php
/**
 * logout.php — Entry point de cierre de sesión.
 *
 * Registra auditoría, destruye la sesión y redirige al login.
 */

session_start();
require __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/auditoria_helper.php';
if (isset($_SESSION['id'], $_SESSION['usuario'])) {
    audit_sesion($conexion, (int)$_SESSION['id'], $_SESSION['usuario'], 'logout');
}
session_destroy();

header("Location: login.php");
exit;