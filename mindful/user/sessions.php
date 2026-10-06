<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$base = '..';
$page_title = 'Therapy Sessions';
$uid = (int)$user['id'];
$msg = null; $err = null;
try {
    $therapists = db()->query('SELECT * FROM therapists WHERE is_active=1 ORDER BY rating DESC')->fetchAll();
    $mine = db()->prepare('SELECT a.*, t.name tname, t.specialty FROM appointments a JOIN therapists t ON t.id=a.therapist_id WHERE a.user_id=? ORDER BY a.appt_date DESC, a.appt_time DESC LIMIT 20');
    $mine->execute([$uid]);
    $myAppts = $mine->fetchAll();
} catch (Throwable $ex) { $therapists = []; $myAppts = []; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'book') {
        $tid = (int)($_POST['therapist_id'] ?? 0);
        $date = (string)($_POST['appt_date'] ?? '');
        $time = (string)($_POST['appt_time'] ?? '');
        $validDate = (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
        $validTime = (bool)preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time);
        if (strlen($time) === 5) $time .= ':00';
        if ($tid <= 0 || !$validDate || !$validTime) { $err = 'Please complete all booking steps.'; }
        elseif ($date < date('Y-m-d')) { $err = 'Please choose a future date.'; }
        else {
            try {
                $dup = db()->prepare('SELECT COUNT(*) FROM appointments WHERE therapist_id=? AND appt_date=? AND appt_time=? AND status IN (\'pending\',\'confirmed\')');
                $dup->execute([$tid, $date, $time]);
                if ((int)$dup->fetchColumn() > 0) { $err = 'That time is already taken. Please pick another.'; }
                else {
                    $ins = db()->prepare("INSERT INTO appointments (user_id, therapist_id, appt_date, appt_time, status) VALUES (?,?,?,?,'pending')");
                    $ins->execute([$uid, $tid, $date, $time]);
                    $cnt = (int)db()->query("SELECT COUNT(*) FROM wellness_activities WHERE user_id=$uid")->fetchColumn();
                    if ($cnt + 1 >= 10) award_achievement($uid, 'ten-sessions');
                    notify($uid, 'Session requested 📅', "Your request for $date is received. We'll confirm shortly.");
                    header('Location: sessions.php?booked=1');
                    exit;
                }
            } catch (Throwable $ex) { $err = 'Could not book. Please try again.'; }
        }
    } elseif ($action === 'cancel') {
        $aid = (int)($_POST['id'] ?? 0);
        $st = db()->prepare("UPDATE appointments SET status='cancelled' WHERE id=? AND user_id=? AND status IN ('pending','confirmed')");
        $st->execute([$aid, $uid]);
        header('Location: sessions.php?cancelled=1');
        exit;
    }
}
if (isset($_GET['booked'])) $msg = 'Session requested! Your therapist will confirm soon. 💚';
if (isset($_GET['cancelled'])) $msg = 'Appointment cancelled.';
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap">
    <div class="page-head reveal">
      <div><p class="eyebrow">CARE</p><h1 class="section-title">Support when you're ready for it.</h1>
      <p class="section-sub">Professional, human support. Book in four gentle steps.</p></div>
    </div>
    <?php if ($msg): ?><p class="alert success reveal"><?= e($msg) ?></p><?php endif; ?>
    <?php if ($err): ?><p class="alert error reveal"><?= e($err) ?></p><?php endif; ?>
    <div class="grid-3">
      <?php foreach ($therapists as $t): ?>
        <article class="card therapist-card reveal">
          <div class="avatar" aria-hidden="true">🧑‍⚕️</div>
          <h3><?= e((string)$t['name']) ?></h3>
          <p class="muted" style="margin:.2rem 0"><?= e((string)$t['specialty']) ?> · <?= (int)$t['experience_years'] ?> yrs · ⭐ <?= e((string)$t['rating']) ?></p>
          <p class="muted"><?= e((string)($t['bio'] ?? '')) ?></p>
          <p class="muted" style="font-size:.85rem">🗓️ <?= e((string)($t['session_types'] ?? 'Video')) ?> · <span style="color:var(--sage-d);font-weight:700">● Available</span></p>
          <button class="btn btn-primary btn-sm" type="button" onclick="openBooking(<?= (int)$t['id'] ?>, <?= e(json_encode((string)$t['name'])) ?>)">Book a Session</button>
        </article>
      <?php endforeach; ?>
      <?php if (!$therapists): ?><div class="card"><p class="muted">No therapists available right now. Please check back soon.</p></div><?php endif; ?>
    </div>

    <div class="card reveal" style="margin-top:1.2rem">
      <h3>Your appointments</h3>
      <?php if ($myAppts): ?>
        <div class="table-wrap"><table>
          <thead><tr><th>Therapist</th><th>Date</th><th>Time</th><th>Status</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($myAppts as $a): ?>
            <tr>
              <td><strong><?= e((string)$a['tname']) ?></strong><br><span class="muted"><?= e((string)$a['specialty']) ?></span></td>
              <td><?= e((string)$a['appt_date']) ?></td>
              <td><?= e(substr((string)$a['appt_time'], 0, 5)) ?></td>
              <td><span class="tag"><?= e((string)$a['status']) ?></span></td>
              <td>
                <?php if (in_array($a['status'], ['pending','confirmed'], true)): ?>
                <form method="post" onsubmit="return confirm('Cancel this appointment?')">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="cancel">
                  <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                  <button class="btn btn-ghost btn-sm" type="submit">Cancel</button>
                </form>
                <?php else: ?><span class="muted">—</span><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php else: ?><p class="muted">No appointments yet. Your bookings will appear here.</p><?php endif; ?>
    </div>
  </div>
</section>

<div class="modal" id="bookModal" role="dialog" aria-modal="true" aria-label="Book a session">
  <div class="modal-box">
    <h3>Book with <span id="bookTherapistName"></span></h3>
    <div class="steps" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
    <form method="post" id="bookForm">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="book">
      <input type="hidden" name="therapist_id" id="bookTherapistId" value="">
      <div class="book-step" data-step="1">
        <p class="muted">Step 1 of 4 — therapist selected ✓</p>
        <button class="btn btn-primary btn-block" type="button" onclick="goStep(2)">Continue →</button>
      </div>
      <div class="book-step" data-step="2" hidden>
        <label>Step 2 — Choose a date
          <input type="date" name="appt_date" id="bookDate" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
        </label>
        <div style="display:flex;gap:.6rem"><button class="btn btn-soft" type="button" onclick="goStep(1)">← Back</button><button class="btn btn-primary" type="button" onclick="goStep(3)">Continue →</button></div>
      </div>
      <div class="book-step" data-step="3" hidden>
        <label>Step 3 — Choose a time
          <select name="appt_time" required>
            <option value="">Select…</option>
            <?php foreach (['09:00','10:30','12:00','14:00','15:30','17:00','18:30'] as $slot): ?>
              <option value="<?= $slot ?>"><?= $slot ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <div style="display:flex;gap:.6rem"><button class="btn btn-soft" type="button" onclick="goStep(2)">← Back</button><button class="btn btn-primary" type="button" onclick="goStep(4)">Review →</button></div>
      </div>
      <div class="book-step" data-step="4" hidden>
        <p class="muted">Step 4 — confirm your request. The therapist confirms shortly, and you'll get a notification.</p>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap"><button class="btn btn-soft" type="button" onclick="goStep(3)">← Back</button><button class="btn btn-primary" type="submit">Confirm Booking 🌿</button><button class="btn btn-ghost" type="button" onclick="closeBooking()">Cancel</button></div>
      </div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
