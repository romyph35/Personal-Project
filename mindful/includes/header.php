<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? e($page_title) . ' · Mindful' : 'Mindful · A calmer mind starts with one small step' ?></title>
<meta name="description" content="<?= e($page_desc ?? 'Mindful is your calm premium space for mood check-ins, journaling, therapy sessions, and wellness tools.') ?>">
<link rel="stylesheet" href="<?= e($base ?? '.') ?>/assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌿</text></svg>">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="nav" id="topnav">
  <div class="nav-inner">
    <a class="logo" href="<?= e($base ?? '.') ?>/index.php" aria-label="Mindful home">🌿 MINDFUL</a>
    <nav class="nav-links" aria-label="Primary">
      <a href="<?= e($base ?? '.') ?>/index.php">Home</a>
      <a href="<?= e($base ?? '.') ?>/user/mood.php">Mood</a>
      <a href="<?= e($base ?? '.') ?>/user/sessions.php">Sessions</a>
      <a href="<?= e($base ?? '.') ?>/user/resources.php">Resources</a>
      <a href="<?= e($base ?? '.') ?>/user/wellness.php">Wellness</a>
    </nav>
    <div class="nav-actions">
      <a class="crisis-link" href="<?= e($base ?? '.') ?>/index.php#crisis" aria-label="Crisis support">🆘 Crisis</a>
      <button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle dark mode">🌙</button>
      <?php if ($user): ?>
        <a class="nav-user" href="<?= e($base ?? '.') ?>/user/dashboard.php">👋 <?= e(explode(' ', (string)$user['name'])[0]) ?></a>
        <?php if (($user['role'] ?? 'user') === 'admin'): ?>
          <a class="btn btn-ghost btn-sm" href="<?= e($base ?? '.') ?>/admin/dashboard.php">Admin</a>
        <?php else: ?>
          <a class="btn btn-ghost btn-sm" href="<?= e($base ?? '.') ?>/user/dashboard.php">Dashboard</a>
        <?php endif; ?>
        <a class="btn btn-soft btn-sm" href="<?= e($base ?? '.') ?>/logout.php">Logout</a>
      <?php else: ?>
        <a class="btn btn-ghost btn-sm" href="<?= e($base ?? '.') ?>/login.php">Log In</a>
        <a class="btn btn-primary btn-sm" href="<?= e($base ?? '.') ?>/register.php">Get Started</a>
      <?php endif; ?>
      <button class="nav-burger" id="navBurger" type="button" aria-label="Open menu" aria-expanded="false">☰</button>
    </div>
  </div>
  <div class="nav-mobile" id="navMobile" hidden>
    <a href="<?= e($base ?? '.') ?>/index.php">Home</a>
    <a href="<?= e($base ?? '.') ?>/user/mood.php">Mood</a>
    <a href="<?= e($base ?? '.') ?>/user/sessions.php">Sessions</a>
    <a href="<?= e($base ?? '.') ?>/user/resources.php">Resources</a>
    <a href="<?= e($base ?? '.') ?>/user/wellness.php">Wellness</a>
  </div>
</header>
<main id="main-content">
