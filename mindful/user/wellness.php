<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$base = '..';
$page_title = 'Wellness Activities';
$uid = (int)$user['id'];
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $type = (string)($_POST['type'] ?? '');
    $content = trim((string)($_POST['content'] ?? ''));
    $minutes = (int)($_POST['minutes'] ?? 0) ?: null;
    if (!in_array($type, ['breathing','gratitude','grounding','reflection'], true)) { $msg = 'Unknown activity.'; }
    elseif (in_array($type, ['gratitude','reflection'], true) && $content === '') { $msg = 'Please write a few words first.'; }
    else {
        if (mb_strlen($content) > 500) $content = mb_substr($content, 0, 500);
        $st = db()->prepare('INSERT INTO wellness_activities (user_id, type, minutes, content) VALUES (?,?,?,?)');
        $st->execute([$uid, $type, $minutes, $content !== '' ? $content : null]);
        $cnt = (int)db()->query("SELECT COUNT(*) FROM wellness_activities WHERE user_id=$uid")->fetchColumn();
        if ($cnt >= 10) award_achievement($uid, 'ten-sessions');
        $msg = 'Beautifully done. Logged to your journey. 🌿';
    }
}
try {
    $recent = db()->prepare('SELECT * FROM wellness_activities WHERE user_id=? ORDER BY created_at DESC LIMIT 8');
    $recent->execute([$uid]);
    $logs = $recent->fetchAll();
} catch (Throwable $ex) { $logs = []; }
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap">
    <div class="page-head reveal">
      <div><p class="eyebrow">PRACTICE</p><h1 class="section-title">Small practices, real calm.</h1>
      <p class="section-sub">Breathe, notice, ground, reflect — each one logs to your journey.</p></div>
    </div>
    <?php if ($msg): ?><p class="alert success reveal"><?= e($msg) ?></p><?php endif; ?>
    <div class="grid-2">
      <div class="card reveal breathe-wrap">
        <p class="eyebrow">BREATHE</p><h3>Guided breathing</h3>
        <div class="breathe-circle" id="breatheCircle"><span id="breathePhase">Ready?</span></div>
        <p class="muted" id="breatheTimer">1:00</p>
        <div class="breathe-controls">
          <button class="btn btn-primary btn-sm" type="button" onclick="startBreathing(1)">1 min</button>
          <button class="btn btn-soft btn-sm" type="button" onclick="startBreathing(3)">3 min</button>
          <button class="btn btn-soft btn-sm" type="button" onclick="startBreathing(5)">5 min</button>
          <button class="btn btn-ghost btn-sm" type="button" id="breatheStop">Stop</button>
        </div>
        <form method="post" style="margin-top:.8rem">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="type" value="breathing">
          <input type="hidden" name="minutes" value="1">
          <button class="btn btn-ghost btn-sm" type="submit">Log a breathing session ✓</button>
        </form>
      </div>
      <div class="card reveal">
        <p class="eyebrow">GRATITUDE</p><h3>What is one thing you're grateful for today?</h3>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="type" value="gratitude">
          <label>I'm grateful for…<input type="text" name="content" maxlength="500" placeholder="Warm coffee, a kind message, sunshine…"></label>
          <button class="btn btn-primary btn-sm" type="submit">Save gratitude 💛</button>
        </form>
      </div>
      <div class="card reveal">
        <p class="eyebrow">GROUND</p><h3>5-4-3-2-1 grounding</h3>
        <p class="muted">Tap each step as you notice it around you.</p>
        <div class="ach-row">
          <button class="ach" type="button" data-ground-step>👀 5 things you see</button>
          <button class="ach" type="button" data-ground-step>✋ 4 things you feel</button>
          <button class="ach" type="button" data-ground-step>👂 3 things you hear</button>
          <button class="ach" type="button" data-ground-step>👃 2 things you smell</button>
          <button class="ach" type="button" data-ground-step>👅 1 thing you taste</button>
        </div>
        <p class="muted" id="groundNote" aria-live="polite"></p>
        <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="type" value="grounding"><button class="btn btn-soft btn-sm" type="submit">Log grounding ✓</button></form>
      </div>
      <div class="card reveal">
        <p class="eyebrow">REFLECT</p><h3>A short reflection</h3>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="type" value="reflection">
          <label>What's on your mind?<textarea name="content" maxlength="500" placeholder="Today I noticed…"></textarea></label>
          <button class="btn btn-primary btn-sm" type="submit">Save reflection 📝</button>
        </form>
      </div>
    </div>
    <div class="card reveal" style="margin-top:1.1rem">
      <h3>Recent practices</h3>
      <?php if ($logs): ?>
        <div class="ach-row"><?php foreach ($logs as $l): ?><span class="ach"><?= e(ucfirst((string)$l['type'])) ?> · <?= e(date('M j', strtotime((string)$l['created_at']))) ?></span><?php endforeach; ?></div>
      <?php else: ?><p class="muted">Nothing logged yet — your practices will appear here.</p><?php endif; ?>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
