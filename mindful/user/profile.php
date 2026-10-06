<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$base = '..';
$page_title = 'Profile & Plan';
$uid = (int)$user['id'];
$msg = null; $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'profile') {
        $name = trim((string)($_POST['name'] ?? ''));
        if (mb_strlen($name) < 2) $err = 'Please enter your name.';
        else {
            db()->prepare('UPDATE users SET name=? WHERE id=?')->execute([$name, $uid]);
            $user['name'] = $name;
            $msg = 'Profile updated. 🌿';
        }
    } elseif ($action === 'plan') {
        $plan = (string)($_POST['plan'] ?? '');
        if (!in_array($plan, ['free','plus','premium'], true)) $err = 'Invalid plan.';
        else {
            db()->prepare('UPDATE users SET subscription_plan=? WHERE id=?')->execute([$plan, $uid]);
            db()->prepare('INSERT INTO subscriptions (user_id, plan, status) VALUES (?,?,\'active\') ON DUPLICATE KEY UPDATE plan=?, status=\'active\'')->execute([$uid, $plan, $plan]);
            $user['subscription_plan'] = $plan;
            $msg = 'Plan updated to ' . ucfirst($plan) . '. (Demo — no payment taken.) ✨';
        }
    }
}
try {
    $sub = db()->prepare('SELECT * FROM subscriptions WHERE user_id=? LIMIT 1');
    $sub->execute([$uid]);
    $subRow = $sub->fetch();
    $achs = db()->prepare('SELECT a.code,a.title,a.icon,a.description FROM user_achievements ua JOIN achievements a ON a.id=ua.achievement_id WHERE ua.user_id=? ORDER BY ua.unlocked_at DESC');
    $achs->execute([$uid]);
    $mine = $achs->fetchAll();
    $all = db()->query('SELECT * FROM achievements ORDER BY id')->fetchAll();
} catch (Throwable $ex) { $subRow = false; $mine = []; $all = []; }
$unlocked = array_column($mine, 'code');
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap">
    <div class="page-head reveal"><div><p class="eyebrow">YOU</p><h1 class="section-title">Profile &amp; plan</h1></div></div>
    <?php if ($msg): ?><p class="alert success reveal"><?= e($msg) ?></p><?php endif; ?>
    <?php if ($err): ?><p class="alert error reveal"><?= e($err) ?></p><?php endif; ?>
    <div class="grid-2">
      <div class="card reveal">
        <h3>Your details</h3>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="profile">
          <label>Name<input type="text" name="name" value="<?= e((string)$user['name']) ?>" required></label>
          <p class="muted">Email: <?= e((string)$user['email']) ?></p>
          <button class="btn btn-primary btn-sm" type="submit">Save changes</button>
        </form>
      </div>
      <div class="card reveal">
        <h3>Your plan: <?= e(ucfirst((string)($subRow['plan'] ?? $user['subscription_plan'] ?? 'free'))) ?></h3>
        <p class="muted">Status: <?= e((string)($subRow['status'] ?? 'active')) ?> · Demo mode — choosing a plan only updates your account, no payment.</p>
        <form method="post" style="display:flex;gap:.5rem;flex-wrap:wrap">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="plan">
          <?php foreach (['free','plus','premium'] as $p): ?>
            <button class="btn <?= ($user['subscription_plan'] ?? 'free') === $p ? 'btn-primary' : 'btn-soft' ?> btn-sm" type="submit" name="plan" value="<?= $p ?>">Choose <?= ucfirst($p) ?></button>
          <?php endforeach; ?>
        </form>
      </div>
    </div>
    <div class="card reveal" style="margin-top:1.1rem">
      <h3>Achievements</h3>
      <div class="ach-row">
        <?php foreach ($all as $a): $has = in_array($a['code'], $unlocked, true); ?>
          <span class="ach <?= $has ? '' : 'locked' ?>"><?= e((string)$a['icon']) ?> <?= e((string)$a['title']) ?><?= $has ? '' : ' · locked' ?></span>
        <?php endforeach; ?>
        <?php if (!$all): ?><p class="muted">Achievements will appear here.</p><?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
