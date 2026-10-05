<?php

declare(strict_types=1);

/**
 * Application configuration.
 *
 * Returns a single associative array with all runtime settings.
 * Secrets (DB password, JWT secret) are NEVER hardcoded here: they are read
 * from real environment variables or from a local .env file (see .env.example).
 */

// ---------------------------------------------------------------------------
// Minimal .env loader (no external dependencies, no framework).
// Existing real environment variables always take precedence over .env values.
// ---------------------------------------------------------------------------
$envFile = __DIR__ . '/.env';

if (is_file($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines !== false) {
        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and lines without a key=value pair.
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // Strip a single pair of surrounding quotes, if present.
            $length = strlen($value);
            if ($length >= 2) {
                $first = $value[0];
                $last  = $value[$length - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            // Do not overwrite variables already provided by the environment.
            if ($key !== '' && getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }
}

/**
 * Read an environment variable, returning $default when it is missing or empty.
 */
$env = static function (string $key, ?string $default = null): ?string {
    $value = getenv($key);

    return ($value === false || $value === '') ? $default : $value;
};

return [
    // -----------------------------------------------------------------------
    // Application
    // -----------------------------------------------------------------------
    'app' => [
        'env'   => $env('APP_ENV', 'production'),
        'debug' => filter_var($env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    ],

    // -----------------------------------------------------------------------
    // Database (PostgreSQL 18, accessed through pure PDO)
    // -----------------------------------------------------------------------
    'db' => [
        'host'     => $env('DB_HOST', '127.0.0.1'),
        'port'     => (int) $env('DB_PORT', '5432'),
        'database' => $env('DB_NAME', 'medvox'),
        'username' => $env('DB_USER', 'postgres'),
        'password' => $env('DB_PASSWORD', ''),
        'charset'  => $env('DB_CHARSET', 'utf8'),
    ],

    // -----------------------------------------------------------------------
    // JWT
    // -----------------------------------------------------------------------
    'jwt' => [
        'secret' => $env('JWT_SECRET', ''),
        'issuer' => $env('JWT_ISSUER', 'medvox'),
        'ttl'    => (int) $env('JWT_TTL', '3600'),
        'algo'   => 'HS256',
    ],

    // -----------------------------------------------------------------------
    // CORS
    // The API is served from a separate domain, so the browser needs explicit
    // Access-Control-Allow-Origin headers for every trusted frontend origin.
    // -----------------------------------------------------------------------
    'cors' => [
        'allowed_origins' => [
            'http://mymedvox.ru',
            'http://adminpanel.surveygo.ru',
            'http://projectpanel.surveygo.ru',
        ],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'max_age'         => 86400,
    ],

    // -----------------------------------------------------------------------
    // Rate limiting (OWASP API #7 — brute-force / DDoS protection)
    // -----------------------------------------------------------------------
    'rate_limit' => [
        'login' => [
            'max_attempts' => (int) $env('RATE_LIMIT_LOGIN_MAX', '10'),
            'window'       => (int) $env('RATE_LIMIT_LOGIN_WINDOW', '60'),
            'storage'      => $env('RATE_LIMIT_STORAGE', __DIR__ . '/storage/rate_limit'),
        ],
    ],
];

