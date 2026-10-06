<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
$user = current_user();
$base = '.';
$page_title = 'A calmer mind starts here';
$page_desc = 'Mindful — mood check-ins, journaling, therapy sessions, resources and wellness tools.';
$therapists = [];
$resources = [];
$crisis = [];
try {
    $therapists = db()->query("SELECT * FROM therapists WHERE is_active=1 ORDER BY rating DESC LIMIT 3")->fetchAll();
    $resources = db()->query("SELECT * FROM resources ORDER BY id DESC LIMIT 4")->fetchAll();
    $crisis = db()->query("SELECT * FROM crisis_resources ORDER BY id ASC LIMIT 6")->fetchAll();
} catch (Throwable $ex) { /* static fallback below */ }
if (!$therapists) {
    $therapists = [
        ['name' => 'Dr. Maya Santos', 'specialty' => 'Anxiety & Stress', 'rating' => '4.9', 'bio' => 'Warm, practical support for stress, overthinking, and burnout.'],
        ['name' => 'James Carter, LMFT', 'specialty' => 'Relationships', 'rating' => '4.8', 'bio' => 'Helps couples and individuals build calmer communication.'],
        ['name' => 'Dr. Priya Nair', 'specialty' => 'Sleep & Mindfulness', 'rating' => '5.0', 'bio' => 'Specialist in sleep habits, mindfulness, and evening routines.'],
    ];
}
if (!$resources) {
    $resources = [
        ['id'=>0,'category'=>'Stress','title'=>'A 5-minute reset for busy days','description'=>'A short breathing and grounding routine to lower tension.','reading_minutes'=>5],
        ['id'=>0,'category'=>'Sleep','title'=>'Wind down for deeper rest','description'=>'A gentle evening routine for better sleep.','reading_minutes'=>6],
        ['id'=>0,'category'=>'Mindfulness','title'=>'Notice one moment fully','description'=>'A one-minute mindfulness pause you can do anywhere.','reading_minutes'=>4],
        ['id'=>0,'category'=>'Anxiety','title'=>'Untangle worried thoughts','description'=>'Write worries down, then sort what you can act on.','reading_minutes'=>7],
    ];
}
if (!$crisis) {
    $crisis = [
        ['country'=>'Global','name'=>'Find a Helpline International','phone'=>'—','website'=>'https://findahelpline.org','availability'=>'Varies','description'=>'Directory of helplines by country.'],
        ['country'=>'USA','name'=>'988 Suicide and Crisis Lifeline','phone'=>'988','website'=>'https://988lifeline.org','availability'=>'24/7','description'=>'Call or text 988 for support in the US.'],
        ['country'=>'UK & Ireland','name'=>'Samaritans','phone'=>'116 123','website'=>'https://www.samaritans.org','availability'=>'24/7','description'=>'Free listening support, day or night.'],
    ];
}
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="hero-inner">
    <div class="reveal">
      <span class="eyebrow">YOUR SPACE TO RESET</span>
      <h1>A calmer mind starts with <span class="grad">one small step.</span></h1>
      <p class="lead">Mindful is your premium daily companion — check in with your mood, journal freely, book caring therapists, and grow with gentle wellness tools.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="<?= $user ? e($base) . '/user/dashboard.php' : e($base) . '/register.php' ?>">Start Your Journey</a>
        <a class="btn btn-soft" href="#resources">Explore Resources</a>
      </div>
      <p class="trust"><span>⭐ 4.9 loved by members</span><span>🔒 Private by design</span><span>🌿 Free to start</span></p>
    </div>
    <div class="dash-card reveal" aria-label="App preview">
      <div class="dash-head">
        <strong>🌤️ Today</strong>
        <span class="pill"><span data-count="12">12</span> day streak 🔥</span>
      </div>
      <div class="dash-grid">
        <div class="mini-card">
          <h4>Today's mood</h4>
          <div class="mood-row"><span class="on">😊</span><span>🙂</span><span>😐</span></div>
          <p class="muted" style="margin:.4rem 0 0">Feeling bright</p>
        </div>
        <div class="mini-card center">
          <h4>Wellness score</h4>
          <div class="ring" style="--p:78"><b>78</b></div>
        </div>
        <div class="mini-card">
          <h4>Weekly mood</h4>
          <svg viewBox="0 0 200 70" width="100%" height="64" role="img" aria-label="Weekly mood trend rising">
            <polyline points="5,55 35,48 65,52 95,38 125,42 155,28 190,22" fill="none" stroke="#5b8fd6" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="190" cy="22" r="5" fill="#5f9e6f"/>
          </svg>
        </div>
        <div class="mini-card">
          <h4>Upcoming session</h4>
          <p style="margin:0"><strong>Dr. Maya Santos</strong><br><span class="muted">Thu · 4:00 PM · Video</span></p>
        </div>
      </div>
      <div class="mini-card" style="margin-top:.9rem">
        <h4>📝 Reflection</h4>
        <p class="muted" style="margin:0">“Today I noticed three good things…” — continue in your journal.</p>
      </div>
    </div>
  </div>
