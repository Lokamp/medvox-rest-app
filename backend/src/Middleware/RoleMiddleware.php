<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\Request;
use App\Security\AuthContext;
use App\Services\JWTService;
use RuntimeException;

/**
 * Role-based access control middleware.
 *
 * Extracts the JWT from the "Authorization: Bearer <token>" header, validates
 * it (signature, expiry, issuer and audience) and enforces that the token role
 * is one of the allowed roles. On success the immutable AuthContext is attached
 * to the Request; on failure a JSON error with HTTP 401/403 is emitted and the
 * script is terminated.
 */
final class RoleMiddleware
{
    /**
     * @param JWTService   $jwt          Token service used for validation.
     * @param list<string> $allowedRoles Roles permitted to access the route.
     * @param string       $audience     Expected "aud" claim (per application).
     */
    public function __construct(
        private readonly JWTService $jwt,
        private readonly array $allowedRoles,
        private readonly string $audience,
    ) {
    }

    /**
     * Middleware entry point (invoked by the router).
     */
    public function __invoke(Request $request): bool
    {
        $token = $this->extractBearerToken($request);

        if ($token === null) {
            $this->deny(401, 'Authorization token is missing.');
        }

        try {
            $claims = $this->jwt->decode($token, $this->audience);
        } catch (RuntimeException) {
            $this->deny(401, 'Invalid or expired token.');
        }

        $role = $claims['role'] ?? null;

        if (!is_string($role) || !in_array($role, $this->allowedRoles, true)) {
            $this->deny(403, 'Insufficient permissions.');
        }

        // Attach the immutable identity to the request (no global state).
        $request->setUser(AuthContext::fromClaims($claims));

        return true;
    }

    /**
     * Extract the bearer token from the Authorization header.
     */
    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('authorization') ?? '';

        // Some SAPI configurations do not expose the header in $_SERVER.
        if ($header === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            $header  = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (preg_match('/^Bearer\s+(.+)$/i', trim((string) $header), $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Emit a JSON error response and stop execution.
     */
    private function deny(int $status, string $message): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            ['error' => $message],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        exit;
    }
}
