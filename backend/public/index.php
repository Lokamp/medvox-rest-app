<?php

declare(strict_types=1);

/**
 * Single entry point for the Medvox REST API.
 *
 * Responsibilities:
 *  - Load configuration.
 *  - Configure error reporting (production-safe).
 *  - Emit CORS headers and answer preflight (OPTIONS) requests.
 *  - Register a PSR-4 autoloader for the App\ namespace.
 *  - Initialize the PDO connection (pure PDO, no emulated prepares).
 *  - Register routes and dispatch the request through the router.
 */

use App\Controllers\AdminPanelController;
use App\Controllers\AuthController;
use App\Controllers\MainController;
use App\Controllers\ProjectPanelController;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\RoleMiddleware;
use App\Router;
use App\Services\JWTService;
use App\Services\RateLimiter;

// ---------------------------------------------------------------------------
// 1. Configuration
// ---------------------------------------------------------------------------
/** @var array<string, mixed> $config */
$config = require __DIR__ . '/../config.php';

// ---------------------------------------------------------------------------
// 2. Error reporting
// ---------------------------------------------------------------------------
error_reporting(E_ALL);

if (($config['app']['debug'] ?? false) === true) {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// ---------------------------------------------------------------------------
// 3. CORS
// The API lives on a separate domain, so trusted frontend origins must be
// explicitly allowed. Multiple origins are supported (two domains, three apps).
// ---------------------------------------------------------------------------
$requestOrigin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = $config['cors']['allowed_origins'] ?? [];

if ($requestOrigin !== '' && in_array($requestOrigin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
    header('Access-Control-Allow-Credentials: true');
}

// Always advertise the allowed methods/headers; the browser caches this.
header('Vary: Origin');
header('Access-Control-Allow-Methods: ' . implode(', ', $config['cors']['allowed_methods'] ?? []));
header('Access-Control-Allow-Headers: ' . implode(', ', $config['cors']['allowed_headers'] ?? []));
header('Access-Control-Max-Age: ' . (string) ($config['cors']['max_age'] ?? 86400));

// Answer preflight requests immediately, before any routing or DB work.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Default response type for the REST API.
header('Content-Type: application/json; charset=utf-8');

// ---------------------------------------------------------------------------
// 4. Autoloader (PSR-4: App\ => src/)
// ---------------------------------------------------------------------------
spl_autoload_register(static function (string $class): void {
    $prefix  = 'App\\';
    $baseDir = __DIR__ . '/../src/';
    $length  = strlen($prefix);

    if (strncmp($prefix, $class, $length) !== 0) {
        return;
    }

    $relativeClass = substr($class, $length);
    $file          = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

// ---------------------------------------------------------------------------
// 5. Database connection (pure PDO, prepared statements only)
// ---------------------------------------------------------------------------
$dsn = sprintf(
    'pgsql:host=%s;port=%d;dbname=%s',
    $config['db']['host'],
    $config['db']['port'],
    $config['db']['database']
);

try {
    $pdo = new PDO(
        $dsn,
        $config['db']['username'],
        $config['db']['password'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Disable emulated prepares: real server-side prepared statements.
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------------------------------------------------------------------------
// 6. Routing
// ---------------------------------------------------------------------------
try {
    // Shared token service (one secret, per-application audience).
    $jwt = new JWTService(
        (string) ($config['jwt']['secret'] ?? ''),
        (int) ($config['jwt']['ttl'] ?? 3600),
        (string) ($config['jwt']['issuer'] ?? 'medvox'),
    );

    // Shared rate limiter for all login endpoints (per client IP).
    $loginLimiter = new RateLimiter(
        (string) ($config['rate_limit']['login']['storage'] ?? __DIR__ . '/../storage/rate_limit'),
        (int) ($config['rate_limit']['login']['max_attempts'] ?? 10),
        (int) ($config['rate_limit']['login']['window'] ?? 60),
    );
    $loginRateLimit = new RateLimitMiddleware($loginLimiter, 'login');

    $router = new Router($pdo, $config, ['rate_limiter' => $loginLimiter]);

    // --- Application 1: Main (http://mymedvox.ru) ---
    $mainAuth = new RoleMiddleware($jwt, ['user'], 'main');

    $router->get('/api/v1/main/faq', MainController::class, 'faq');
    $router->post('/api/v1/main/registration', MainController::class, 'registration');
    $router->post('/api/v1/main/auth/login', AuthController::class, 'mainLogin', [$loginRateLimit]);
    $router->get('/api/v1/main/account/home', MainController::class, 'accountHome', [$mainAuth]);

    // --- Application 2: AdminPanel (http://adminpanel.surveygo.ru) ---
    // Admin-only endpoints (full access) and shared endpoints (admin + moderator).
    $adminOnly        = new RoleMiddleware($jwt, ['admin'], 'adminpanel');            // for future admin-only endpoints
    $adminOrModerator = new RoleMiddleware($jwt, ['admin', 'moderator'], 'adminpanel'); // home + moderation

    $router->post('/api/v1/adminpanel/auth/login', AuthController::class, 'adminLogin', [$loginRateLimit]);
    $router->get('/api/v1/adminpanel/home', AdminPanelController::class, 'home', [$adminOrModerator]);
    $router->get('/api/v1/adminpanel/moderation', AdminPanelController::class, 'moderation', [$adminOrModerator]);

    // --- Application 3: ProjectPanel (http://projectpanel.surveygo.ru) ---
    // Full access: admins and managers.
    $projectAuth = new RoleMiddleware($jwt, ['admin', 'manager'], 'projectpanel');

    $router->post('/api/v1/projectpanel/auth/login', AuthController::class, 'projectLogin', [$loginRateLimit]);
    $router->get('/api/v1/projectpanel/projects', ProjectPanelController::class, 'projects', [$projectAuth]);

    $router->dispatch();
} catch (Throwable $e) {
    error_log('Unhandled exception: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode(
        ['error' => ($config['app']['debug'] ?? false) ? $e->getMessage() : 'Internal Server Error'],
        JSON_UNESCAPED_UNICODE
    );
}
