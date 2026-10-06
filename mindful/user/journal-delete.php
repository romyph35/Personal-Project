<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $st = db()->prepare('DELETE FROM journal_entries WHERE id=? AND user_id=?');
    $st->execute([$id, (int)$user['id']]);
}
header('Location: journal.php');
exit;
