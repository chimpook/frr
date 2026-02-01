<?php

namespace App;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuthenticator
{
    private string $publicKeyPath;
    private ?string $publicKey = null;

    public function __construct(string $publicKeyPath)
    {
        $this->publicKeyPath = $publicKeyPath;
    }

    public function validate(string $token): ?array
    {
        try {
            if ($this->publicKey === null) {
                $this->publicKey = file_get_contents($this->publicKeyPath);
                if ($this->publicKey === false) {
                    error_log("Failed to read public key from: {$this->publicKeyPath}");
                    return null;
                }
            }

            $decoded = JWT::decode($token, new Key($this->publicKey, 'RS256'));

            return [
                'userId' => $decoded->sub ?? null,
                'email' => $decoded->username ?? null,
                'roles' => $decoded->roles ?? [],
            ];
        } catch (\Exception $e) {
            error_log("JWT validation failed: " . $e->getMessage());
            return null;
        }
    }
}
