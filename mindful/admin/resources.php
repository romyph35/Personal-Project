<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_auth.php';
$base = '..';
$page_title = 'Manage Resources';
$msg = null; $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'delete') {
        db()->prepare('DELETE FROM resources WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
        $msg = 'Resource deleted.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $category = trim((string)($_POST['category'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));
        $body = trim((string)($_POST['body'] ?? ''));
        $mins = max(1, min(120, (int)($_POST['reading_minutes'] ?? 5)));
        $prem = isset($_POST['is_premium']) ? 1 : 0;
        if ($category === '' || $title === '' || $desc === '') { $err = 'Category, title and description are required.'; }
        else {
            if ($id > 0) {
                db()->prepare('UPDATE resources SET category=?, title=?, description=?, body=?, reading_minutes=?, is_premium=? WHERE id=?')
                    ->execute([$category, $title, $desc, $body, $mins, $prem, $id]);
                $msg = 'Resource updated.';
            } else {
                db()->prepare('INSERT INTO resources (category, title, description, body, reading_minutes, is_premium) VALUES (?,?,?,?,?,?)')
                    ->execute([$category, $title, $desc, $body, $mins, $prem]);
                $msg = 'Resource added.';
            }
        }
    }
}
$edit = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare('SELECT * FROM resources WHERE id=? LIMIT 1');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
try { $list = db()->query('SELECT * FROM resources ORDER BY id DESC LIMIT 100')->fetchAll(); }
catch (Throwable $ex) { $list = []; }
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap">
    <div class="page-head reveal"><div><p class="eyebrow">ADMIN</p><h1 class="section-title">Resources</h1></div><a class="btn btn-soft btn-sm" href="dashboard.php">← Dashboard</a></div>
    <?php if ($msg): ?><p class="alert success"><?= e($msg) ?></p><?php endif; ?>
    <?php if ($err): ?><p class="alert error"><?= e($err) ?></p><?php endif; ?>
    <div class="grid-2">
      <div class="card reveal">
        <h3><?= $edit ? 'Edit resource' : 'Add resource' ?></h3>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
          <label>Category<input type="text" name="category" required list="catList" value="<?= e((string)($edit['category'] ?? '')) ?>" placeholder="Anxiety, Sleep, Stress…"></label>
          <datalist id="catList"><option>Anxiety</option><option>Stress</option><option>Sleep</option><option>Mindfulness</option><option>Self-care</option><option>Relationships</option><option>Emotional Wellness</option><option>Personal Growth</option></datalist>
          <label>Title<input type="text" name="title" required maxlength="160" value="<?= e((string)($edit['title'] ?? '')) ?>"></label>
          <label>Short description<input type="text" name="description" required maxlength="280" value="<?= e((string)($edit['description'] ?? '')) ?>"></label>
          <label>Full text<textarea name="body"><?= e((string)($edit['body'] ?? '')) ?></textarea></label>
          <div class="grid-2">
            <label>Reading minutes<input type="number" name="reading_minutes" min="1" max="120" value="<?= (int)($edit['reading_minutes'] ?? 5) ?>"></label>
            <label style="display:flex;gap:.5rem;align-items:center"><input type="checkbox" name="is_premium" value="1" style="width:auto" <?= !empty($edit['is_premium']) ? 'checked' : '' ?>> Premium</label>
          </div>
          <div style="display:flex;gap:.5rem"><button class="btn btn-primary btn-sm" type="submit"><?= $edit ? 'Save' : 'Add resource' ?></button><?php if ($edit): ?><a class="btn btn-ghost btn-sm" href="resources.php">Cancel</a><?php endif; ?></div>
        </form>
      </div>
      <div class="card reveal"><h3>All resources</h3>
        <?php foreach ($list as $r): ?>
          <p><span class="tag"><?= e((string)$r['category']) ?></span> <strong><?= e((string)$r['title']) ?></strong><br>
          <a href="resources.php?edit=<?= (int)$r['id'] ?>">Edit</a> ·
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this resource?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit">Delete</button></form></p>
        <?php endforeach; ?>
        <?php if (!$list): ?><p class="muted">No resources yet.</p><?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
