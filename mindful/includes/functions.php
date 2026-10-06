<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

session_start();

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $t = $_POST['csrf'] ?? '';
        if (!hash_equals($_SESSION['csrf'] ?? '', (string)$t)) { http_response_code(419); exit('Session expired. Please go back and try again.'); }
    }
}
function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    try {
        $st = db()->prepare('SELECT id,name,email,role,status,subscription_plan FROM users WHERE id=?');
        $st->execute([(int)$_SESSION['uid']]);
        $u = $st->fetch();
        return $u ?: null;
    } catch (Throwable $ex) { return null; }
}
function require_login(): array {
    $u = current_user();
    if (!$u) { header('Location: ../login.php'); exit; }
    if ($u['status'] !== 'active') { session_destroy(); header('Location: ../login.php?disabled=1'); exit; }
    return $u;
}
function greeting(?string $name): string {
    $h = (int)date('G');
    $t = $h < 12 ? 'Good morning' : ($h < 18 ? 'Good afternoon' : 'Good evening');
    return $t . ($name ? ', ' . $name : '');
}
function award_achievement(int $uid, string $code): void {
    $st = db()->prepare("SELECT id FROM achievements WHERE code=?");
    $st->execute([$code]);
    $a = $st->fetch();
    if (!$a) return;
    $ins = db()->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?,?)");
    $ins->execute([$uid, (int)$a['id']]);
}
function notify(int $uid, string $title, ?string $body = null): void {
    $st = db()->prepare("INSERT INTO notifications (user_id,title,body) VALUES (?,?,?)");
    $st->execute([$uid, $title, $body]);
}
