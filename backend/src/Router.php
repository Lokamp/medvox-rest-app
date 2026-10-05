<?php

declare(strict_types=1);

namespace App;

use App\Http\Request;
use PDO;
use Throwable;
use Uri\Rfc3986\Uri;

/**
 * Lightweight REST router.
 *
 * Supports:
 *  - Route registration by HTTP method + URL pattern (with {param} placeholders).
 *  - A chain of custom middleware callables (global and per-route).
 *  - Dispatching the current request to a controller action.
 *
 * The router builds a Request object from the environment and passes it to
 * middleware and controllers, so no global state is used. It relies on the
 * PHP 8.5 built-in RFC 3986 URI parser instead of the legacy parse_url().
 */
final class Router
{
    /**
     * @var list<array{
     *     method: string,
     *     pattern: string,
     *     regex: string,
     *     params: list<string>,
     *     controller: class-string,
     *     action: string,
     *     middleware: list<callable>
     * }>
     */
    private array $routes = [];

    /** @var list<callable> */
    private array $globalMiddleware = [];

    /**
     * @param PDO                  $pdo      Shared database connection.
     * @param array<string, mixed> $config   Application configuration.
     * @param array<string, mixed> $services Shared services injected into controllers.
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly array $config = [],
        private readonly array $services = [],
    ) {
    }

    /**
     * Register a global middleware that runs before every matched route.
     *
     * A middleware is any callable with the signature:
     *   function (Request $request): bool
     * Returning false stops the chain (the middleware is expected to have
     * already produced the response).
     */
    public function use(callable $middleware): self
    {
        $this->globalMiddleware[] = $middleware;

        return $this;
    }

    /**
     * Register a route.
     *
     * @param string         $method     HTTP method (GET, POST, ...).
     * @param string         $path       URL pattern, e.g. "/api/v1/main/account/{id}".
     * @param class-string   $controller Fully qualified controller class name.
     * @param string         $action     Controller method to invoke.
     * @param list<callable> $middleware Per-route middleware chain.
     */
    public function add(
        string $method,
        string $path,
        string $controller,
        string $action,
        array $middleware = [],
    ): self {
        $params = [];
        $regex  = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static function (array $matches) use (&$params): string {
                $params[] = $matches[1];

                return '([^/]+)';
            },
            $path
        );

        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $path,
            'regex'      => '#^' . $regex . '$#',
            'params'     => $params,
            'controller' => $controller,
            'action'     => $action,
            'middleware' => $middleware,
        ];

        return $this;
    }

    /** Convenience wrapper for GET routes. */
    public function get(string $path, string $controller, string $action, array $middleware = []): self
    {
        return $this->add('GET', $path, $controller, $action, $middleware);
    }

    /** Convenience wrapper for POST routes. */
    public function post(string $path, string $controller, string $action, array $middleware = []): self
    {
        return $this->add('POST', $path, $controller, $action, $middleware);
    }

    /** Convenience wrapper for PUT routes. */
    public function put(string $path, string $controller, string $action, array $middleware = []): self
    {
        return $this->add('PUT', $path, $controller, $action, $middleware);
    }

    /** Convenience wrapper for PATCH routes. */
    public function patch(string $path, string $controller, string $action, array $middleware = []): self
    {
        return $this->add('PATCH', $path, $controller, $action, $middleware);
    }

    /** Convenience wrapper for DELETE routes. */
    public function delete(string $path, string $controller, string $action, array $middleware = []): self
    {
        return $this->add('DELETE', $path, $controller, $action, $middleware);
    }

    /**
     * Match the current request against registered routes and invoke the handler.
     *
     * @param string|null $method HTTP method; defaults to the current request.
     * @param string|null $uri    Request URI; defaults to the current request.
     */
    public function dispatch(?string $method = null, ?string $uri = null): void
    {
        $method = strtoupper($method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path   = $this->extractPath($uri ?? ($_SERVER['REQUEST_URI'] ?? '/'));

        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }

            // The path matched; remember it to distinguish 404 from 405.
            $pathMatched = true;

            if ($route['method'] !== $method) {
                continue;
            }

            array_shift($matches);

            $params = [];
            foreach ($route['params'] as $index => $name) {
                $params[$name] = $matches[$index] ?? null;
            }

            $request = $this->createRequest($method, $path, $params);

            // --- Middleware chain: global first, then per-route. ---
            foreach ([...$this->globalMiddleware, ...$route['middleware']] as $middleware) {
                if ($middleware($request) === false) {
                    return; // Middleware already produced the response.
                }
            }

            $this->invoke($route['controller'], $route['action'], $request);

            return;
        }

        if ($pathMatched) {
            $this->jsonError(405, 'Method Not Allowed');
        } else {
            $this->jsonError(404, 'Not Found');
        }
    }

    /**
     * Build a Request object from the current environment.
     *
     * @param array<string, string> $params Route parameters.
     */
    private function createRequest(string $method, string $path, array $params): Request
    {
        return new Request(
            method: $method,
            path: $path,
            query: $_GET,
            headers: $this->collectHeaders(),
            body: $this->parseBody(),
            params: $params,
            clientIp: (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
        );
    }

    /**
     * Collect HTTP headers from $_SERVER, normalizing names to lower case.
     *
     * @return array<string, string>
     */
    private function collectHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name           = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string) $value;
            }
        }

        // Content-Type / Content-Length are not prefixed with HTTP_.
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = (string) $_SERVER['CONTENT_LENGTH'];
        }

        return $headers;
    }

    /**
     * Parse the request body: JSON first, then form-encoded ($_POST).
     *
     * @return array<string, mixed>
     */
    private function parseBody(): array
    {
        $raw = file_get_contents('php://input');

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $_POST;
    }

    /**
     * Instantiate the controller and call the requested action.
     *
     * @param class-string $controller
     */
    private function invoke(string $controller, string $action, Request $request): void
    {
        if (!class_exists($controller)) {
            $this->jsonError(500, 'Controller not found');

            return;
        }

        $instance = new $controller($this->pdo, $this->config, $this->services);

        if (!method_exists($instance, $action)) {
            $this->jsonError(500, 'Action not found');

            return;
        }

        $instance->{$action}($request);
    }

    /**
     * Extract the path component from a request URI using the PHP 8.5 URI parser.
     */
    private function extractPath(string $uri): string
    {
        $path = null;

        try {
            $parsed = Uri::parse($uri);

            if ($parsed !== null) {
                $path = $parsed->getPath();
            }
        } catch (Throwable) {
            // Fall back to manual parsing below on any parser failure.
            $path = null;
        }

        if ($path === null) {
            $queryPosition = strpos($uri, '?');
            $path = $queryPosition === false ? $uri : substr($uri, 0, $queryPosition);
        }

        if ($path === '') {
            return '/';
        }

        // Normalize a trailing slash (except for the root path).
        if (strlen($path) > 1) {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    /**
     * Emit a JSON error response.
     */
    private function jsonError(int $status, string $message): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            ['error' => $message],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
