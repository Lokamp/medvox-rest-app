<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\Request;
use App\Services\RateLimiter;

/**
 * Rate limiting middleware for sensitive endpoints (e.g. login).
 *
 * Applies a per-client-IP sliding window. When the limit is exceeded it
 * responds with HTTP 429 and a Retry-After header, then terminates. On success
 * it stores the counter key on the Request so a successful login can reset it.
 */
final class RateLimitMiddleware
{
    /**
     * @param RateLimiter $limiter Shared rate limiter.
     * @param string      $scope   Logical bucket name (e.g. "login").
     */
    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly string $scope = 'default',
    ) {
    }

    /**
     * Middleware entry point (invoked by the router).
     */
    public function __invoke(Request $request): bool
    {
        $key    = $this->scope . ':' . $request->clientIp;
        $result = $this->limiter->hit($key);

        if ($result['allowed'] === false) {
            header('Retry-After: ' . $result['retry_after']);
            $this->deny(429, 'Too many requests. Please try again later.');
        }

        // Expose the counter key so a successful login can reset it.
        $request->setAttribute('rate_limit_key', $key);

        return true;
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
