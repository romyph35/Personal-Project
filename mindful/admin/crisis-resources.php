<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_auth.php';
$base = '..';
$page_title = 'Manage Crisis Resources';
$msg = null; $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'delete') {
        db()->prepare('DELETE FROM crisis_resources WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
        $msg = 'Crisis resource deleted.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $country = trim((string)($_POST['country'] ?? ''));
        $name = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $website = trim((string)($_POST['website'] ?? ''));
        $avail = trim((string)($_POST['availability'] ?? '24/7'));
        $desc = trim((string)($_POST['description'] ?? ''));
        if ($country === '' || $name === '') { $err = 'Country and name are required.'; }
        elseif ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) { $err = 'Website must be a valid URL.'; }
        else {
            if ($id > 0) {
                db()->prepare('UPDATE crisis_resources SET country=?, name=?, phone=?, website=?, availability=?, description=? WHERE id=?')
                    ->execute([$country, $name, $phone ?: null, $website ?: null, $avail, $desc ?: null, $id]);
                $msg = 'Crisis resource updated.';
            } else {
                db()->prepare('INSERT INTO crisis_resources (country, name, phone, website, availability, description) VALUES (?,?,?,?,?,?)')
                    ->execute([$country, $name, $phone ?: null, $website ?: null, $avail, $desc ?: null]);
                $msg = 'Crisis resource added.';
            }
        }
    }
}
$edit = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare('SELECT * FROM crisis_resources WHERE id=? LIMIT 1');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
try { $list = db()->query('SELECT * FROM crisis_resources ORDER BY country, name')->fetchAll(); }
catch (Throwable $ex) { $list = []; }
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap">
    <div class="page-head reveal"><div><p class="eyebrow">ADMIN</p><h1 class="section-title">Crisis resources</h1><p class="section-sub">Kept in the database so numbers stay current — never hard-coded.</p></div><a class="btn btn-soft btn-sm" href="dashboard.php">← Dashboard</a></div>
    <?php if ($msg): ?><p class="alert success"><?= e($msg) ?></p><?php endif; ?>
    <?php if ($err): ?><p class="alert error"><?= e($err) ?></p><?php endif; ?>
    <div class="grid-2">
      <div class="card reveal">
        <h3><?= $edit ? 'Edit resource' : 'Add resource' ?></h3>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
          <div class="grid-2">
            <label>Country / region<input type="text" name="country" required value="<?= e((string)($edit['country'] ?? '')) ?>"></label>
            <label>Name<input type="text" name="name" required value="<?= e((string)($edit['name'] ?? '')) ?>"></label>
          </div>
          <div class="grid-2">
            <label>Phone<input type="text" name="phone" value="<?= e((string)($edit['phone'] ?? '')) ?>"></label>
            <label>Availability<input type="text" name="availability" value="<?= e((string)($edit['availability'] ?? '24/7')) ?>"></label>
          </div>
          <label>Website<input type="url" name="website" value="<?= e((string)($edit['website'] ?? '')) ?>" placeholder="https://…"></label>
          <label>Description<textarea name="description"><?= e((string)($edit['description'] ?? '')) ?></textarea></label>
          <div style="display:flex;gap:.5rem"><button class="btn btn-primary btn-sm" type="submit"><?= $edit ? 'Save' : 'Add resource' ?></button><?php if ($edit): ?><a class="btn btn-ghost btn-sm" href="crisis-resources.php">Cancel</a><?php endif; ?></div>
        </form>
      </div>
      <div class="card reveal"><h3>All crisis resources</h3>
        <?php foreach ($list as $c): ?>
          <p><strong><?= e((string)$c['name']) ?></strong> <span class="muted">· <?= e((string)$c['country']) ?> · <?= e((string)$c['phone'] ?? '') ?></span><br>
          <a href="crisis-resources.php?edit=<?= (int)$c['id'] ?>">Edit</a> ·
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this crisis resource?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit">Delete</button></form></p>
        <?php endforeach; ?>
        <?php if (!$list): ?><p class="muted">No crisis resources yet.</p><?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