</section>

<section id="mood">
  <div class="wrap card reveal center">
    <p class="eyebrow">DAILY CHECK-IN</p>
    <h2 class="section-title">How are you feeling right now?</h2>
    <p class="section-sub">One tap. No judgment. It takes 10 seconds.</p>
    <form method="post" action="<?= e($base) ?>/user/mood.php">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="mood-pick" data-mood-group data-confirm="#moodConfirmHome" role="radiogroup" aria-label="Choose your mood">
        <?php $moodsHome = [['id'=>1,'e'=>'😊','l'=>'Great'],['id'=>2,'e'=>'🙂','l'=>'Good'],['id'=>3,'e'=>'😐','l'=>'Okay'],['id'=>4,'e'=>'😔','l'=>'Low'],['id'=>5,'e'=>'😢','l'=>'Very Low']]; ?>
        <?php foreach ($moodsHome as $m): ?>
          <button type="submit" name="mood_id" value="<?= (int)$m['id'] ?>" class="mood-btn" data-label="<?= e($m['l']) ?>" aria-label="<?= e($m['l']) ?>"><?= e($m['e']) ?><small><?= e($m['l']) ?></small></button>
        <?php endforeach; ?>
      </div>
      <p class="mood-confirm" id="moodConfirmHome" aria-live="polite"></p>
      <p class="muted">Log in to save notes, tags &amp; streaks — or <a href="user/mood.php">open the full check-in</a>.</p>
    </form>
  </div>
</section>

<section>
  <div class="wrap grid-2">
    <div class="card reveal">
      <p class="eyebrow">INSIGHTS</p>
      <h2 class="section-title">See your week at a glance</h2>
      <p class="section-sub">Gentle analytics show patterns — sleep, streaks, and brighter days.</p>
      <div class="bars" aria-hidden="true">
        <?php $bars = [['M',45],['T',60],['W',52],['T',70],['F',82],['S',66],['S',78]]; ?>
        <?php foreach ($bars as $b): ?>
          <div class="bar" data-h="<?= (int)$b[1] ?>%" style="height:<?= (int)$b[1] ?>%"></div>
        <?php endforeach; ?>
      </div>
      <div class="bar-labels"><?php foreach ($bars as $b): ?><span><?= e($b[0]) ?></span><?php endforeach; ?></div>
      <p><strong><span data-count="78">78</span>%</strong> <span class="muted">brighter week · <strong><span data-count="12">12</span>-day</strong> streak</span></p>
    </div>
    <div class="card reveal" id="wellness">
      <p class="eyebrow">BREATHE</p>
      <h2 class="section-title">Try a 30-second reset</h2>
      <p class="section-sub">Follow the circle: in… hold… out.</p>
      <div class="breathe-wrap">
        <div class="breathe-circle" id="breatheCircle"><span id="breathePhase">Ready?</span></div>
        <p class="muted" id="breatheTimer">1:00</p>
        <div class="breathe-controls">
          <button class="btn btn-primary btn-sm" type="button" onclick="startBreathing(1)">Start 1 min</button>
          <a class="btn btn-soft btn-sm" href="user/wellness.php">Open wellness tools</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section>
  <div class="wrap">
    <div class="page-head reveal">
      <div><p class="eyebrow">THERAPISTS</p><h2 class="section-title">Caring humans, when you need them</h2></div>
      <a class="btn btn-soft btn-sm" href="user/sessions.php">View all &amp; book</a>
    </div>
    <div class="grid-3">
      <?php foreach ($therapists as $t): ?>
        <article class="card therapist-card reveal">
          <div class="avatar" aria-hidden="true">🧑‍⚕️</div>
          <h3><?= e((string)($t['name'] ?? '')) ?></h3>
          <p class="muted" style="margin:0"><?= e((string)($t['specialty'] ?? '')) ?> · ⭐ <?= e((string)($t['rating'] ?? '4.9')) ?></p>
          <p class="muted"><?= e(mb_substr((string)($t['bio'] ?? ''), 0, 110)) ?></p>
          <a class="btn btn-primary btn-sm" href="user/sessions.php">Book session</a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="resources" style="padding-top:0">
  <div class="wrap">
    <div class="page-head reveal">
      <div><p class="eyebrow">LEARN</p><h2 class="section-title">Resources for every mood</h2></div>
      <a class="btn btn-soft btn-sm" href="user/resources.php">Browse library</a>
    </div>
    <div class="grid-4">
      <?php foreach ($resources as $r): ?>
        <article class="card res-card reveal">
          <div class="res-thumb" aria-hidden="true"></div>
          <span class="tag" style="align-self:flex-start"><?= e((string)($r['category'] ?? 'Wellness')) ?></span>
          <h3><?= e((string)($r['title'] ?? '')) ?></h3>
          <p class="muted"><?= e(mb_substr((string)($r['description'] ?? ''), 0, 110)) ?></p>
          <p class="res-meta">📖 <?= (int)($r['reading_minutes'] ?? 5) ?> min read</p>
          <?= ((int)($r['id'] ?? 0) > 0) ? '<a href="user/resource.php?id=' . (int)$r['id'] . '">Read →</a>' : '<a href="user/resources.php">Read →</a>' ?>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section style="padding-top:0">
  <div class="wrap card reveal center">
    <p class="eyebrow">GROWTH</p>
    <h2 class="section-title">Small wins, celebrated</h2>
    <p class="section-sub">Unlock achievements as you check in, breathe, read, and reflect.</p>
    <div class="ach-row" style="justify-content:center">
      <span class="ach">🌱 First Check-in</span>
      <span class="ach">🌿 7 Day Reflection</span>
      <span class="ach">✨ 30 Check-ins</span>
      <span class="ach">🧘 10 Wellness Sessions</span>
      <span class="ach">📖 10 Resources Read</span>
    </div>
  </div>
