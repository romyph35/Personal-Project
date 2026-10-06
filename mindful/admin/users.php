<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_auth.php';
$base = '..';
$page_title = 'Manage Users';
$msg = null; $err = null;
$q = trim((string)($_GET['q'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0 && $id !== (int)$user['id']) {
        if ($action === 'disable') db()->prepare("UPDATE users SET status='disabled' WHERE id=?")->execute([$id]);
        elseif ($action === 'enable') db()->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$id]);
        elseif ($action === 'delete') db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
        elseif ($action === 'role') {
            $role = (string)($_POST['role'] ?? 'user');
            if (in_array($role, ['user','admin'], true)) db()->prepare('UPDATE users SET role=? WHERE id=?')->execute([$role, $id]);
        }
        $msg = 'User updated.';
    } elseif ($id === (int)$user['id']) { $err = 'You cannot change your own account here.'; }
}
try {
    if ($q !== '') {
        $st = db()->prepare('SELECT * FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC LIMIT 50');
        $st->execute(["%$q%", "%$q%"]);
    } else {
        $st = db()->query('SELECT * FROM users ORDER BY created_at DESC LIMIT 50');
    }
    $users = $st->fetchAll();
} catch (Throwable $ex) { $users = []; }
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap">
    <div class="page-head reveal"><div><p class="eyebrow">ADMIN</p><h1 class="section-title">Users</h1></div><a class="btn btn-soft btn-sm" href="dashboard.php">← Dashboard</a></div>
    <?php if ($msg): ?><p class="alert success"><?= e($msg) ?></p><?php endif; ?>
    <?php if ($err): ?><p class="alert error"><?= e($err) ?></p><?php endif; ?>
    <form class="toolbar reveal" method="get"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name or email…" aria-label="Search users"><button class="btn btn-soft btn-sm" type="submit">Search</button></form>
    <div class="table-wrap reveal"><table>
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Plan</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><strong><?= e((string)$u['name']) ?></strong></td><td><?= e((string)$u['email']) ?></td><td><?= e((string)$u['role']) ?></td>
          <td><?= e((string)$u['subscription_plan']) ?></td><td><span class="tag"><?= e((string)$u['status']) ?></span></td>
          <td>
            <?php if ((int)$u['id'] !== (int)$user['id']): ?>
            <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <input type="hidden" name="action" value="<?= $u['status'] === 'active' ? 'disable' : 'enable' ?>">
              <button class="btn btn-ghost btn-sm" type="submit"><?= $u['status'] === 'active' ? 'Disable' : 'Enable' ?></button>
            </form>
            <form method="post" style="display:inline" onsubmit="return confirm('Delete this user and all their data?')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-ghost btn-sm" type="submit">Delete</button></form>
            <?php else: ?><span class="muted">you</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$users): ?><tr><td colspan="6" class="muted">No users found.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
