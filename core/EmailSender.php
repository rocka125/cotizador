<?php
/**
 * EmailSender — Envío de correo vía SMTP nativo (sin librerías externas).
 *
 * Soporta:
 *   - SSL (puerto 465) y STARTTLS (puerto 587)
 *   - Cuerpo HTML con fallback plain-text
 *   - Adjuntos (PDF u otros archivos en memoria)
 *   - Múltiples destinatarios / CC
 *
 * Uso:
 *   $mailer = new EmailSender();
 *   $mailer->setTo('cliente@empresa.com', 'Nombre')
 *          ->setSubject('Cotización COT-2026-0001')
 *          ->setBodyHtml($html)
 *          ->send();
 */
class EmailSender
{
    private string  $toAddress   = '';
    private string  $toName      = '';
    private array   $ccList      = [];
    private string  $subject     = '';
    private string  $bodyHtml    = '';
    private string  $bodyPlain   = '';
    private array   $attachments = [];
    private ?string $lastError   = null;

    public function __construct()
    {
        if (!defined('MAIL_SMTP_HOST')) {
            require_once __DIR__ . '/email_config.php';
        }
    }

    // ── Interfaz fluida ───────────────────────────────────────────────────

    public function setTo(string $address, string $name = ''): self
    {
        $this->toAddress = $address;
        $this->toName    = $name;
        return $this;
    }

