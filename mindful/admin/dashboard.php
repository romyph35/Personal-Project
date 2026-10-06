<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_auth.php';
$base = '..';
$page_title = 'Admin Dashboard';
try {
    $stats = [
        'Users' => (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'Active users' => (int)db()->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn(),
        'Appointments' => (int)db()->query('SELECT COUNT(*) FROM appointments')->fetchColumn(),
        'Resources' => (int)db()->query('SELECT COUNT(*) FROM resources')->fetchColumn(),
        'Mood check-ins' => (int)db()->query('SELECT COUNT(*) FROM check_ins')->fetchColumn(),
        'Therapists' => (int)db()->query('SELECT COUNT(*) FROM therapists WHERE is_active=1')->fetchColumn(),
    ];
    $recentUsers = db()->query('SELECT name,email,role,status,created_at FROM users ORDER BY created_at DESC LIMIT 5')->fetchAll();
    $recentAppts = db()->query('SELECT a.appt_date,a.appt_time,a.status,u.name uname,t.name tname FROM appointments a JOIN users u ON u.id=a.user_id JOIN therapists t ON t.id=a.therapist_id ORDER BY a.created_at DESC LIMIT 5')->fetchAll();
} catch (Throwable $ex) { $stats = []; $recentUsers = []; $recentAppts = []; }
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap">
    <div class="page-head reveal"><div><p class="eyebrow">ADMIN</p><h1 class="section-title">Dashboard</h1><p class="section-sub">Welcome back, <?= e((string)$user['name']) ?>. Here's your studio at a glance.</p></div></div>
    <div class="toolbar reveal">
      <a class="btn btn-primary btn-sm" href="users.php">Users</a>
      <a class="btn btn-soft btn-sm" href="therapists.php">Therapists</a>
      <a class="btn btn-soft btn-sm" href="appointments.php">Appointments</a>
      <a class="btn btn-soft btn-sm" href="resources.php">Resources</a>
      <a class="btn btn-soft btn-sm" href="crisis-resources.php">Crisis resources</a>
    </div>
    <div class="grid-3">
      <?php foreach ($stats as $k => $v): ?>
        <div class="card reveal"><p class="eyebrow"><?= e($k) ?></p><p class="stat-num"><span data-count="<?= (int)$v ?>"><?= (int)$v ?></span></p></div>
      <?php endforeach; ?>
    </div>
    <div class="grid-2" style="margin-top:1.1rem">
      <div class="card reveal"><h3>Newest users</h3>
        <?php if ($recentUsers): foreach ($recentUsers as $u): ?><p><strong><?= e((string)$u['name']) ?></strong> <span class="muted">· <?= e((string)$u['email']) ?> · <?= e((string)$u['role']) ?> · <?= e((string)$u['status']) ?></span></p><?php endforeach; ?>
        <?php else: ?><p class="muted">No users yet.</p><?php endif; ?>
      </div>
      <div class="card reveal"><h3>Latest appointments</h3>
        <?php if ($recentAppts): foreach ($recentAppts as $a): ?><p><strong><?= e((string)$a['uname']) ?></strong> → <?= e((string)$a['tname']) ?> <span class="muted">· <?= e((string)$a['appt_date']) ?> <?= e(substr((string)$a['appt_time'],0,5)) ?> · <?= e((string)$a['status']) ?></span></p><?php endforeach; ?>
        <?php else: ?><p class="muted">No appointments yet.</p><?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
