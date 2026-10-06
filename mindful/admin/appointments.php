<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_auth.php';
$base = '..';
$page_title = 'Manage Appointments';
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $status = (string)($_POST['status'] ?? '');
    if ($id > 0 && in_array($status, ['pending','confirmed','cancelled','completed'], true)) {
        db()->prepare('UPDATE appointments SET status=? WHERE id=?')->execute([$status, $id]);
        $msg = 'Appointment updated to ' . $status . '.';
    }
}
try {
    $rows = db()->query('SELECT a.*, u.name uname, u.email uemail, t.name tname FROM appointments a JOIN users u ON u.id=a.user_id JOIN therapists t ON t.id=a.therapist_id ORDER BY a.appt_date DESC, a.appt_time DESC LIMIT 100')->fetchAll();
} catch (Throwable $ex) { $rows = []; }
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap">
    <div class="page-head reveal"><div><p class="eyebrow">ADMIN</p><h1 class="section-title">Appointments</h1></div><a class="btn btn-soft btn-sm" href="dashboard.php">← Dashboard</a></div>
    <?php if ($msg): ?><p class="alert success"><?= e($msg) ?></p><?php endif; ?>
    <div class="table-wrap reveal"><table>
      <thead><tr><th>User</th><th>Therapist</th><th>Date</th><th>Time</th><th>Status</th><th>Change</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $a): ?>
        <tr>
          <td><strong><?= e((string)$a['uname']) ?></strong><br><span class="muted"><?= e((string)$a['uemail']) ?></span></td>
          <td><?= e((string)$a['tname']) ?></td>
          <td><?= e((string)$a['appt_date']) ?></td>
          <td><?= e(substr((string)$a['appt_time'], 0, 5)) ?></td>
          <td><span class="tag"><?= e((string)$a['status']) ?></span></td>
          <td><form method="post" style="display:flex;gap:.4rem"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
            <select name="status" aria-label="Change status"><?php foreach (['pending','confirmed','cancelled','completed'] as $s): ?><option value="<?= $s ?>" <?= $a['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
            <button class="btn btn-soft btn-sm" type="submit">Save</button></form></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="muted">No appointments yet.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