    public function addCc(string $address, string $name = ''): self
    {
        $this->ccList[] = ['address' => $address, 'name' => $name];
        return $this;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function setBodyHtml(string $html, string $plain = ''): self
    {
        $this->bodyHtml  = $html;
        $this->bodyPlain = $plain ?: strip_tags($html);
        return $this;
    }

    /** Adjunta bytes en memoria (ej. PDF generado con ob_get_clean). */
    public function attachString(string $data, string $filename, string $mime = 'application/octet-stream'): self
    {
        $this->attachments[] = ['data' => $data, 'filename' => $filename, 'mime' => $mime];
        return $this;
    }

    /** Adjunta un archivo desde disco. */
    public function attachFile(string $path, string $filename = '', string $mime = ''): self
    {
        if (!file_exists($path)) return $this;
        $data     = file_get_contents($path);
        $filename = $filename ?: basename($path);
        $mime     = $mime ?: (mime_content_type($path) ?: 'application/octet-stream');
        return $this->attachString($data, $filename, $mime);
    }

    public function getLastError(): ?string { return $this->lastError; }

    // ── Envío ─────────────────────────────────────────────────────────────

    public function send(): bool
    {
        $this->lastError = null;
        if (!$this->toAddress || !$this->bodyHtml) {
            $this->lastError = 'Faltan destinatario o cuerpo del correo.';
            return false;
        }
        try {
            return $this->smtpSend($this->buildMime());
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log('[EmailSender] ' . $e->getMessage());
            return false;
        }
    }

    // ── Construcción MIME ─────────────────────────────────────────────────

    private function buildMime(): array
    {
        $boundary = 'bound_' . bin2hex(random_bytes(12));
        $altBound = 'alt_'   . bin2hex(random_bytes(12));

        $from    = $this->formatAddress(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $to      = $this->formatAddress($this->toAddress, $this->toName);
        $subject = '=?UTF-8?B?' . base64_encode($this->subject) . '?=';
        $msgId   = '<' . time() . '.' . bin2hex(random_bytes(8)) . '@' . MAIL_SMTP_HOST . '>';

        $headers  = "Date: " . date('r') . "\r\n";
        $headers .= "From: {$from}\r\n";
        $headers .= "To: {$to}\r\n";

        if (!empty($this->ccList)) {
            $cc = implode(', ', array_map(
                fn($c) => $this->formatAddress($c['address'], $c['name']),
                $this->ccList
            ));
            $headers .= "Cc: {$cc}\r\n";
        }

        $headers .= "Subject: {$subject}\r\n";
        $headers .= "Message-ID: {$msgId}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "X-Mailer: Fortress8-Cotizador/1.0\r\n";

        if (!empty($this->attachments)) {
            $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
            $body  = "--{$boundary}\r\n";
            $body .= "Content-Type: multipart/alternative; boundary=\"{$altBound}\"\r\n\r\n";
            $body .= $this->buildAlternativeParts($altBound);
            $body .= "\r\n--{$boundary}\r\n";
            $body .= $this->buildAttachmentParts($boundary);
            $body .= "--{$boundary}--\r\n";
        } else {
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $body  = $this->buildAlternativeParts($boundary);
            $body .= "--{$boundary}--\r\n";
        }

        return ['headers' => $headers, 'body' => $body];
    }

    private function buildAlternativeParts(string $b): string
    {
        $out  = "--{$b}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $out .= chunk_split(base64_encode($this->bodyPlain)) . "\r\n";
        $out .= "--{$b}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $out .= chunk_split(base64_encode($this->bodyHtml)) . "\r\n";
        return $out;
    }

    private function buildAttachmentParts(string $b): string
    {
        $out = '';
        foreach ($this->attachments as $att) {
            $name  = '=?UTF-8?B?' . base64_encode($att['filename']) . '?=';
            $out  .= "Content-Type: {$att['mime']}; name=\"{$name}\"\r\n";
            $out .= "Content-Disposition: attachment; filename=\"{$name}\"\r\n";
            $out .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $out .= chunk_split(base64_encode($att['data'])) . "\r\n";
            $out .= "--{$b}\r\n";
        }
        return $out;
    }

    private function formatAddress(string $addr, string $name = ''): string
    {
        return $name
            ? '=?UTF-8?B?' . base64_encode($name) . "?= <{$addr}>"
            : "<{$addr}>";
    }

    // ── SMTP raw ──────────────────────────────────────────────────────────

    private function smtpSend(array $message): bool
    {
        $prefix = (MAIL_SMTP_SECURE === 'ssl') ? 'ssl://' : '';
        $sock   = @fsockopen($prefix . MAIL_SMTP_HOST, MAIL_SMTP_PORT, $errno, $errstr, MAIL_TIMEOUT);

        if (!$sock) {
            throw new RuntimeException("No se pudo conectar a " . MAIL_SMTP_HOST . ":" . MAIL_SMTP_PORT . " — {$errstr} ({$errno})");
        }

        stream_set_timeout($sock, MAIL_TIMEOUT);
        $this->read($sock, '220');

        $this->write($sock, "EHLO " . (gethostname() ?: 'localhost'));
        $this->read($sock, '250');

        if (MAIL_SMTP_SECURE === 'tls') {
            $this->write($sock, 'STARTTLS');
            $this->read($sock, '220');
            if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('STARTTLS falló — verifica que OpenSSL esté habilitado en PHP');
            }
            $this->write($sock, "EHLO " . (gethostname() ?: 'localhost'));
            $this->read($sock, '250');
        }

        $this->write($sock, 'AUTH LOGIN');
        $this->read($sock, '334');
        $this->write($sock, base64_encode(MAIL_SMTP_USER));
        $this->read($sock, '334');
        $this->write($sock, base64_encode(MAIL_SMTP_PASS));
        $this->read($sock, '235');

        $this->write($sock, 'MAIL FROM:<' . MAIL_FROM_ADDRESS . '>');
        $this->read($sock, '250');

        $rcpts = array_merge([$this->toAddress], array_column($this->ccList, 'address'));
        foreach ($rcpts as $rcpt) {
            $this->write($sock, "RCPT TO:<{$rcpt}>");
            $this->read($sock, '250');
        }

        $this->write($sock, 'DATA');
        $this->read($sock, '354');

        $raw = $message['headers'] . "\r\n" . $message['body'];
        $raw = str_replace("\r\n.", "\r\n..", $raw);   // byte-stuffing
        fwrite($sock, $raw . "\r\n.\r\n");
        $this->read($sock, '250');

        $this->write($sock, 'QUIT');
        fclose($sock);
        return true;
    }

    private function write($sock, string $cmd): void
    {
        if (MAIL_DEBUG) error_log('[SMTP>>>] ' . $cmd);
        fwrite($sock, $cmd . "\r\n");
    }

    private function read($sock, string $expected): string
    {
        $response = '';
        while ($line = fgets($sock, 512)) {
            if (MAIL_DEBUG) error_log('[SMTP<<<] ' . trim($line));
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        $code = substr($response, 0, 3);
        if ($code !== $expected) {
            throw new RuntimeException("SMTP — esperado {$expected}, recibido: " . trim($response));
        }
        return $response;
    }
}