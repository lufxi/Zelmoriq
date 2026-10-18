<?php
require_once __DIR__ . '/includes/bootstrap.php';
$token = (string)($_POST['token'] ?? $_GET['token'] ?? '');
$hash  = hash('sha256', $token);
$u = $token !== '' ? db_one("SELECT id FROM users WHERE reset_token_hash = ? AND reset_expires > NOW() AND status = 'active'", [$hash]) : null;
$error = '';

if ($u && is_post()) {
    csrf_check();
    $pw = (string)($_POST['password'] ?? '');
    if ($pe = password_error($pw)) {
        $error = $pe;
    } elseif ($pw !== (string)($_POST['confirm_password'] ?? '')) {
        $error = 'The two passwords do not match.';
    } else {
        db_exec('UPDATE users SET password = ?, reset_token_hash = NULL, reset_expires = NULL WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $u['id']]);
        flash('success', 'Password updated. Log in with your new password.');
        redirect('login.php');
    }
}

render_header(['title' => 'Choose a new password', 'layout' => 'auth']);
?>
<h1>Choose a new password</h1>
<?php if (!$u): ?>
  <div class="flash error"><?= icon('alert') ?><span>This reset link is invalid or has expired.</span></div>
  <p><a class="btn primary" href="<?= e(url('forgot_password.php')) ?>">Create a new link</a></p>
<?php else: ?>
  <p class="sub">Pick something you have not used elsewhere.</p>
  <?php if ($error): ?><div class="flash error"><?= icon('alert') ?><span><?= e($error) ?></span></div><?php endif; ?>
  <form method="post" class="stack">
    <?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="field"><label for="password">New password</label>
      <input class="input" id="password" name="password" type="password" autocomplete="new-password" data-meter="#pw-meter" required>
      <div class="meter" id="pw-meter"><i></i></div></div>
    <div class="field"><label for="confirm_password">Confirm new password</label><input class="input" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required></div>
    <button class="btn primary block" type="submit">Save new password</button>
  </form>
<?php endif; ?>
<?php render_footer();
