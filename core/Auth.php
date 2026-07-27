<?php

/**
 * Auth — Verificación de sesión centralizada.
 *
 * Uso:
 *   require_once __DIR__ . '/../core/Auth.php';
 *   $auth = Auth::init();                            // arranca sesión, redirige si no hay sesión
 *   $auth = Auth::init(redirectIfUnauthenticated: false); // no redirige (para login.php)
 *   $auth->estaAutenticado()   // bool — si hay sesión válida
 *   $auth->esAdmin()           // bool
 *   $auth->usuarioId()         // int
 *   $auth->usuarioNombre()     // string
 */
class Auth
{
    private int    $id;
    private string $nombre;
    private string $rol;

    private function __construct()
    {
        $this->id     = intval($_SESSION['id']      ?? 0);
        $this->nombre = strval($_SESSION['usuario'] ?? '');
        $this->rol    = strval($_SESSION['rol']     ?? 'vendedor');
    }

    /**
     * Inicia la sesión y devuelve la instancia.
     *
     * @param string $loginUrl                   URL de redirección si no hay sesión.
     * @param bool   $redirectIfUnauthenticated   Si es false, NO redirige aunque no haya sesión.
     *                                            Útil en login.php para verificar si ya está logueado.
     */
    public static function init(
        string $loginUrl = 'login.php',
        bool $redirectIfUnauthenticated = true
    ): self {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($redirectIfUnauthenticated && empty($_SESSION['usuario'])) {
            header('Location: ' . $loginUrl);
            exit;
        }

        return new self();
    }

    /**
     * Indica si el usuario tiene una sesión activa válida.
     * Útil en login.php para redirigir al dashboard si ya está logueado.
     */
    public function estaAutenticado(): bool
    {
        return !empty($this->nombre) && $this->id > 0;
    }

    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function usuarioId(): int
    {
        return $this->id;
    }

    public function usuarioNombre(): string
    {
        return $this->nombre;
    }

    public function rol(): string
    {
        return $this->rol;
    }

    /** Iniciales para el avatar (máx. 2 caracteres en mayúsculas). */
    public function iniciales(): string
    {
        return strtoupper(mb_substr($this->nombre, 0, 2));
    }
}
