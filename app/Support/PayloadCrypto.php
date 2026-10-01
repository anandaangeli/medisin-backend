<?php

namespace App\Support;

use RuntimeException;

/**
 * AES-256-CBC, shared key PAYLOAD_KEY (32 bytes).
 * Wire format: base64( iv[16] . ciphertext ). Frontend (crypto-js) and Postman use the same layout.
 */
class PayloadCrypto
{
    public static function encrypt(string $plain): string
    {
        $iv = random_bytes(16);
        $ct = openssl_encrypt($plain, 'aes-256-cbc', self::key(), OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv.$ct);
    }

    public static function decrypt(string $payload): ?string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) <= 16) {
            return null;
        }
        $plain = openssl_decrypt(substr($raw, 16), 'aes-256-cbc', self::key(), OPENSSL_RAW_DATA, substr($raw, 0, 16));

        return $plain === false ? null : $plain;
    }

    private static function key(): string
    {
        $key = (string) config('app.payload_key');
        if (strlen($key) !== 32) {
            throw new RuntimeException('PAYLOAD_KEY must be exactly 32 characters.');
        }

        return $key;
    }
}
