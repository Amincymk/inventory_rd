
<?php
declare(strict_types=1);

require_once __DIR__ . '/auth_check.php';

function requireRole(array $allowedRoles): void
{
    $role = $_SESSION['user']['role'] ?? '';

    if (!in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        exit('Access denied. You do not have permission for this action.');
    }
}
