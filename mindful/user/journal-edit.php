<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$base = '..';
$uid = (int)$user['id'];
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$entry = null;
try {
    $moods = db()->query('SELECT * FROM moods ORDER BY score DESC')->fetchAll();
    if ($id > 0) {
        $st = db()->prepare('SELECT * FROM journal_entries WHERE id=? AND user_id=? LIMIT 1');
        $st->execute([$id, $uid]);
        $entry = $st->fetch() ?: null;
        if (!$entry) { http_response_code(404); exit('Entry not found.'); }
    }
} catch (Throwable $ex) { $moods = []; }
$page_title = $entry ? 'Edit Entry' : 'New Entry';
$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim((string)($_POST['title'] ?? ''));
    $body = trim((string)($_POST['body'] ?? ''));
    $mood_id = (int)($_POST['mood_id'] ?? 0) ?: null;
    if ($title === '' || mb_strlen($title) > 150) $err = 'Please give your entry a short title.';
    elseif ($body === '') $err = 'Please write something — even one sentence.';
    else {
        try {
            if ($entry) {
                $up = db()->prepare('UPDATE journal_entries SET title=?, body=?, mood_id=? WHERE id=? AND user_id=?');
                $up->execute([$title, $body, $mood_id, $entry['id'], $uid]);
            } else {
                $ins = db()->prepare('INSERT INTO journal_entries (user_id, title, body, mood_id) VALUES (?,?,?,?)');
                $ins->execute([$uid, $title, $body, $mood_id]);
            }
            notify($uid, 'Journal saved 📝', 'Your reflection is safely stored.');
            header('Location: journal.php');
            exit;
        } catch (Throwable $ex) { $err = 'Could not save. Please try again.'; }
    }
}
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap" style="max-width:720px">
    <div class="card reveal notebook">
      <p class="eyebrow">JOURNAL</p>
      <h1 class="section-title"><?= $entry ? 'Edit your reflection' : 'Write a reflection' ?></h1>
      <?php if ($err): ?><p class="alert error"><?= e($err) ?></p><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <?php if ($entry): ?><input type="hidden" name="id" value="<?= (int)$entry['id'] ?>"><?php endif; ?>
        <label>Title
          <input type="text" name="title" maxlength="150" required value="<?= e($_POST['title'] ?? $entry['title'] ?? '') ?>" placeholder="A title for today…">
        </label>
        <label>Mood alongside this entry (optional)
          <select name="mood_id">
            <option value="0">— No mood —</option>
            <?php $sel = (int)($_POST['mood_id'] ?? $entry['mood_id'] ?? 0); ?>
            <?php foreach ($moods as $m): ?>
              <option value="<?= (int)$m['id'] ?>" <?= $sel === (int)$m['id'] ? 'selected' : '' ?>><?= e((string)$m['emoji'] . ' ' . $m['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Your words
          <textarea name="body" required placeholder="Write freely — this is only for you…"><?= e($_POST['body'] ?? $entry['body'] ?? '') ?></textarea>
        </label>
        <div style="display:flex;gap:.6rem;flex-wrap:wrap">
          <button class="btn btn-primary" type="submit">Save entry</button>
          <a class="btn btn-soft" href="journal.php">Back to journal</a>
        </div>
      </form>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
