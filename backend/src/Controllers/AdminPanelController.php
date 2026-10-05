<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use PDO;

/**
 * AdminPanel application controller (http://adminpanel.surveygo.ru).
 *
 * All endpoints are protected by the role middleware. The "home" endpoint
 * is a stub that returns the authenticated staff member.
 */
final class AdminPanelController
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
     * GET /api/v1/adminpanel/home — protected admin dashboard (stub).
     *
     * Available to admins and moderators (is_moderator).
     */
    public function home(Request $request): void
    {
        $user = $request->user();

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            [
                'message' => 'Admin dashboard (stub).',
                'user'    => $user === null ? null : [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'role'     => $user->role,
                    'audience' => $user->audience,
                ],
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * GET /api/v1/adminpanel/moderation — protected moderation view (stub).
     *
     * Available to admins and moderators (is_moderator).
     */
    public function moderation(Request $request): void
    {
        $user = $request->user();

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            [
                'message' => 'Moderation (stub).',
                'user'    => $user === null ? null : [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'role'     => $user->role,
                    'audience' => $user->audience,
                ],
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
