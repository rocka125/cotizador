<?php

/**
 * login_rate_limit.php — Límite de intentos de login por IP.
 *
 * Regla: máximo LOGIN_MAX_INTENTOS intentos fallidos en LOGIN_VENTANA_MINUTOS
 * minutos, por IP. Al superarlo, se bloquea el intento (sin llegar a
 * consultar la contraseña) durante lo que reste de la ventana.
 *
 * Requiere la tabla `login_intentos` (ver abajo, se crea sola si no existe).
 *
 * Uso en LoginController::handle(), antes de verificar la contraseña:
 *
 *   require_once __DIR__ . '/../core/login_rate_limit.php';
 *   $rl = new LoginRateLimit($GLOBALS['conexion']);
 *   if ($rl->estaBloqueada($ip)) {
 *       $this->error = 'Demasiados intentos. Intenta de nuevo en unos minutos.';
 *       return;
 *   }
 *   ...
 *   if (!password_verify(...)) {
 *       $rl->registrarFallo($ip);
 *       ...
 *   }
 *   $rl->limpiar($ip); // en login exitoso
 */

define('LOGIN_MAX_INTENTOS', 5);
define('LOGIN_VENTANA_MINUTOS', 15);

class LoginRateLimit
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
        $this->asegurarTabla();
    }

    private function asegurarTabla(): void
    {
        $this->db->query('
            CREATE TABLE IF NOT EXISTS login_intentos (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                ip VARCHAR(45) NOT NULL,
                intentos INT UNSIGNED NOT NULL DEFAULT 1,
                primer_intento DATETIME NOT NULL,
                ultimo_intento DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_ip (ip)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ');
    }

    /**
     * true si esta IP ya alcanzó el máximo de intentos dentro de la ventana.
     */
    public function estaBloqueada(string $ip): bool
    {
        $stmt = $this->db->prepare(
            'SELECT intentos, primer_intento FROM login_intentos WHERE ip = ? LIMIT 1'
        );
        $stmt->bind_param('s', $ip);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            return false;
        }

        $ventanaExpiro = (time() - strtotime($row['primer_intento'])) > (LOGIN_VENTANA_MINUTOS * 60);
        if ($ventanaExpiro) {
            // La ventana ya pasó: reiniciamos silenciosamente.
            $this->limpiar($ip);
            return false;
        }

        return (int)$row['intentos'] >= LOGIN_MAX_INTENTOS;
    }

    /**
     * Minutos restantes de bloqueo (para mostrar al usuario). 0 si no aplica.
     */
    public function minutosRestantes(string $ip): int
    {
        $stmt = $this->db->prepare(
            'SELECT primer_intento FROM login_intentos WHERE ip = ? LIMIT 1'
        );
        $stmt->bind_param('s', $ip);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) return 0;

        $restante = (LOGIN_VENTANA_MINUTOS * 60) - (time() - strtotime($row['primer_intento']));
        return $restante > 0 ? (int)ceil($restante / 60) : 0;
    }

    /**
     * Registra un intento fallido. Incrementa el contador si ya existía
     * un registro dentro de la ventana; si no, crea uno nuevo.
     */
    public function registrarFallo(string $ip): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO login_intentos (ip, intentos, primer_intento, ultimo_intento)
            VALUES (?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                intentos = IF(
                    TIMESTAMPDIFF(SECOND, primer_intento, NOW()) > ?,
                    1,
                    intentos + 1
                ),
                primer_intento = IF(
                    TIMESTAMPDIFF(SECOND, primer_intento, NOW()) > ?,
                    NOW(),
                    primer_intento
                ),
                ultimo_intento = NOW()
        ');
        $ventanaSegundos = LOGIN_VENTANA_MINUTOS * 60;
        $stmt->bind_param('sii', $ip, $ventanaSegundos, $ventanaSegundos);
        $stmt->execute();
    }

    /**
     * Limpia el registro de intentos de una IP (login exitoso o ventana expirada).
     */
    public function limpiar(string $ip): void
    {
        $stmt = $this->db->prepare('DELETE FROM login_intentos WHERE ip = ?');
        $stmt->bind_param('s', $ip);
        $stmt->execute();
    }
}
