<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$user = current_user();
$base = '.';
$page_title = 'Log In';
if ($user) { header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php')); exit; }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Please enter a valid email address.'; }
    if ($password === '') { $errors[] = 'Please enter your password.'; }
    if (!$errors) {
        $st = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $found = $st->fetch();
        if ($found && password_verify($password, (string)$found['password_hash'])) {
            if ($found['status'] !== 'active') {
                $errors[] = 'This account has been disabled. Please contact support.';
            } else {
                session_regenerate_id(true);
                $_SESSION['uid'] = (int)$found['id'];
                $dest = $found['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php';
                header('Location: ' . $dest);
                exit;
            }
        } else {
            $errors[] = 'Email or password is incorrect.';
        }
    }
}
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
  <div class="auth-card reveal">
    <p class="eyebrow">Welcome back</p>
    <h1>Log in to your calm space</h1>
    <p class="muted">Small steps, every day. Pick up right where you left off. 🌿</p>
    <?php if (isset($_GET['registered'])): ?><p class="alert success">Account created — please log in. 🎉</p><?php endif; ?>
    <?php if (isset($_GET['disabled'])): ?><p class="alert error">That account is disabled.</p><?php endif; ?>
    <?php foreach ($errors as $er): ?><p class="alert error"><?= e($er) ?></p><?php endforeach; ?>
    <form method="post" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label> Email
        <input type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@example.com">
      </label>
      <label> Password
        <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
      </label>
      <button class="btn btn-primary btn-block" type="submit">Log In</button>
    </form>
    <p class="muted center">New here? <a href="register.php">Create an account</a></p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
