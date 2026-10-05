<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use PDO;

/**
 * Main application controller (http://mymedvox.ru).
 *
 * Public endpoints are stubs for now; the protected "account" endpoint
 * demonstrates the role middleware by returning the authenticated user.
 */
final class MainController
{
    /**
     * @param PDO                  $pdo      Database connection.
     * @param array<string, mixed> $config   Application configuration.
     * @param array<string, mixed> $services Shared services.
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly array $config,
        private readonly array $services = [],
    ) {
    }

    /**
     * GET /api/v1/main/faq — public FAQ.
     *
     * Returns the list of help topics shown on the public "Помощь" page.
     * The content is managed from the admin panel, so it is read from the
     * `faq` table on every request (no caching at this layer).
     */
    public function faq(Request $request): void
    {
        $statement = $this->pdo->query(
            'SELECT id, title, description, created_at
               FROM faq
              ORDER BY id ASC'
        );

        $rows = $statement->fetchAll();

        $items = array_map(
            static fn (array $row): array => [
                'id'          => (int) $row['id'],
                'title'       => (string) $row['title'],
                'description' => (string) $row['description'],
                'created_at'  => (string) $row['created_at'],
            ],
            $rows
        );

        $this->respond(200, ['items' => $items]);
    }

    /**
     * POST /api/v1/main/register — public registration (stub).
     */
    public function register(Request $request): void
    {
        $this->notImplemented('register');
    }

    /**
     * GET /api/v1/main/account/home — protected personal account (stub).
     */
    public function accountHome(Request $request): void
    {
        $user = $request->user();

        $this->respond(200, [
            'message' => 'Personal account (stub).',
            'user'    => $user === null ? null : [
                'id'       => $user->id,
                'username' => $user->username,
                'role'     => $user->role,
                'audience' => $user->audience,
            ],
        ]);
    }

    /**
     * Emit a 501 Not Implemented response.
     */
    private function notImplemented(string $endpoint): void
    {
        $this->respond(501, [
            'error'    => 'Not Implemented',
            'endpoint' => $endpoint,
        ]);
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
