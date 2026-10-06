<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_auth.php';
$base = '..';
$page_title = 'Manage Therapists';
$msg = null; $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM therapists WHERE id=?')->execute([$id]);
        $msg = 'Therapist deleted.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $specialty = trim((string)($_POST['specialty'] ?? ''));
        $exp = max(0, min(60, (int)($_POST['experience_years'] ?? 3)));
        $rating = max(1, min(5, (float)($_POST['rating'] ?? 4.8)));
        $types = trim((string)($_POST['session_types'] ?? 'Video'));
        $bio = trim((string)($_POST['bio'] ?? ''));
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($name === '' || $specialty === '') { $err = 'Name and specialty are required.'; }
        else {
            if ($id > 0) {
                db()->prepare('UPDATE therapists SET name=?, specialty=?, experience_years=?, rating=?, session_types=?, bio=?, is_active=? WHERE id=?')
                    ->execute([$name, $specialty, $exp, $rating, $types, $bio, $active, $id]);
                $msg = 'Therapist updated.';
            } else {
                db()->prepare('INSERT INTO therapists (name, specialty, experience_years, rating, session_types, bio, is_active) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$name, $specialty, $exp, $rating, $types, $bio, $active]);
                $msg = 'Therapist added.';
            }
        }
    }
}
$edit = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare('SELECT * FROM therapists WHERE id=? LIMIT 1');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
try { $list = db()->query('SELECT * FROM therapists ORDER BY name')->fetchAll(); }
catch (Throwable $ex) { $list = []; }
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap">
    <div class="page-head reveal"><div><p class="eyebrow">ADMIN</p><h1 class="section-title">Therapists</h1></div><a class="btn btn-soft btn-sm" href="dashboard.php">← Dashboard</a></div>
    <?php if ($msg): ?><p class="alert success"><?= e($msg) ?></p><?php endif; ?>
    <?php if ($err): ?><p class="alert error"><?= e($err) ?></p><?php endif; ?>
    <div class="grid-2">
      <div class="card reveal">
        <h3><?= $edit ? 'Edit therapist' : 'Add therapist' ?></h3>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
          <label>Name<input type="text" name="name" required value="<?= e((string)($edit['name'] ?? '')) ?>"></label>
          <label>Specialty<input type="text" name="specialty" required value="<?= e((string)($edit['specialty'] ?? '')) ?>"></label>
          <div class="grid-2">
            <label>Experience (yrs)<input type="number" name="experience_years" min="0" max="60" value="<?= (int)($edit['experience_years'] ?? 3) ?>"></label>
            <label>Rating (1–5)<input type="number" name="rating" step="0.1" min="1" max="5" value="<?= e((string)($edit['rating'] ?? '4.8')) ?>"></label>
          </div>
          <label>Session types<input type="text" name="session_types" value="<?= e((string)($edit['session_types'] ?? 'Video, Audio')) ?>"></label>
          <label>Bio<textarea name="bio"><?= e((string)($edit['bio'] ?? '')) ?></textarea></label>
          <label style="display:flex;gap:.5rem;align-items:center"><input type="checkbox" name="is_active" value="1" style="width:auto" <?= ((int)($edit['is_active'] ?? 1)) ? 'checked' : '' ?>> Active</label>
          <div style="display:flex;gap:.5rem"><button class="btn btn-primary btn-sm" type="submit"><?= $edit ? 'Save' : 'Add therapist' ?></button><?php if ($edit): ?><a class="btn btn-ghost btn-sm" href="therapists.php">Cancel</a><?php endif; ?></div>
        </form>
      </div>
      <div class="card reveal"><h3>All therapists</h3>
        <?php foreach ($list as $t): ?>
          <p><strong><?= e((string)$t['name']) ?></strong> <span class="muted">· <?= e((string)$t['specialty']) ?> · ⭐ <?= e((string)$t['rating']) ?> · <?= !empty($t['is_active']) ? 'active' : 'hidden' ?></span><br>
          <a href="therapists.php?edit=<?= (int)$t['id'] ?>">Edit</a> ·
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this therapist?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit">Delete</button></form></p>
        <?php endforeach; ?>
        <?php if (!$list): ?><p class="muted">No therapists yet.</p><?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
