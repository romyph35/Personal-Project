<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$user = current_user();
if (!$user) { header('Location: ../login.php'); exit; }
if (($user['role'] ?? 'user') !== 'admin' || ($user['status'] ?? '') !== 'active') {
    http_response_code(403);
    exit('Forbidden: admins only.');
}
