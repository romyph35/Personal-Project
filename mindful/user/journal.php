<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$base = '..';
$page_title = 'Journal';
$uid = (int)$user['id'];
$q = trim((string)($_GET['q'] ?? ''));
try {
    $moods = db()->query('SELECT * FROM moods ORDER BY score DESC')->fetchAll();
    if ($q !== '') {
        $st = db()->prepare('SELECT j.*, m.emoji FROM journal_entries j LEFT JOIN moods m ON m.id=j.mood_id WHERE j.user_id=? AND (j.title LIKE ? OR j.body LIKE ?) ORDER BY j.created_at DESC LIMIT 50');
        $st->execute([$uid, "%$q%", "%$q%"]);
    } else {
        $st = db()->prepare('SELECT j.*, m.emoji FROM journal_entries j LEFT JOIN moods m ON m.id=j.mood_id WHERE j.user_id=? ORDER BY j.created_at DESC LIMIT 50');
        $st->execute([$uid]);
    }
    $entries = $st->fetchAll();
} catch (Throwable $ex) { $moods = []; $entries = []; }
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap">
    <div class="page-head reveal">
      <div><p class="eyebrow">PRIVATE JOURNAL</p><h1 class="section-title">How are you feeling today?</h1>
      <p class="section-sub">A calm notebook only you can read. Write honestly.</p></div>
      <a class="btn btn-primary btn-sm" href="journal-edit.php">+ New entry</a>
    </div>
    <form class="toolbar reveal" method="get" role="search">
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search entries…" aria-label="Search journal entries">
      <button class="btn btn-soft btn-sm" type="submit">Search</button>
      <?php if ($q !== ''): ?><a class="btn btn-ghost btn-sm" href="journal.php">Clear</a><?php endif; ?>
    </form>
    <?php if ($entries): ?>
      <div class="grid-2">
        <?php foreach ($entries as $en): ?>
          <article class="card reveal">
            <h3><?= !empty($en['emoji']) ? e((string)$en['emoji']) . ' ' : '' ?><?= e((string)$en['title']) ?></h3>
            <p class="muted" style="font-size:.85rem"><?= e(date('M j, Y g:i A', strtotime((string)$en['created_at']))) ?></p>
            <p><?= e(mb_substr((string)$en['body'], 0, 220)) ?><?= mb_strlen((string)$en['body']) > 220 ? '…' : '' ?></p>
            <div style="display:flex;gap:.6rem">
              <a class="btn btn-soft btn-sm" href="journal-edit.php?id=<?= (int)$en['id'] ?>">Open / Edit</a>
              <form method="post" action="journal-delete.php" onsubmit="return confirm('Delete this entry?')">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$en['id'] ?>">
                <button class="btn btn-ghost btn-sm" type="submit">Delete</button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="card reveal center notebook">
        <p style="font-size:2.4rem">📝</p>
        <h3><?= $q !== '' ? 'Nothing found' : 'Your first page is blank' ?></h3>
        <p class="muted"><?= $q !== '' ? 'Try different words — or start a new entry.' : 'Write your first reflection. Even one honest sentence counts.' ?></p>
        <a class="btn btn-primary" href="journal-edit.php">Write an entry</a>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
