<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (user()) { redirect(role_home(user()['role'])); }

$sent = false; $devLink = '';
if (is_post()) {
    csrf_check();
    $email = strtolower(post_str('email'));
    $u = filter_var($email, FILTER_VALIDATE_EMAIL) ? db_one("SELECT id, name FROM users WHERE email = ? AND status = 'active'", [$email]) : null;
    if ($u) {
        $token = bin2hex(random_bytes(32));
        db_exec('UPDATE users SET reset_token_hash = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?', [hash('sha256', $token), $u['id']]);
        $link = absolute_url('reset_password.php?token=' . $token);
        $mailed = mail_send($email, 'Reset your Zelmoriq password',
            "Hi " . $u['name'] . ",\n\nUse this link within one hour to choose a new password:\n" . $link . "\n\nIf you did not ask for this, ignore this email.");
        if (!$mailed && APP_ENV === 'local') { $devLink = $link; }
    }
    $sent = true; // same answer whether or not the email exists
}

render_header(['title' => 'Forgot password', 'layout' => 'auth']);
?>
<h1>Reset your password</h1>
<p class="sub">Enter your account email and we will create a reset link that works for one hour.</p>
<?php if ($sent): ?>
  <div class="flash success"><?= icon('check') ?><span>If that email is registered, a reset link has been created.</span></div>
  <?php if ($devLink): ?>
    <div class="flash info"><?= icon('info') ?><span><strong>Local mode:</strong> no mail server is configured, so the link is shown here.<br>
      <a href="<?= e($devLink) ?>"><?= e($devLink) ?></a></span></div>
  <?php endif; ?>
<?php endif; ?>
<form method="post" class="stack">
  <?= csrf_field() ?>
  <div class="field"><label for="email">Email</label><input class="input" id="email" name="email" type="email" required autofocus></div>
  <button class="btn primary block" type="submit">Create reset link</button>
</form>
<p class="auth-links"><a href="<?= e(url('login.php')) ?>">Back to log in</a></p>
<?php render_footer();
