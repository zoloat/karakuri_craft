<?php
declare(strict_types=1);

/**
 * Audience: Human and AI module authors.
 * Purpose: Access current signed-in admin state with one API.
 *
 * Inputs:
 * - Session variables managed by dashboard login flow
 *
 * Outputs:
 * - Authentication status and current admin identifier
 */
final class KrMemberContext
{
    public function isAdminAuthenticated(): bool
    {
        return !empty($_SESSION['kr_admin_auth']);
    }

    public function adminUser(): ?string
    {
        if (!$this->isAdminAuthenticated()) {
            return null;
        }
        $user = (string) ($_SESSION['kr_admin_user'] ?? '');
        return $user !== '' ? $user : null;
    }

    public function requireAdmin(): void
    {
        if (!$this->isAdminAuthenticated()) {
            http_response_code(403);
            echo 'Forbidden.';
            exit;
        }
    }
}