</section>

<section id="crisis" class="crisis">
  <div class="wrap">
    <div class="card crisis-card reveal">
      <p class="eyebrow">YOU ARE NOT ALONE</p>
      <h2 class="section-title">Crisis support — reach out now 🤍</h2>
      <p class="section-sub">If you or someone you love is struggling, please contact one of these services right away. Help is available.</p>
      <div class="crisis-grid">
        <?php foreach ($crisis as $c): ?>
          <div class="mini-card">
            <strong><?= e((string)$c['name']) ?></strong>
            <p class="muted" style="margin:.3rem 0">📍 <?= e((string)$c['country']) ?> · ⏰ <?= e((string)$c['availability']) ?></p>
            <?php if (!empty($c['phone']) && $c['phone'] !== '—'): ?><p style="margin:.2rem 0">📞 <strong><?= e((string)$c['phone']) ?></strong></p><?php endif; ?>
            <?php if (!empty($c['website'])): ?><p style="margin:.2rem 0"><a href="<?= e((string)$c['website']) ?>" target="_blank" rel="noopener">Visit website →</a></p><?php endif; ?>
            <?php if (!empty($c['description'])): ?><p class="muted" style="margin:.2rem 0"><?= e((string)$c['description']) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section id="pricing">
  <div class="wrap">
    <div class="center reveal"><p class="eyebrow">PLANS</p><h2 class="section-title">Simple, honest pricing</h2><p class="section-sub">Start free. Upgrade only if it helps you grow.</p></div>
    <div class="pricing">
      <div class="card price reveal"><h3>Free</h3><p class="stat-num">$0</p><p class="muted">Mood check-ins · Journal · Breathing tools</p><a class="btn btn-soft btn-block" href="register.php">Start free</a></div>
      <div class="card price hl reveal"><span class="flag">MOST LOVED</span><h3>Plus</h3><p class="stat-num">$8<span class="muted">/mo</span></p><p class="muted">Everything in Free · Full library · Streak insights · Priority booking</p><a class="btn btn-primary btn-block" href="register.php">Choose Plus</a></div>
      <div class="card price reveal"><h3>Premium</h3><p class="stat-num">$15<span class="muted">/mo</span></p><p class="muted">Everything in Plus · Monthly therapist session credit · Advanced analytics</p><a class="btn btn-soft btn-block" href="register.php">Choose Premium</a></div>
    </div>
  </div>
</section>

<section style="padding-top:0">
  <div class="wrap card reveal center" style="background:linear-gradient(135deg,var(--blue),var(--sage) 50%,var(--lav));border:0">
    <h2 class="section-title">Tonight, sleep a little lighter. 🌙</h2>
    <p>Join thousands building calmer days — one check-in at a time.</p>
    <a class="btn btn-primary" href="<?= $user ? 'user/dashboard.php' : 'register.php' ?>">Start Your Journey</a>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
