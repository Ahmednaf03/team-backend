<?php

class JWT
{
    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data)
    {
        $remainder = strlen($data) % 4;

        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function generateAccessToken(array $payload): string
    {
        $header = self::base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT'
        ]));

        $payload['exp'] = time() + (int)$_ENV['JWT_EXPIRY'];

        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            "$header.$payloadEncoded",
            $_ENV['JWT_SECRET'],
            true
        );

        $signatureEncoded = self::base64UrlEncode($signature);

        return "$header.$payloadEncoded.$signatureEncoded";
    }
// invalid token not verifying
    public static function verify(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new Exception('Invalid token structure');
        }

        [$header, $payload, $signature] = $parts;
//change to generated signature
        $expected = hash_hmac(
            'sha256',
            "$header.$payload",
            $_ENV['JWT_SECRET'],
            true
        );
// cookie manager issue 
        $decodedSignature = self::base64UrlDecode($signature);

        if (!hash_equals($expected, $decodedSignature)) {
            throw new Exception('Invalid token signature');
        }

        $decodedPayload = json_decode(
            self::base64UrlDecode($payload),
            true
        );

        if (!$decodedPayload || $decodedPayload['exp'] < time()) {
            throw new Exception('Token expired');
        }

        return $decodedPayload;
    }
}
