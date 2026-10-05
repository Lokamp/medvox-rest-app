<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Immutable authenticated user context.
 *
 * Created by the role middleware after a successful token validation and
 * attached to the current Request. Because the class is readonly, the identity
 * cannot be altered by downstream code (unlike a global variable).
 */
final readonly class AuthContext
{
    /**
     * @param int                  $id       User/manager identifier.
     * @param string               $username Login (email).
     * @param string               $role     Application role (user/admin/manager/operator).
     * @param string               $audience Token audience (main/adminpanel/projectpanel).
     * @param array<string, mixed> $claims   Full decoded JWT claims.
     */
    public function __construct(
        public int $id,
        public string $username,
        public string $role,
        public string $audience,
        public array $claims = [],
    ) {
    }

    /**
     * Build a context from decoded JWT claims.
     *
     * @param array<string, mixed> $claims
     */
    public static function fromClaims(array $claims): self
    {
        return new self(
            id: (int) ($claims['id'] ?? 0),
            username: (string) ($claims['username'] ?? ''),
            role: (string) ($claims['role'] ?? ''),
            audience: (string) ($claims['aud'] ?? ''),
            claims: $claims,
        );
    }

    /**
     * Whether the user has one of the given roles.
     */
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}
