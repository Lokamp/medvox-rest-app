<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Services\JWTService;
use App\Services\RateLimiter;
use PDO;

/**
 * Authentication controller.
 *
 * Provides three independent login endpoints, one per isolated application.
 * All database access uses PDO prepared statements; input is explicitly
 * filtered (no mass assignment).
 */
final class AuthController
{
    private const AUD_MAIN    = 'main';
    private const AUD_ADMIN   = 'adminpanel';
    private const AUD_PROJECT = 'projectpanel';

    private readonly JWTService $jwt;
    private readonly ?RateLimiter $rateLimiter;

    /**
     * @param PDO                  $pdo      Database connection.
     * @param array<string, mixed> $config   Application configuration.
     * @param array<string, mixed> $services Shared services (e.g. rate limiter).
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly array $config,
        array $services = [],
    ) {
        $this->jwt = new JWTService(
            (string) ($config['jwt']['secret'] ?? ''),
            (int) ($config['jwt']['ttl'] ?? 3600),
            (string) ($config['jwt']['issuer'] ?? 'medvox'),
        );

        $limiter = $services['rate_limiter'] ?? null;
        $this->rateLimiter = $limiter instanceof RateLimiter ? $limiter : null;
    }

    /**
     * POST /api/v1/main/auth/login — regular user login.
     */
    public function mainLogin(Request $request): void
    {
        $email    = $this->normalizeEmail($request->input('email'));
        $password = $this->normalizePassword($request->input('password'));

        if ($email === null || $password === null) {
            $this->respond(422, ['error' => 'Email and password are required.']);

            return;
        }

        $statement = $this->pdo->prepare(
            'SELECT id, email, password_hash, status, first_name, last_name
             FROM users
             WHERE lower(email) = lower(:email)
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if ($user === false || !password_verify($password, (string) $user['password_hash'])) {
            $this->respond(401, ['error' => 'Invalid credentials.']);

            return;
        }

        // Blocked accounts must not be able to sign in.
        if (in_array((string) $user['status'], ['banned', 'bad_email'], true)) {
            $this->respond(403, ['error' => 'Account is not allowed to sign in.']);

            return;
        }

        $token = $this->jwt->encode([
            'id'       => (int) $user['id'],
            'username' => (string) $user['email'],
            'role'     => 'user',
            'aud'      => self::AUD_MAIN,
        ]);

        // Successful login: reset the brute-force counter for this client.
        $this->resetRateLimit($request);

        $this->respond(200, $this->tokenResponse($token, [
            'id'         => (int) $user['id'],
            'email'      => (string) $user['email'],
            'first_name' => (string) $user['first_name'],
            'last_name'  => (string) $user['last_name'],
            'role'       => 'user',
        ]));
    }

    /**
     * POST /api/v1/adminpanel/auth/login — AdminPanel staff login.
     */
    public function adminLogin(Request $request): void
    {
        $email    = $this->normalizeEmail($request->input('email'));
        $password = $this->normalizePassword($request->input('password'));

        if ($email === null || $password === null) {
            $this->respond(422, ['error' => 'Email and password are required.']);

            return;
        }

        $statement = $this->pdo->prepare(
            'SELECT id, email, password_hash, first_name, last_name,
                    is_admin, is_moderator, is_manager, is_active
             FROM managers
             WHERE lower(email) = lower(:email)
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $manager = $statement->fetch();

        if ($manager === false || !password_verify($password, (string) $manager['password_hash'])) {
            $this->respond(401, ['error' => 'Invalid credentials.']);

            return;
        }

        if (!$this->toBool($manager['is_active'])) {
            $this->respond(403, ['error' => 'Account is disabled.']);

            return;
        }

        $isAdmin     = $this->toBool($manager['is_admin']);
        $isModerator = $this->toBool($manager['is_moderator']);

        // AdminPanel is available to admins and moderators only.
        if (!$isAdmin && !$isModerator) {
            $this->respond(403, ['error' => 'Insufficient permissions.']);

            return;
        }

        $role = $isAdmin ? 'admin' : 'moderator';

        $token = $this->jwt->encode([
            'id'       => (int) $manager['id'],
            'username' => (string) $manager['email'],
            'role'     => $role,
            'aud'      => self::AUD_ADMIN,
        ]);

        // Successful login: reset the brute-force counter for this client.
        $this->resetRateLimit($request);

        $this->respond(200, $this->tokenResponse($token, [
            'id'         => (int) $manager['id'],
            'email'      => (string) $manager['email'],
            'first_name' => (string) $manager['first_name'],
            'last_name'  => (string) $manager['last_name'],
            'role'       => $role,
        ]));
    }

    /**
     * POST /api/v1/projectpanel/auth/login — ProjectPanel staff login.
     */
    public function projectLogin(Request $request): void
    {
        $email    = $this->normalizeEmail($request->input('email'));
        $password = $this->normalizePassword($request->input('password'));

        if ($email === null || $password === null) {
            $this->respond(422, ['error' => 'Email and password are required.']);

            return;
        }

        $statement = $this->pdo->prepare(
            'SELECT id, email, password_hash, first_name, last_name,
                    is_admin, is_moderator, is_manager, is_active
             FROM managers
             WHERE lower(email) = lower(:email)
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $manager = $statement->fetch();

        if ($manager === false || !password_verify($password, (string) $manager['password_hash'])) {
            $this->respond(401, ['error' => 'Invalid credentials.']);

            return;
        }

        if (!$this->toBool($manager['is_active'])) {
            $this->respond(403, ['error' => 'Account is disabled.']);

            return;
        }

        $isAdmin   = $this->toBool($manager['is_admin']);
        $isManager = $this->toBool($manager['is_manager']);

        // ProjectPanel is available to admins (full access) and managers.
        if (!$isAdmin && !$isManager) {
            $this->respond(403, ['error' => 'Insufficient permissions.']);

            return;
        }

        $role = $isAdmin ? 'admin' : 'manager';

        $token = $this->jwt->encode([
            'id'       => (int) $manager['id'],
            'username' => (string) $manager['email'],
            'role'     => $role,
            'aud'      => self::AUD_PROJECT,
        ]);

        // Successful login: reset the brute-force counter for this client.
        $this->resetRateLimit($request);

        $this->respond(200, $this->tokenResponse($token, [
            'id'         => (int) $manager['id'],
            'email'      => (string) $manager['email'],
            'first_name' => (string) $manager['first_name'],
            'last_name'  => (string) $manager['last_name'],
            'role'       => $role,
        ]));
    }

    /**
     * Validate and normalize an email address.
     */
    private function normalizeEmail(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $email = trim($value);

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $email;
    }

    /**
     * Validate a password (never trimmed: spaces may be significant).
     */
    private function normalizePassword(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * Normalize a database boolean value across drivers (bool, 't'/'f', 0/1).
     */
    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 't', 'true', 'on', 'yes'], true);
        }

        return (bool) $value;
    }

    /**
     * Reset the login rate-limit counter after a successful authentication.
     *
     * The counter key is published by RateLimitMiddleware as a request attribute.
     */
    private function resetRateLimit(Request $request): void
    {
        $key = $request->attribute('rate_limit_key');

        if ($this->rateLimiter !== null && is_string($key) && $key !== '') {
            $this->rateLimiter->clear($key);
        }
    }

    /**
     * Build the standard successful login response.
     *
     * @param array<string, mixed> $user
     *
     * @return array<string, mixed>
     */
    private function tokenResponse(string $token, array $user): array
    {
        return [
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => (int) ($this->config['jwt']['ttl'] ?? 3600),
            'user'       => $user,
        ];
    }

    /**
     * Emit a JSON response with the given HTTP status.
     *
     * @param array<string, mixed> $payload
     */
    private function respond(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
