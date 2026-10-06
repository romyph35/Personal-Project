<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$base = '..';
$uid = (int)$user['id'];
$id = (int)($_GET['id'] ?? 0);
try {
    $st = db()->prepare('SELECT r.*, (SELECT COUNT(*) FROM resource_bookmarks b WHERE b.user_id=? AND b.resource_id=r.id) saved FROM resources r WHERE r.id=? LIMIT 1');
    $st->execute([$uid, $id]);
    $r = $st->fetch();
} catch (Throwable $ex) { $r = false; }
if (!$r) { http_response_code(404); exit('Resource not found.'); }
$page_title = $r['title'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $on = (string)($_POST['on'] ?? '1');
    if ($on === '1') {
        db()->prepare('INSERT IGNORE INTO resource_bookmarks (user_id, resource_id) VALUES (?,?)')->execute([$uid, $id]);
        $cnt = (int)db()->query("SELECT COUNT(*) FROM resource_bookmarks WHERE user_id=$uid")->fetchColumn();
        if ($cnt >= 10) award_achievement($uid, 'ten-reads');
    } else {
        db()->prepare('DELETE FROM resource_bookmarks WHERE user_id=? AND resource_id=?')->execute([$uid, $id]);
    }
    header('Location: resource.php?id=' . $id);
    exit;
}
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap" style="max-width:760px">
    <article class="card reveal">
      <div class="res-thumb" aria-hidden="true" style="height:180px"></div>
      <p style="margin:.9rem 0 0"><span class="tag"><?= e((string)$r['category']) ?></span> <span class="muted">· 📖 <?= (int)$r['reading_minutes'] ?> min</span></p>
      <h1 class="section-title"><?= e((string)$r['title']) ?></h1>
      <p class="lead"><?= e((string)$r['description']) ?></p>
      <div style="white-space:pre-line"><?= e((string)($r['body'] ?? $r['description'])) ?></div>
      <form method="post" style="margin-top:1.2rem" data-bookmark-form action="resource.php?id=<?= (int)$r['id'] ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="on" value="<?= !empty($r['saved']) ? '0' : '1' ?>">
        <button class="btn <?= !empty($r['saved']) ? 'btn-soft' : 'btn-primary' ?>" type="submit" data-marked="<?= !empty($r['saved']) ? '1' : '0' ?>"><?= !empty($r['saved']) ? '✅ Saved to your library' : '🔖 Save to my library' ?></button>
        <a class="btn btn-ghost" href="resources.php">← All resources</a>
      </form>
    </article>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
