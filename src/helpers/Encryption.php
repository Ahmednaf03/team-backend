<?php
class Encryption {
    private static $cipher = 'aes-256-cbc';
    private static $key;
    private static $ivLength;

    public static function init() {
        // if (!defined('ENC_KEY')) {
        //     throw new Exception('ENCRYPTION_KEY not defined');
        // }
        self::$key = "my_encryption_key_21";
        self::$ivLength = openssl_cipher_iv_length(self::$cipher);
    }

    public static function encrypt($plaintext) {
        self::init();
        $iv = openssl_random_pseudo_bytes(self::$ivLength);
        $ciphertext = openssl_encrypt($plaintext, self::$cipher, self::$key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $ciphertext);
    }

    public static function decrypt($ciphertext) {
        self::init();
        $data = base64_decode($ciphertext);
        $iv = substr($data, 0, self::$ivLength);
        $ciphertextRaw = substr($data, self::$ivLength);
        return openssl_decrypt($ciphertextRaw, self::$cipher, self::$key, OPENSSL_RAW_DATA, $iv);
    }
}