</main>
<footer class="footer">
  <div class="footer-inner">
    <div class="footer-brand">
      <p class="logo">🌿 MINDFUL</p>
      <p class="muted">Your premium calm space for moods, journals, sessions, and gentle growth.</p>
      <p class="crisis-note">🆘 If you are in crisis, please reach out now — see <a href="<?= e($base ?? '.') ?>/index.php#crisis">crisis support</a>. You matter.</p>
    </div>
    <div class="footer-cols">
      <div>
        <h4>Explore</h4>
        <a href="<?= e($base ?? '.') ?>/index.php">Home</a>
        <a href="<?= e($base ?? '.') ?>/user/mood.php">Mood check-in</a>
        <a href="<?= e($base ?? '.') ?>/user/journal.php">Journal</a>
        <a href="<?= e($base ?? '.') ?>/user/wellness.php">Wellness</a>
      </div>
      <div>
        <h4>Care</h4>
        <a href="<?= e($base ?? '.') ?>/user/sessions.php">Book a session</a>
        <a href="<?= e($base ?? '.') ?>/user/resources.php">Resources</a>
        <a href="<?= e($base ?? '.') ?>/user/notifications.php">Notifications</a>
        <a href="<?= e($base ?? '.') ?>/user/profile.php">Profile</a>
      </div>
      <div>
        <h4>Trust</h4>
        <a href="<?= e($base ?? '.') ?>/index.php#pricing">Plans</a>
        <a href="<?= e($base ?? '.') ?>/index.php#crisis">Crisis support</a>
        <a href="<?= e($base ?? '.') ?>/login.php">Log in</a>
        <a href="<?= e($base ?? '.') ?>/register.php">Get started</a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© <?= date('Y') ?> Mindful · Made with care 🌿</span>
    <span class="muted">Wellness companion — not a substitute for professional care.</span>
  </div>
</footer>
<div class="toast-wrap" id="toastWrap" aria-live="polite"></div>
<script src="<?= e($base ?? '.') ?>/assets/js/app.js" defer></script>
</body>
</html>
