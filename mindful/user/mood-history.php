<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$base = '..';
$page_title = 'Mood History';
$uid = (int)$user['id'];
try {
    $st = db()->prepare('SELECT c.*, m.label, m.emoji, m.score FROM check_ins c JOIN moods m ON m.id=c.mood_id WHERE c.user_id=? ORDER BY c.created_at DESC LIMIT 60');
    $st->execute([$uid]);
    $rows = $st->fetchAll();
    $agg = db()->prepare("SELECT m.label, m.emoji, COUNT(*) n FROM check_ins c JOIN moods m ON m.id=c.mood_id WHERE c.user_id=? AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY m.label, m.emoji ORDER BY n DESC");
    $agg->execute([$uid]);
    $week = $agg->fetchAll();
    $tot = 0; foreach ($week as $w) $tot += (int)$w['n'];
} catch (Throwable $ex) { $rows = []; $week = []; $tot = 0; }
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap">
    <div class="page-head reveal">
      <div><p class="eyebrow">PATTERNS</p><h1 class="section-title">Your mood history</h1>
      <p class="section-sub">Gentle patterns — not judgments. Look for what lifts you up.</p></div>
      <a class="btn btn-primary btn-sm" href="mood.php">+ New check-in</a>
    </div>
    <div class="card reveal">
      <h3>This week</h3>
      <?php if ($week): ?>
        <div class="ach-row">
          <?php foreach ($week as $w): $pct = $tot ? round((int)$w['n'] / $tot * 100) : 0; ?>
            <span class="ach"><?= e((string)$w['emoji']) ?> <?= e((string)$w['label']) ?> · <?= (int)$w['n'] ?> (<?= $pct ?>%)</span>
          <?php endforeach; ?>
        </div>
      <?php else: ?><p class="muted">No check-ins this week yet. <a href="mood.php">Check in now</a>.</p><?php endif; ?>
    </div>
    <div class="table-wrap reveal" style="margin-top:1rem">
      <table>
        <thead><tr><th>Date</th><th>Mood</th><th>Note</th></tr></thead>
        <tbody>
        <?php if ($rows): foreach ($rows as $r): ?>
          <tr>
            <td><?= e(date('M j, Y g:i A', strtotime((string)$r['created_at']))) ?></td>
            <td><?= e((string)$r['emoji']) ?> <strong><?= e((string)$r['label']) ?></strong></td>
            <td><?= $r['note'] ? e((string)$r['note']) : '<span class="muted">—</span>' ?></td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="3" class="muted">No check-ins yet. Your history will live here. 🌱</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
