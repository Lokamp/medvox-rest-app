<?php

declare(strict_types=1);

namespace App\Http;

use App\Security\AuthContext;

/**
 * Request-scoped container.
 *
 * Holds immutable request data (method, path, query, headers, body, route
 * params) plus a mutable, request-scoped authentication context and a small
 * attribute bag. It replaces global state ($GLOBALS) with an explicit object
 * that is passed to middleware and controllers.
 */
final class Request
{
    private ?AuthContext $user = null;

    /** @var array<string, mixed> */
    private array $attributes = [];

    /**
     * @param array<string, string> $query    Query string parameters.
     * @param array<string, string> $headers  HTTP headers (lower-cased names).
     * @param array<string, mixed>  $body     Parsed request body.
     * @param array<string, string> $params   Route parameters.
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $headers = [],
        public readonly array $body = [],
        public readonly array $params = [],
        public readonly string $clientIp = 'unknown',
    ) {
    }

    /**
     * Attach the authenticated user (called by the role middleware).
     */
    public function setUser(AuthContext $user): void
    {
        $this->user = $user;
    }

    /**
     * The authenticated user, or null for public endpoints.
     */
    public function user(): ?AuthContext
    {
        return $this->user;
    }

    public function isAuthenticated(): bool
    {
        return $this->user !== null;
    }

    /**
     * Store a request-scoped attribute (e.g. the rate-limit counter key).
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Read a request-scoped attribute.
     */
    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Read a header by name (case-insensitive).
     */
    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    /**
     * Read a value from the parsed request body.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }
}
