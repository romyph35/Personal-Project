<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$base = '..';
$page_title = 'Notifications';
$uid = (int)$user['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    db()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$uid]);
    header('Location: notifications.php?read=1');
    exit;
}
try {
    $st = db()->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 30');
    $st->execute([$uid]);
    $notes = $st->fetchAll();
} catch (Throwable $ex) { $notes = []; }
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap" style="max-width:720px">
    <div class="page-head reveal"><div><p class="eyebrow">INBOX</p><h1 class="section-title">Notifications</h1></div>
      <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="btn btn-soft btn-sm" type="submit">Mark all read</button></form>
    </div>
    <?php if (isset($_GET['read'])): ?><p class="alert success reveal">All caught up. 🌿</p><?php endif; ?>
    <?php if ($notes): foreach ($notes as $n): ?>
      <div class="card reveal" style="<?= empty($n['is_read']) ? 'border-left:5px solid var(--sage-d)' : '' ?>">
        <h3 style="margin:0"><?= e((string)$n['title']) ?></h3>
        <?php if (!empty($n['body'])): ?><p class="muted"><?= e((string)$n['body']) ?></p><?php endif; ?>
        <p class="muted" style="font-size:.82rem;margin:0"><?= e(date('M j, Y g:i A', strtotime((string)$n['created_at']))) ?></p>
      </div>
    <?php endforeach; else: ?>
      <div class="card reveal center"><p style="font-size:2rem">🔔</p><h3>All quiet</h3><p class="muted">Check-ins, bookings, and milestones will notify you here.</p></div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
