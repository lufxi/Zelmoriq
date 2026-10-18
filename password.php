<?php
require_once __DIR__ . '/includes/bootstrap.php';
$u = require_login();
$error = '';
if (is_post()) {
    csrf_check();
    $cur = (string)($_POST['current_password'] ?? ''); $new = (string)($_POST['new_password'] ?? ''); $new2 = (string)($_POST['confirm_password'] ?? '');
    if (!password_verify($cur, $u['password']))  { $error = 'Your current password is incorrect.'; }
    elseif ($pe = password_error($new))          { $error = $pe; }
    elseif ($new !== $new2)                      { $error = 'The new passwords do not match.'; }
    elseif ($new === $cur)                       { $error = 'Choose a password different from the current one.'; }
    else {
        db_exec('UPDATE users SET password = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        session_regenerate_id(true);
        flash('success', 'Password changed.');
        redirect('password.php');
    }
}
render_header(['title' => 'Change password', 'layout' => 'app', 'active' => 'password', 'heading' => 'Change password', 'sub' => 'Use a password you do not use anywhere else.']);
?>
<?php if ($error): ?><div class="flash error"><?= icon('alert') ?><span><?= e($error) ?></span></div><?php endif; ?>
<form method="post" class="card card-pad stack" style="max-width:520px">
  <?= csrf_field() ?>
  <div class="field"><label for="current_password">Current password</label><input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
  <div class="field"><label for="new_password">New password</label><input class="input" id="new_password" name="new_password" type="password" autocomplete="new-password" data-meter="#pw-meter" required>
    <div class="meter" id="pw-meter"><i></i></div><span class="hint">8+ characters with upper, lower, number and symbol.</span></div>
  <div class="field"><label for="confirm_password">Confirm new password</label><input class="input" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required></div>
  <div><button class="btn primary" type="submit">Change password</button></div>
</form>
<?php render_footer();
