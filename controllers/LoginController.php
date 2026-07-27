<?php

/**
 * LoginController — Orquesta la autenticación del formulario de login.
 *
 * Responsabilidades:
 *  - Solo actuar cuando el método es POST.
 *  - Buscar el usuario, verificar la contraseña con password_verify().
 *  - Registrar la auditoría de sesión.
 *  - Escribir las variables de sesión y redirigir.
 *  - Exponer el mensaje de error para la vista cuando falla.
 *
 * No contiene HTML. Solo lógica.
 */
class LoginController
{
    /** Mensaje de error listo para la vista (vacío si no hubo intento o fue exitoso). */
    public string $error  = '';

    /** Valor del correo para repoblar el input tras un error. */
    public string $correo = '';

    private LoginModel $model;

    public function __construct(LoginModel $model)
    {
        $this->model = $model;
    }

    /**
     * Punto de entrada. Solo procesa si la request es POST.
     */
    public function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $this->correo = trim($_POST['correo']  ?? '');
        $password     = $_POST['password']     ?? '';

        if ($this->correo === '' || $password === '') {
            $this->error = 'Por favor completa todos los campos';
            return;
        }

        require_once __DIR__ . '/../core/login_rate_limit.php';
        $ip        = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $rateLimit = new LoginRateLimit($GLOBALS['conexion']);

        // Todo el tramo de verificación se serializa por (ip, correo) para
        // que ráfagas de peticiones paralelas no se salten el límite de intentos.
        $rateLimit->ejecutarConLock($ip, $this->correo, function (bool $lockOk) use ($rateLimit, $ip, $password) {
            if (!$lockOk) {
                $this->error = 'Demasiados intentos simultáneos. Intenta de nuevo en un momento.';
                return;
            }

            if ($rateLimit->estaBloqueada($ip, $this->correo)) {
                $min = max(1, $rateLimit->minutosRestantes($ip, $this->correo));
                $this->error = "Demasiados intentos fallidos. Intenta de nuevo en $min minuto(s).";
                return;
            }

            $usuario = $this->model->getUsuarioPorCorreo($this->correo);

            if (!$usuario || !password_verify($password, $usuario['password'])) {
                $rateLimit->registrarFallo($ip, $this->correo);
                // Mensaje genérico a propósito: no revelar si el correo existe o no.
                $this->error = 'Correo o contraseña incorrectos';
                return;
            }

            // Credenciales correctas — limpiar contador y iniciar sesión
            $rateLimit->limpiar($ip, $this->correo);
            $this->iniciarSesion($usuario);
        });
    }

    // ── Privados ──────────────────────────────────────────────────────────────

    private function iniciarSesion(array $usuario): void
    {
        // Regenerar ID de sesión para prevenir session fixation
        session_regenerate_id(true);

        $_SESSION['usuario'] = $usuario['nombre'];
        $_SESSION['id']      = $usuario['id'];
        $_SESSION['nombre']  = $usuario['nombre'];
        $_SESSION['rol']     = $usuario['rol'] ?? 'trabajador';

        require_once __DIR__ . '/../core/auditoria_helper.php';
        audit_sesion($GLOBALS['conexion'], $usuario['id'], $usuario['nombre'], 'login');

        header('Location: dashboard.php');
        exit;
    }
}
