<?php
declare(strict_types=1);


$allowedRoles = $allowedRoles ?? [];

if (
    !isset($_SESSION['user_role']) ||
    !in_array($_SESSION['user_role'], $allowedRoles, true)
) {
    http_response_code(403);
    exit('Access denied.');
}
