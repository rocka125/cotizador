<?php

/**
 * login_rate_limit.php — Límite de intentos de login por (IP, correo).
 *
 * Regla: máximo LOGIN_MAX_INTENTOS intentos fallidos en LOGIN_VENTANA_MINUTOS
 * minutos, contando la combinación IP + correo objetivo. Al superarlo, se
 * bloquea el intento (sin llegar a consultar la contraseña) durante lo que
 * reste de la ventana.
 *
 * Por qué (ip, correo) y no solo ip: si se limitara solo por IP, un login
 * exitoso de CUALQUIER cuenta desde una IP compartida (oficina, NAT) borraba
 * el contador de intentos fallidos que un atacante acumulaba contra OTRA
 * cuenta desde esa misma IP — el conteo se reiniciaba sin que la cuenta
 * atacada hubiera tenido nada que ver. Al contar por par (ip, correo), el
 * login exitoso de un usuario solo limpia su propio contador.
 *
 * Concurrencia: el chequeo de bloqueo y el incremento son operaciones
 * separadas; sin sincronización, varias peticiones en paralelo para el mismo
 * (ip, correo) podrían leer "no bloqueado" antes de que ninguna incremente el
 * contador, saltándose el límite. `ejecutarConLock()` usa GET_LOCK/RELEASE_LOCK
 * de MySQL para serializar ese tramo por (ip, correo), sin afectar a otras
 * IPs/cuentas.
 *
 * Requiere la tabla `login_intentos` (se crea sola si no existe).
 *
 * Uso en LoginController::handle():
 *
 *   require_once __DIR__ . '/../core/login_rate_limit.php';
 *   $rl = new LoginRateLimit($GLOBALS['conexion']);
 *   $rl->ejecutarConLock($ip, $correo, function () use (...) {
 *       if ($rl->estaBloqueada($ip, $correo)) { ...; return; }
 *       if (!password_verify(...)) { $rl->registrarFallo($ip, $correo); ...; return; }
 *       $rl->limpiar($ip, $correo);
 *       ...
 *   });
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
                correo VARCHAR(150) NOT NULL,
                intentos INT UNSIGNED NOT NULL DEFAULT 1,
                primer_intento DATETIME NOT NULL,
                ultimo_intento DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_ip_correo (ip, correo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ');
    }

    /**
     * Serializa el tramo "verificar bloqueo → checar password → registrar
     * resultado" para un mismo (ip, correo), usando un lock con nombre de
     * MySQL. Si no se puede adquirir el lock (contención extrema), se falla
     * cerrado: se trata como bloqueado en vez de arriesgar un bypass.
     *
     * @return mixed lo que devuelva $fn(bool $bloqueadoPorContencion)
     */
    public function ejecutarConLock(string $ip, string $correo, callable $fn)
    {
        $clave = 'login_rl_' . md5($ip . '|' . $correo);

        $stmt = $this->db->prepare('SELECT GET_LOCK(?, 5) AS ok');
        $stmt->bind_param('s', $clave);
        $stmt->execute();
        $adquirido = (bool)($stmt->get_result()->fetch_assoc()['ok'] ?? 0);

        try {
            return $fn($adquirido);
        } finally {
            if ($adquirido) {
                $rel = $this->db->prepare('SELECT RELEASE_LOCK(?)');
                $rel->bind_param('s', $clave);
                $rel->execute();
            }
        }
    }

    /**
     * true si esta combinación (ip, correo) ya alcanzó el máximo de intentos
     * dentro de la ventana.
     */
    public function estaBloqueada(string $ip, string $correo): bool
    {
        // La antigüedad se calcula DENTRO de MySQL (TIMESTAMPDIFF) en vez de
        // comparar time()/strtotime() en PHP: si el timezone del proceso PHP
        // no coincide exactamente con el timezone de sesión de MySQL (fijado
        // en core/db.php), esa comparación en PHP puede dar una diferencia de
        // varias horas y hacer creer que la ventana ya expiró, anulando el
        // límite de intentos por completo.
        $stmt = $this->db->prepare(
            'SELECT intentos, TIMESTAMPDIFF(SECOND, primer_intento, NOW()) AS antiguedad_seg
             FROM login_intentos WHERE ip = ? AND correo = ? LIMIT 1'
        );
        $stmt->bind_param('ss', $ip, $correo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            return false;
        }

        if ((int)$row['antiguedad_seg'] > (LOGIN_VENTANA_MINUTOS * 60)) {
            // La ventana ya pasó: reiniciamos silenciosamente.
            $this->limpiar($ip, $correo);
            return false;
        }

        return (int)$row['intentos'] >= LOGIN_MAX_INTENTOS;
    }

    /**
     * Minutos restantes de bloqueo (para mostrar al usuario). 0 si no aplica.
     */
    public function minutosRestantes(string $ip, string $correo): int
    {
        $ventanaSeg = LOGIN_VENTANA_MINUTOS * 60;
        $stmt = $this->db->prepare(
            'SELECT GREATEST(0, ? - TIMESTAMPDIFF(SECOND, primer_intento, NOW())) AS restante_seg
             FROM login_intentos WHERE ip = ? AND correo = ? LIMIT 1'
        );
        $stmt->bind_param('iss', $ventanaSeg, $ip, $correo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) return 0;

        $restante = (int)$row['restante_seg'];
        return $restante > 0 ? (int)ceil($restante / 60) : 0;
    }

    /**
     * Registra un intento fallido. Incrementa el contador si ya existía
     * un registro dentro de la ventana; si no, crea uno nuevo.
     */
    public function registrarFallo(string $ip, string $correo): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO login_intentos (ip, correo, intentos, primer_intento, ultimo_intento)
            VALUES (?, ?, 1, NOW(), NOW())
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
        $stmt->bind_param('ssii', $ip, $correo, $ventanaSegundos, $ventanaSegundos);
        $stmt->execute();
    }

    /**
     * Limpia el registro de intentos de una combinación (ip, correo)
     * (login exitoso o ventana expirada). No afecta otras cuentas que
     * compartan la misma IP.
     */
    public function limpiar(string $ip, string $correo): void
    {
        $stmt = $this->db->prepare('DELETE FROM login_intentos WHERE ip = ? AND correo = ?');
        $stmt->bind_param('ss', $ip, $correo);
        $stmt->execute();
    }
}
