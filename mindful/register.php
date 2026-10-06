<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
$user = current_user();
$base = '.';
$page_title = 'Create Account';
if ($user) { header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php')); exit; }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $p1 = (string)($_POST['password'] ?? '');
    $p2 = (string)($_POST['password2'] ?? '');
    if (mb_strlen($name) < 2) { $errors[] = 'Please enter your name.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Please enter a valid email address.'; }
    if (mb_strlen($p1) < 8) { $errors[] = 'Password must be at least 8 characters.'; }
    if ($p1 !== $p2) { $errors[] = 'Passwords do not match.'; }
    if (!$errors) {
        try {
            $st = db()->prepare('INSERT INTO users (name, email, password_hash, role, subscription_plan) VALUES (?,?,?,\'user\',\'free\')');
            $st->execute([$name, $email, password_hash($p1, PASSWORD_DEFAULT)]);
            $uid = (int)db()->lastInsertId();
            $sub = db()->prepare('INSERT INTO subscriptions (user_id, plan, status) VALUES (?, \'free\', \'active\') ON DUPLICATE KEY UPDATE plan=\'free\', status=\'active\'');
            $sub->execute([$uid]);
            notify($uid, 'Welcome to Mindful 🌿', 'Your calm space is ready. Start with a quick mood check-in.');
            header('Location: login.php?registered=1');
            exit;
        } catch (PDOException $ex) {
            $errors[] = (str_contains($ex->getMessage(), 'Duplicate') || $ex->getCode() === '23000')
                ? 'That email is already registered. Try logging in.'
                : 'Something went wrong. Please try again.';
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
  <div class="auth-card reveal">
    <p class="eyebrow">Join Mindful</p>
    <h1>Begin your calmer journey</h1>
    <p class="muted">Free forever plan · No card needed · 2 minutes to start ✨</p>
    <?php foreach ($errors as $er): ?><p class="alert error"><?= e($er) ?></p><?php endforeach; ?>
    <form method="post" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label> Full name
        <input type="text" name="name" required autocomplete="name" value="<?= e($_POST['name'] ?? '') ?>" placeholder="Your name">
      </label>
      <label> Email
        <input type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@example.com">
      </label>
      <div class="grid-2">
        <label> Password
          <input type="password" name="password" required autocomplete="new-password" placeholder="Min. 8 characters">
        </label>
        <label> Confirm
          <input type="password" name="password2" required autocomplete="new-password" placeholder="Repeat password">
        </label>
      </div>
      <button class="btn btn-primary btn-block" type="submit">Create My Account</button>
    </form>
    <p class="muted center">Already have an account? <a href="login.php">Log in</a></p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
