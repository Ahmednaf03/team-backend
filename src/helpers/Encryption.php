<?php

class Encryption
{
    private static string $cipher = 'aes-256-cbc';
    private static ?string $key = null;
    private static ?int $ivLength = null;

    private static function init(): void
    {
        if (self::$key !== null) {
            return;
        }

        if (empty($_ENV['ENC_KEY'])) {
            throw new Exception('ENC_KEY not configured');
        }

        self::$key = hash('sha256', $_ENV['ENC_KEY'], true);
        self::$ivLength = openssl_cipher_iv_length(self::$cipher);
    }

    public static function encrypt(string $plaintext): string
    {
        self::init();

        $iv = random_bytes(self::$ivLength);

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::$cipher,
            self::$key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return base64_encode($iv . $ciphertext);
    }

    public static function decrypt(string $ciphertext): string
    {
        self::init();

        $data = base64_decode($ciphertext);

        if ($data === false) {
            return '';
        }

        $iv = substr($data, 0, self::$ivLength);
        $cipherRaw = substr($data, self::$ivLength);

        $decrypted = openssl_decrypt(
            $cipherRaw,
            self::$cipher,
            self::$key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $decrypted !== false ? $decrypted : '';
    }

    public static function blindIndex(string $value): string
    {
        if (empty($_ENV['EMAIL_SECRET'])) {
            throw new Exception('EMAIL_SECRET not configured');
        }

        return hash_hmac(
            'sha256',
            strtolower(trim($value)),
            $_ENV['EMAIL_SECRET']
        );
    }
}
