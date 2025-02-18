<?php
require_once __DIR__ . '/security.php';

class JWTAuth {
    private $secretKey;
    private $algorithm = 'HS256';
    private $tokenLifetime = 3600; // 1 saat

    public function __construct() {
        $this->secretKey = getenv('JWT_SECRET_KEY') ?: 'your-secret-key-here';
    }

    public function generateToken($userId) {
        $issuedAt = time();
        $expire = $issuedAt + $this->tokenLifetime;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'user_id' => $userId
        ];

        return $this->encode($payload);
    }

    public function validateToken($token) {
        try {
            $payload = $this->decode($token);
            
            if ($payload->exp < time()) {
                return false;
            }
            
            return $payload->user_id;
        } catch (Exception $e) {
            return false;
        }
    }

    private function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => $this->algorithm]);
        $payload = json_encode($payload);

        $base64Header = $this->base64UrlEncode($header);
        $base64Payload = $this->base64UrlEncode($payload);

        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", $this->secretKey, true);
        $base64Signature = $this->base64UrlEncode($signature);

        return "$base64Header.$base64Payload.$base64Signature";
    }

    private function decode($token) {
        list($base64Header, $base64Payload, $signature) = explode('.', $token);

        $signature2 = hash_hmac('sha256', "$base64Header.$base64Payload", $this->secretKey, true);
        $base64Signature2 = $this->base64UrlEncode($signature2);

        if ($signature !== $base64Signature2) {
            throw new Exception('Invalid signature');
        }

        return json_decode($this->base64UrlDecode($base64Payload));
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
} 