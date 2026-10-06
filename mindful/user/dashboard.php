<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$base = '..';
$page_title = 'Dashboard';
$uid = (int)$user['id'];
$first = explode(' ', (string)$user['name'])[0] ?? 'Friend';

function calc_streak(PDO $pdo, int $uid): int {
    $st = $pdo->prepare('SELECT DISTINCT DATE(created_at) d FROM check_ins WHERE user_id=? ORDER BY d DESC LIMIT 60');
    $st->execute([$uid]);
    $days = $st->fetchAll(PDO::FETCH_COLUMN);
    if (!$days) return 0;
    $streak = 0;
    $cursor = new DateTime($days[0]);
    $today = new DateTime('today');
    $diff = (int)$today->diff($cursor)->format('%a');
    if ($diff > 1) return 0;
    if ($diff === 1) { /* streak counts but today missing */ }
    $expected = clone $cursor;
    foreach ($days as $d) {
        if ($d === $expected->format('Y-m-d')) { $streak++; $expected->modify('-1 day'); }
        else break;
    }
    return $streak;
}
try {
    $today = db()->prepare('SELECT c.*, m.label, m.emoji, m.score FROM check_ins c JOIN moods m ON m.id=c.mood_id WHERE c.user_id=? AND DATE(c.created_at)=CURDATE() ORDER BY c.created_at DESC LIMIT 1');
    $today->execute([$uid]);
    $todayMood = $today->fetch();
    $week = db()->prepare('SELECT COUNT(*) FROM check_ins WHERE user_id=? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)');
    $week->execute([$uid]);
    $weekCount = (int)$week->fetchColumn();
    $streak = calc_streak(db(), $uid);
    $up = db()->prepare('SELECT a.*, t.name tname FROM appointments a JOIN therapists t ON t.id=a.therapist_id WHERE a.user_id=? AND a.status IN (\'pending\',\'confirmed\') AND a.appt_date >= CURDATE() ORDER BY a.appt_date, a.appt_time LIMIT 1');
    $up->execute([$uid]);
    $upcoming = $up->fetch();
    $rec = db()->query('SELECT * FROM resources ORDER BY RAND() LIMIT 1')->fetch();
    $achs = db()->prepare('SELECT a.title, a.icon FROM user_achievements ua JOIN achievements a ON a.id=ua.achievement_id WHERE ua.user_id=? ORDER BY ua.unlocked_at DESC LIMIT 6');
    $achs->execute([$uid]);
    $myachs = $achs->fetchAll();
    $unread = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
    $unread->execute([$uid]);
    $unreadCount = (int)$unread->fetchColumn();
} catch (Throwable $ex) { $todayMood=false; $weekCount=0; $streak=0; $upcoming=false; $rec=false; $myachs=[]; $unreadCount=0; }
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap">
    <div class="page-head reveal">
      <div>
        <p class="eyebrow">YOUR DAY</p>
        <h1 class="section-title"><?= e(greeting($first)) ?> 🌿</h1>
        <p class="section-sub">One gentle step at a time. Here's your calm overview.</p>
      </div>
      <div style="display:flex;gap:.6rem;flex-wrap:wrap">
        <a class="btn btn-primary btn-sm" href="mood.php">+ Check in</a>
        <a class="btn btn-soft btn-sm" href="journal.php">📝 Journal</a>
        <a class="btn btn-soft btn-sm" href="notifications.php">🔔 <?= $unreadCount ? e((string)$unreadCount) . ' new' : 'Notifications' ?></a>
      </div>
    </div>
    <div class="grid-3">
      <div class="card reveal">
        <h3>💛 Today's mood</h3>
        <?php if ($todayMood): ?>
          <p style="font-size:2.4rem;margin:.2rem 0"><?= e((string)$todayMood['emoji']) ?></p>
          <p><strong><?= e((string)$todayMood['label']) ?></strong></p>
          <?php if (!empty($todayMood['note'])): ?><p class="muted">“<?= e((string)$todayMood['note']) ?>”</p><?php endif; ?>
          <a href="mood-history.php">View history →</a>
        <?php else: ?>
          <p class="muted">No check-in yet today. How are you feeling?</p>
          <a class="btn btn-primary btn-sm" href="mood.php">Check in now</a>
        <?php endif; ?>
      </div>
      <div class="card reveal">
        <h3>🔥 Streak</h3>
        <p class="stat-num"><span data-count="<?= (int)$streak ?>"><?= (int)$streak ?></span> <span class="muted" style="font-size:1rem">day<?= $streak===1?'':'s' ?></span></p>
        <div class="progress" aria-label="Weekly progress"><i style="width:<?= min(100,(int)($weekCount/7*100)) ?>%"></i></div>
        <p class="muted"><?= (int)$weekCount ?> check-ins this week · goal 7</p>
        <a href="mood.php">Keep it going →</a>
      </div>
      <div class="card reveal">
        <h3>📅 Upcoming session</h3>
        <?php if ($upcoming): ?>
          <p><strong><?= e((string)$upcoming['tname']) ?></strong><br><span class="muted"><?= e((string)$upcoming['appt_date']) ?> · <?= e(substr((string)$upcoming['appt_time'],0,5)) ?> · <?= e((string)$upcoming['status']) ?></span></p>
          <a href="sessions.php">Manage sessions →</a>
        <?php else: ?>
          <p class="muted">Nothing booked. A caring therapist is one click away.</p>
          <a class="btn btn-soft btn-sm" href="sessions.php">Book a session</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="grid-2" style="margin-top:1.1rem">
      <div class="card reveal">
        <h3>📖 Recommended for you</h3>
        <?php if ($rec): ?>
          <span class="tag"><?= e((string)$rec['category']) ?></span>
          <h4 style="margin:.4rem 0"><?= e((string)$rec['title']) ?></h4>
          <p class="muted"><?= e((string)$rec['description']) ?></p>
          <a href="resource.php?id=<?= (int)$rec['id'] ?>">Read now →</a>
        <?php else: ?>
          <p class="muted">Visit the library for gentle reads.</p><a href="resources.php">Browse resources →</a>
        <?php endif; ?>
      </div>
      <div class="card reveal">
        <h3>🏆 Achievements</h3>
        <?php if ($myachs): ?>
          <div class="ach-row"><?php foreach ($myachs as $a): ?><span class="ach"><?= e((string)$a['icon']) ?> <?= e((string)$a['title']) ?></span><?php endforeach; ?></div>
        <?php else: ?>
          <p class="muted">Check in today to unlock your first badge 🌱</p>
        <?php endif; ?>
        <p style="margin-top:.6rem"><a href="wellness.php">Try a wellness activity →</a></p>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
