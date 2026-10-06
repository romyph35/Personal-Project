<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$base = '..';
$page_title = 'Mood Check-in';
$uid = (int)$user['id'];
$msg = null; $err = null;
try {
    $moods = db()->query('SELECT * FROM moods ORDER BY score DESC')->fetchAll();
    $tags = db()->query('SELECT * FROM mood_tags ORDER BY name')->fetchAll();
} catch (Throwable $ex) { $moods = []; $tags = []; }
if (!$moods) {
    $moods = [
        ['id'=>1,'label'=>'Great','emoji'=>'😊'],['id'=>2,'label'=>'Good','emoji'=>'🙂'],
        ['id'=>3,'label'=>'Okay','emoji'=>'😐'],['id'=>4,'label'=>'Low','emoji'=>'😔'],['id'=>5,'label'=>'Very Low','emoji'=>'😢'],
    ];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $mood_id = (int)($_POST['mood_id'] ?? 0);
    $note = trim((string)($_POST['note'] ?? ''));
    if (mb_strlen($note) > 280) $note = mb_substr($note, 0, 280);
    $tagIds = array_map('intval', (array)($_POST['tags'] ?? []));
    $valid = false;
    foreach ($moods as $m) { if ((int)$m['id'] === $mood_id) { $valid = true; break; } }
    if (!$valid) { $err = 'Please choose a mood.'; }
    else {
        try {
            $ins = db()->prepare('INSERT INTO check_ins (user_id, mood_id, note) VALUES (?,?,?)');
            $ins->execute([$uid, $mood_id, $note !== '' ? $note : null]);
            $cid = (int)db()->lastInsertId();
            if ($tagIds) {
                $t = db()->prepare('INSERT IGNORE INTO mood_entry_tags (check_in_id, tag_id) VALUES (?,?)');
                foreach (array_slice($tagIds, 0, 5) as $tid) { $t->execute([$cid, $tid]); }
            }
            $cnt = (int)db()->query('SELECT COUNT(*) FROM check_ins WHERE user_id=' . $uid)->fetchColumn();
            if ($cnt >= 1) award_achievement($uid, 'first-checkin');
            if ($cnt >= 30) award_achievement($uid, 'thirty-checkins');
            try {
                $sd = db()->prepare('SELECT DISTINCT DATE(created_at) d FROM check_ins WHERE user_id=? ORDER BY d DESC LIMIT 7');
                $sd->execute([$uid]);
                $days = $sd->fetchAll(PDO::FETCH_COLUMN);
                $streak = 0;
                $exp = new DateTime('today');
                if ($days && $days[0] !== $exp->format('Y-m-d')) { $exp->modify('-1 day'); }
                foreach ($days as $d) {
                    if ($d === $exp->format('Y-m-d')) { $streak++; $exp->modify('-1 day'); }
                    else break;
                }
                if ($streak >= 7) award_achievement($uid, 'week-reflection');
            } catch (Throwable $ignored) {}
            notify($uid, 'Check-in saved 🌿', 'Thanks for checking in. Your feelings are worth noticing.');
            header('Location: mood.php?saved=1');
            exit;
        } catch (Throwable $ex) { $err = 'Could not save. Please try again.'; }
    }
}
if (isset($_GET['saved'])) $msg = 'Thanks for checking in. Your feelings are worth noticing. 💚';
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap" style="max-width:720px">
    <div class="card reveal">
      <p class="eyebrow">DAILY CHECK-IN</p>
      <h1 class="section-title">Check in with yourself.</h1>
      <p class="section-sub">A few seconds of reflection can help you understand your patterns over time.</p>
      <?php if ($msg): ?><p class="alert success" role="status"><?= e($msg) ?></p><?php endif; ?>
      <?php if ($err): ?><p class="alert error" role="alert"><?= e($err) ?></p><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="mood-pick" data-mood-group data-confirm="#moodConfirm" role="radiogroup" aria-label="Choose your mood">
          <?php foreach ($moods as $m): ?>
            <label class="mood-btn" data-label="<?= e((string)$m['label']) ?>" aria-pressed="false" tabindex="0">
              <input type="radio" name="mood_id" value="<?= (int)$m['id'] ?>" hidden>
              <?= e((string)$m['emoji']) ?><small><?= e((string)$m['label']) ?></small>
            </label>
          <?php endforeach; ?>
        </div>
        <p class="mood-confirm" id="moodConfirm" aria-live="polite"></p>
        <label>Note (optional, max 280 chars)
          <textarea name="note" maxlength="280" placeholder="A few honest words about today…"><?= e($_POST['note'] ?? '') ?></textarea>
        </label>
        <?php if ($tags): ?>
        <fieldset style="border:1px solid var(--line);border-radius:14px;padding:.8rem;margin:.6rem 0">
          <legend style="padding:0 .4rem;font-weight:700">What's behind this? (optional)</legend>
          <div class="ach-row">
            <?php foreach ($tags as $t): ?>
              <label class="ach" style="cursor:pointer"><input type="checkbox" name="tags[]" value="<?= (int)$t['id'] ?>"> <?= e($t['name']) ?></label>
            <?php endforeach; ?>
          </div>
        </fieldset>
        <?php endif; ?>
        <button class="btn btn-primary btn-block" type="submit">Save Check-in 🌿</button>
      </form>
      <p class="center" style="margin-top:1rem"><a href="mood-history.php">View mood history →</a></p>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
