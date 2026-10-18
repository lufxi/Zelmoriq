<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (user()) { redirect(role_home(user()['role'])); }

$error = '';
$email = '';
if (is_post()) {
    csrf_check();
    $email = strtolower(post_str('email'));
    $pass  = (string)($_POST['password'] ?? '');
    if ($email === '' || $pass === '') {
        $error = 'Enter your email and password.';
    } elseif (login_blocked($email)) {
        $error = 'Too many failed attempts. Wait 10 minutes, then try again.';
    } else {
        $row = db_one('SELECT * FROM users WHERE email = ?', [$email]);
        if ($row && password_verify($pass, $row['password'])) {
            if ($row['status'] !== 'active') {
                $error = 'This account is disabled. Contact your administrator.';
            } else {
                login_clear($email);
                if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
                    db_exec('UPDATE users SET password = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $row['id']]);
                }
                login_user($row);
                flash('success', 'Welcome back, ' . explode(' ', $row['name'])[0] . '.');
                redirect(role_home($row['role']));
            }
        } else {
            login_fail($email);
            $error = 'Incorrect email or password.';
        }
    }
}

render_header(['title' => 'Log in', 'layout' => 'auth']);
?>
<h1>Log in</h1>
<p class="sub">Students, teachers, HODs and managers all sign in here.</p>
<?php if ($error): ?><div class="flash error"><?= icon('alert') ?><span><?= e($error) ?></span></div><?php endif; ?>
<form method="post" class="stack" novalidate>
  <?= csrf_field() ?>
  <div class="field"><label for="email">Email</label>
    <input class="input" id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="username" required autofocus></div>
  <div class="field"><label for="password">Password</label>
    <input class="input" id="password" name="password" type="password" autocomplete="current-password" required></div>
  <button class="btn primary block" type="submit">Log in</button>
</form>
<p class="auth-links"><a href="<?= e(url('forgot_password.php')) ?>">Forgot your password?</a></p>
<p class="auth-links">New to Zelmoriq? <a href="<?= e(url('register.php')) ?>">Create an account</a></p>
<?php render_footer();
