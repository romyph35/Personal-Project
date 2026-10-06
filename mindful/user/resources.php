<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
$base = '..';
$page_title = 'Resources';
$uid = (int)$user['id'];
$q = trim((string)($_GET['q'] ?? ''));
$cat = trim((string)($_GET['cat'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $rid = (int)($_POST['resource_id'] ?? 0);
    $on = (string)($_POST['on'] ?? '1');
    if ($rid > 0) {
        if ($on === '1') {
            $st = db()->prepare('INSERT IGNORE INTO resource_bookmarks (user_id, resource_id) VALUES (?,?)');
            $st->execute([$uid, $rid]);
        } else {
            $st = db()->prepare('DELETE FROM resource_bookmarks WHERE user_id=? AND resource_id=?');
            $st->execute([$uid, $rid]);
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['ajax'])) { http_response_code(204); exit; }
        header('Location: resources.php?' . http_build_query(['q'=>$q,'cat'=>$cat]));
        exit;
    }
}
try {
    $cats = db()->query('SELECT DISTINCT category FROM resources ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
    $sql = 'SELECT r.*, (SELECT COUNT(*) FROM resource_bookmarks b WHERE b.user_id=? AND b.resource_id=r.id) saved FROM resources r WHERE 1=1';
    $params = [$uid];
    if ($cat !== '') { $sql .= ' AND r.category=?'; $params[] = $cat; }
    if ($q !== '') { $sql .= ' AND (r.title LIKE ? OR r.description LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
    $sql .= ' ORDER BY r.id DESC LIMIT 60';
    $st = db()->prepare($sql);
    $st->execute($params);
    $resources = $st->fetchAll();
} catch (Throwable $ex) { $cats = []; $resources = []; }
require __DIR__ . '/../includes/header.php';
?>
<section>
  <div class="wrap">
    <div class="page-head reveal">
      <div><p class="eyebrow">LIBRARY</p><h1 class="section-title">Tools for where you are today.</h1>
      <p class="section-sub">Short, gentle reads and practices — save what helps.</p></div>
    </div>
    <form class="toolbar reveal" method="get">
      <input type="search" id="resSearch" name="q" value="<?= e($q) ?>" placeholder="Search resources…" aria-label="Search resources">
      <select id="resCat" name="cat" aria-label="Filter by category">
        <option value="">All categories</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= e((string)$c) ?>" <?= $cat === (string)$c ? 'selected' : '' ?>><?= e((string)$c) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-soft btn-sm" type="submit">Filter</button>
    </form>
    <?php if ($resources): ?>
      <div class="grid-4">
        <?php foreach ($resources as $r): ?>
          <article class="card res-card reveal" data-res-card data-title="<?= e((string)$r['title']) ?>" data-cat="<?= e((string)$r['category']) ?>">
            <div class="res-thumb" aria-hidden="true"></div>
            <span class="tag" style="align-self:flex-start"><?= e((string)$r['category']) ?><?= !empty($r['is_premium']) ? ' · ✨' : '' ?></span>
            <h3><?= e((string)$r['title']) ?></h3>
            <p class="muted"><?= e((string)$r['description']) ?></p>
            <p class="res-meta">📖 <?= (int)$r['reading_minutes'] ?> min</p>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
              <a class="btn btn-soft btn-sm" href="resource.php?id=<?= (int)$r['id'] ?>">Read →</a>
              <form method="post" action="resources.php" data-bookmark-form>
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="resource_id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="on" value="<?= !empty($r['saved']) ? '0' : '1' ?>">
                <button class="btn btn-ghost btn-sm" type="submit" data-marked="<?= !empty($r['saved']) ? '1' : '0' ?>"><?= !empty($r['saved']) ? '✅ Saved' : '🔖 Save' ?></button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="card reveal center"><p style="font-size:2rem">📚</p><h3>No resources found</h3><p class="muted">Try different words or another category.</p><a class="btn btn-soft" href="resources.php">Clear filters</a></div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
