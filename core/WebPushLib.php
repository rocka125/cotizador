<?php
/**
 * WebPushLib.php — Librería Web Push mínima (sin Composer)
 *
 * Implementa el protocolo RFC 8030 (Web Push) con cifrado
 * ECDH + AES-128-GCM (RFC 8291) y autenticación VAPID (RFC 8292).
 *
 * Compatible con Chrome, Firefox, Safari (iOS 16.4+), Edge y Samsung Browser.
 */

class WebPushLib
{
    private string $publicKey;
    private string $privateKey;
    private string $subject;

    public function __construct(string $publicKey, string $privateKey, string $subject)
    {
        $this->publicKey  = $publicKey;
        $this->privateKey = $privateKey;
        $this->subject    = $subject;
    }

    /**
     * Enviar una notificación push a una suscripción.
     *
     * @param array  $subscription  ['endpoint'=>'...', 'p256dh'=>'...', 'auth'=>'...']
     * @param string $payload       JSON string con la notificación
     * @param int    $ttl           Tiempo de vida en segundos (default: 12 horas)
     * @return array ['ok'=>bool, 'status'=>int, 'error'=>string|null]
     */
    public function send(array $subscription, string $payload, int $ttl = 43200): array
    {
        try {
            $encrypted = $this->encrypt($payload, $subscription['p256dh'], $subscription['auth']);
            $vapidHeaders = $this->buildVapidHeaders($subscription['endpoint']);

            $headers = array_merge($vapidHeaders, [
                'Content-Type: application/octet-stream',
                'Content-Encoding: aes128gcm',
                'TTL: ' . $ttl,
                'Content-Length: ' . strlen($encrypted),
            ]);

            $ch = curl_init($subscription['endpoint']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $encrypted,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
            ]);

            $body   = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err    = curl_error($ch);
            curl_close($ch);

            if ($err) {
                return ['ok' => false, 'status' => 0, 'error' => $err];
            }

            // 201 = enviado, 202 = aceptado (Firefox)
            $ok = in_array($status, [200, 201, 202]);
            return ['ok' => $ok, 'status' => $status, 'error' => $ok ? null : $body];

        } catch (Throwable $e) {
            return ['ok' => false, 'status' => 0, 'error' => $e->getMessage()];
        }
    }

    // ── Cifrado AES-128-GCM (RFC 8291) ───────────────────────────────────────

    private function encrypt(string $payload, string $p256dh, string $auth): string
    {
        // Decodificar claves del suscriptor
        $userPublicKey = $this->base64urlDecode($p256dh);
        $userAuth      = $this->base64urlDecode($auth);

        // Generar par de claves efímeras para este mensaje
        $localKey = openssl_pkey_new([
            'curve_name'       => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
        $localKeyDetails = openssl_pkey_get_details($localKey);

        // Clave pública del servidor en formato uncompressed (04 || x || y)
        $localPublicKey = $this->ecKeyToUncompressed($localKeyDetails);

        // ECDH: secreto compartido
        $userKey = openssl_pkey_get_public([
            'curve_name' => 'prime256v1',
            'x'          => substr($userPublicKey, 1, 32),
            'y'          => substr($userPublicKey, 33, 32),
        ]);
        // Re-crear correctamente con EC key
        $userKeyPem = $this->uncompressedToEcPem($userPublicKey);
        $userKeyRes = openssl_pkey_get_public($userKeyPem);
        openssl_dh_compute_key($sharedSecret, $localKey, $userKeyRes);

        // HKDF para derivar claves
        $salt        = random_bytes(16);
        $prk         = $this->hkdf($userAuth, $sharedSecret, "Content-Encoding: auth\0", 32);
        $cek         = $this->hkdf($salt, $prk, $this->buildInfo('aesgcm', $userPublicKey, $localPublicKey), 16);
        $nonce       = $this->hkdf($salt, $prk, $this->buildInfo('nonce', $userPublicKey, $localPublicKey), 12);

        // Cifrar con AES-128-GCM
        $tag        = '';
        $ciphertext = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);

        // Construir registro aes128gcm (RFC 8188)
        $recordSize = pack('N', 4096);
        $keyIdLen   = chr(strlen($localPublicKey));
        $header     = $salt . $recordSize . $keyIdLen . $localPublicKey;

        return $header . $ciphertext . $tag;
    }

    private function ecKeyToUncompressed(array $details): string
    {
        $x = str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);
        return "\x04" . $x . $y;
    }

    private function uncompressedToEcPem(string $uncompressed): string
    {
        // Construir SubjectPublicKeyInfo DER para prime256v1
        $oid   = "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07"; // OID prime256v1
        $algId = "\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01" . $oid;
        $bits  = "\x03" . chr(strlen($uncompressed) + 1) . "\x00" . $uncompressed;
        $spki  = "\x30" . chr(strlen($algId) + strlen($bits)) . $algId . $bits;
        return "-----BEGIN PUBLIC KEY-----\n" .
               chunk_split(base64_encode($spki), 64, "\n") .
               "-----END PUBLIC KEY-----\n";
    }

    private function buildInfo(string $type, string $userKey, string $serverKey): string
    {
        return "Content-Encoding: {$type}\0P-256\0" .
               pack('n', strlen($userKey))   . $userKey .
               pack('n', strlen($serverKey)) . $serverKey;
    }

    private function hkdf(string $salt, string $ikm, string $info, int $length): string
    {
        $prk = hash_hmac('sha256', $ikm, $salt, true);
        $t   = '';
        $okm = '';
        for ($i = 1; strlen($okm) < $length; $i++) {
            $t    = hash_hmac('sha256', $t . $info . chr($i), $prk, true);
            $okm .= $t;
        }
        return substr($okm, 0, $length);
    }

    // ── VAPID (RFC 8292) ──────────────────────────────────────────────────────

    private function buildVapidHeaders(string $endpoint): array
    {
        $parsed    = parse_url($endpoint);
        $audience  = $parsed['scheme'] . '://' . $parsed['host'];
        $expiry    = time() + 12 * 3600;

        $header  = $this->base64urlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $payload = $this->base64urlEncode(json_encode([
            'aud' => $audience,
            'exp' => $expiry,
            'sub' => $this->subject,
        ]));

        $signingInput = "{$header}.{$payload}";
        $privateKey   = $this->loadPrivateKey();

        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        // Convertir DER a raw R||S (64 bytes)
        $signature = $this->derToRaw($signature);

        $jwt = "{$signingInput}." . $this->base64urlEncode($signature);
        $pub = $this->base64urlEncode(hex2bin(
            openssl_pkey_get_details($privateKey)['key']
        ));

        // Obtener clave pública como bytes uncompressed
        $details = openssl_pkey_get_details($privateKey);
        $x = str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);
        $pubPoint = "\x04" . $x . $y;
        $pubB64 = $this->base64urlEncode($pubPoint);

        return [
            "Authorization: vapid t={$jwt},k={$this->publicKey}",
        ];
    }

    private function loadPrivateKey()
    {
        // Convertir clave privada base64url raw a PEM EC
        $rawPriv  = $this->base64urlDecode($this->privateKey);
        $rawPub   = $this->base64urlDecode($this->publicKey);

        // Construir ECPrivateKey DER
        $oidP256  = "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
        $params   = "\xa0\x0a\x30\x08" . $oidP256;
        $privOct  = "\x04" . chr(strlen($rawPriv)) . $rawPriv;
        $pubBits  = "\xa1" . chr(strlen($rawPub) + 2) . "\x03" . chr(strlen($rawPub) + 1) . "\x00" . $rawPub;

        $seq = "\x30\x77\x02\x01\x01" . $privOct . $params . $pubBits;

        $pem = "-----BEGIN EC PRIVATE KEY-----\n" .
               chunk_split(base64_encode($seq), 64, "\n") .
               "-----END EC PRIVATE KEY-----\n";

        return openssl_pkey_get_private($pem);
    }

    private function derToRaw(string $der): string
    {
        // Parsear DER ECDSA signature (SEQUENCE { INTEGER r, INTEGER s })
        $offset = 2; // SEQUENCE tag + length
        $rLen   = ord($der[$offset + 1]);
        $r      = substr($der, $offset + 2, $rLen);
        $offset += 2 + $rLen;
        $sLen   = ord($der[$offset + 1]);
        $s      = substr($der, $offset + 2, $sLen);

        // Quitar padding de signo si existe
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        return str_pad($r, 32, "\x00", STR_PAD_LEFT) .
               str_pad($s, 32, "\x00", STR_PAD_LEFT);
    }

    // ── Utilidades ────────────────────────────────────────────────────────────

    private function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
