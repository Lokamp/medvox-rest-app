<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

/**
 * Minimal, dependency-free JWT (JSON Web Token) service.
 *
 * Implements the compact JWS serialization with the HMAC-SHA256 algorithm
 * (alg = "HS256") using only the PHP standard library. No third-party code.
 *
 * Security notes:
 *  - The signature is verified with hash_equals() to prevent timing attacks.
 *  - The "alg" header is pinned to HS256, so the "alg: none" attack is impossible.
 *  - exp / nbf / iss claims are validated on every decode().
 */
final class JWTService
{
    private const ALGORITHM = 'HS256';

    /**
     * @param string $secret HMAC signing key (must be at least 32 bytes).
     * @param int    $ttl    Default token lifetime in seconds.
     * @param string $issuer Expected "iss" claim value.
     */
    public function __construct(
        private readonly string $secret,
        private readonly int $ttl = 3600,
        private readonly string $issuer = 'medvox',
    ) {
        if (strlen($this->secret) < 32) {
            throw new InvalidArgumentException(
                'JWT secret must be at least 32 bytes long. Set a strong JWT_SECRET in the environment.'
            );
        }

        if ($this->ttl <= 0) {
            throw new InvalidArgumentException('JWT TTL must be a positive number of seconds.');
        }
    }

    /**
     * Encode a payload into a signed JWT.
     *
     * The payload is expected to contain the user identity, e.g.:
     *   ['id' => 100001, 'username' => 'john@example.com', 'role' => 'user']
     *
     * Registered claims (iss, iat, nbf, exp) are added automatically.
     *
     * @param array<string, mixed> $payload
     * @param int|null             $ttl     Optional per-token lifetime override.
     */
    #[\NoDiscard]
    public function encode(array $payload, ?int $ttl = null): string
    {
        $now = time();
        $ttl ??= $this->ttl;

        $claims = array_merge($payload, [
            'iss' => $this->issuer,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
        ]);

        $header = [
            'alg' => self::ALGORITHM,
            'typ' => 'JWT',
        ];

        $segments = [
            $this->base64UrlEncode($this->jsonEncode($header)),
            $this->base64UrlEncode($this->jsonEncode($claims)),
        ];

        $signingInput = implode('.', $segments);
        $signature    = hash_hmac('sha256', $signingInput, $this->secret, true);

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Decode and fully validate a JWT.
     *
     * @return array<string, mixed> The validated claims.
     *
     * @throws RuntimeException When the token is malformed, has an invalid
     *                          signature, is expired, not yet valid, has an
     *                          unexpected issuer, or a wrong audience.
     *
     * @param string|null $audience When provided, the "aud" claim must match.
     */
    #[\NoDiscard]
    public function decode(string $token, ?string $audience = null): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Malformed token: expected three segments.');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // --- 1. Verify the header and pin the algorithm (blocks "alg: none"). ---
        $header = $this->jsonDecode($this->base64UrlDecode($headerB64));

        if (($header['alg'] ?? null) !== self::ALGORITHM) {
            throw new RuntimeException('Unsupported or missing signing algorithm.');
        }

        // --- 2. Verify the signature in constant time. ---
        $expectedSignature = hash_hmac('sha256', $headerB64 . '.' . $payloadB64, $this->secret, true);
        $providedSignature = $this->base64UrlDecode($signatureB64);

        if (!hash_equals($expectedSignature, $providedSignature)) {
            throw new RuntimeException('Invalid token signature.');
        }

        // --- 3. Decode and validate the claims. ---
        $claims = $this->jsonDecode($this->base64UrlDecode($payloadB64));
        $now    = time();

        if (isset($claims['nbf']) && $now < (int) $claims['nbf']) {
            throw new RuntimeException('Token is not valid yet (nbf).');
        }

        if (!isset($claims['exp']) || $now >= (int) $claims['exp']) {
            throw new RuntimeException('Token has expired.');
        }

        if (isset($claims['iss']) && $claims['iss'] !== $this->issuer) {
            throw new RuntimeException('Invalid token issuer.');
        }

        if ($audience !== null && (!isset($claims['aud']) || $claims['aud'] !== $audience)) {
            throw new RuntimeException('Invalid token audience.');
        }

        return $claims;
    }

    /**
     * Non-throwing validation helper.
     */
    public function validate(string $token, ?string $audience = null): bool
    {
        try {
            // Only the validity of the token matters here, so the decoded
            // claims are intentionally ignored. The (void) cast satisfies
            // the #[NoDiscard] contract of decode().
            (void) $this->decode($token, $audience);

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws RuntimeException
     */
    private function jsonEncode(array $data): string
    {
        try {
            return json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            throw new RuntimeException('Unable to encode JWT segment: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    private function jsonDecode(string $json): array
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Unable to decode JWT segment: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($data)) {
            throw new RuntimeException('JWT segment is not a JSON object.');
        }

        return $data;
    }

    /**
     * Base64url encoding without padding (RFC 7515, Appendix C).
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64url decoding with padding restoration.
     *
     * @throws RuntimeException
     */
    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;

        if ($remainder !== 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid base64url encoding.');
        }

        return $decoded;
    }
}
