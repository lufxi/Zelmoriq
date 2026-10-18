<?php
/**
 * First-run setup: creates the first Manager account (and optional demo data).
 * It locks itself as soon as a manager exists. Delete this file once you are done.
 */
require_once __DIR__ . '/includes/bootstrap.php';

$tablesOk = true; $hasManager = false;
try { $hasManager = (int)db_val("SELECT COUNT(*) FROM users WHERE role = 'manager'") > 0; }
catch (Throwable $t) { $tablesOk = false; }

$errors = []; $done = false; $demo = false;
if ($tablesOk && !$hasManager && is_post()) {
    csrf_check();
    $name = post_str('name'); $email = strtolower(post_str('email')); $pw = (string)($_POST['password'] ?? ''); $demo = isset($_POST['demo']);
    if ($name === '')                                 { $errors[] = 'Enter a name.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))   { $errors[] = 'Enter a valid email.'; }
    if ($pe = password_error($pw))                    { $errors[] = $pe; }
    elseif ($pw !== (string)($_POST['confirm_password'] ?? '')) { $errors[] = 'The passwords do not match.'; }

    if (!$errors) {
        try {
            db()->beginTransaction();
            db_exec("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'manager')", [$name, $email, password_hash($pw, PASSWORD_DEFAULT)]);
            if ($demo) { zq_seed_demo(); }
            db()->commit();
            $done = true; $hasManager = true;
        } catch (Throwable $t) {
            if (db()->inTransaction()) { db()->rollBack(); }
            error_log('Setup failed: ' . $t->getMessage());
            $errors[] = 'Setup failed: ' . (APP_ENV === 'local' ? $t->getMessage() : 'see the server log.');
        }
    }
}

function zq_seed_demo(): void
{
    $pw   = password_hash('Zelmoriq@123', PASSWORD_DEFAULT);
    $ins  = 'INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)';
    db_exec($ins, ['Dr. Anitha Menon', 'hod@demo.zelmoriq.test', $pw, 'hod', '9876500001']);
    $hod = db_id();
    db_exec('INSERT INTO hod_profiles (user_id, department_id) VALUES (?, 1)', [$hod]);
    db_exec($ins, ['Prof. Rahul Varma', 'teacher@demo.zelmoriq.test', $pw, 'teacher', '9876500002']);
    $t = db_id();
    db_exec("INSERT INTO teacher_profiles (user_id, department_id, qualification, college, approval, reviewed_by, reviewed_at) VALUES (?, 1, 'M.Tech', 'Demo College', 'approved', ?, NOW())", [$t, $hod]);
    db_exec($ins, ['Sneha Nair', 'student@demo.zelmoriq.test', $pw, 'student', '9876500003']);
    $s = db_id();
    $course = (int)db_val("SELECT id FROM courses WHERE name = 'BCA' LIMIT 1");
    db_exec("INSERT INTO student_profiles (user_id, course_id, semester, roll_no, college) VALUES (?, ?, 3, 'BCA23-014', 'Demo College')", [$s, $course]);

    $samples = [
        ['Trees and Graphs Explained', 'Binary trees, BST, BFS and DFS', 'Data Structures', 3, 'sample_ds.pdf'],
        ['Process Scheduling Cheat Sheet', 'FCFS, SJF, Round Robin', 'Operating Systems', 3, 'sample_os.pdf'],
        ['Normalisation in One Page', '1NF to BCNF with examples', 'Database Management Systems', 4, 'sample_dbms.pdf'],
    ];
    foreach ($samples as $i => $n) {
        $src = APP_ROOT . '/database/sample/' . $n[4];
        if (!is_file($src)) { continue; }
        $stored = bin2hex(random_bytes(12)) . '.pdf';
        if (!is_dir(UPLOAD_DIR . '/notes')) { @mkdir(UPLOAD_DIR . '/notes', 0755, true); }
        copy($src, UPLOAD_DIR . '/notes/' . $stored);
        $subj = (int)db_val('SELECT id FROM subjects WHERE name = ? AND department_id = 1', [$n[2]]);
        db_exec('INSERT INTO notes (teacher_id, department_id, subject_id, title, topic, semester, file_name, original_name, file_size) VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?)',
            [$t, $subj, $n[0], $n[1], $n[3], $stored, $n[4], filesize($src)]);
        $nid = db_id();
        db_exec('INSERT INTO ratings (note_id, student_id, rating) VALUES (?, ?, ?)', [$nid, $s, 5 - ($i % 2)]);
    }
}

render_header(['title' => 'Setup', 'layout' => 'auth', 'side_title' => 'Welcome to Zelmoriq.', 'side_text' => 'Create the first Manager account. After that you can add HODs, departments, courses and subjects from the console.']);
?>
<h1>First-run setup</h1>
<?php if (!$tablesOk): ?>
  <div class="flash error"><?= icon('alert') ?><span>The database tables were not found.</span></div>
  <ol class="setup-steps"><li>Start Apache and MySQL in XAMPP.</li><li>In phpMyAdmin, import <code>database/zelmoriq.sql</code>.</li><li>Check <code>config/config.php</code>, then reload this page.</li></ol>
<?php elseif ($done): ?>
  <div class="flash success"><?= icon('check') ?><span>Manager account created.</span></div>
  <?php if ($demo): ?><div class="flash info"><?= icon('info') ?><span>Demo accounts (password <code>Zelmoriq@123</code>): <code>hod@demo.zelmoriq.test</code>, <code>teacher@demo.zelmoriq.test</code>, <code>student@demo.zelmoriq.test</code>. Delete them before going live.</span></div><?php endif; ?>
  <p><strong>Delete <code>setup.php</code> from the project folder now.</strong></p>
  <a class="btn primary" href="<?= e(url('login.php')) ?>">Go to log in</a>
<?php elseif ($hasManager): ?>
  <div class="flash info"><?= icon('info') ?><span>Setup is already complete. You can safely delete <code>setup.php</code>.</span></div>
  <a class="btn primary" href="<?= e(url('login.php')) ?>">Go to log in</a>
<?php else: ?>
  <p class="sub">Create the Manager who will run the portal.</p>
  <?php if ($errors): ?><div class="flash error"><?= icon('alert') ?><span><?php foreach ($errors as $er) { echo e($er) . '<br>'; } ?></span></div><?php endif; ?>
  <form method="post" class="stack">
    <?= csrf_field() ?>
    <div class="field"><label for="name">Full name</label><input class="input" id="name" name="name" value="<?= old('name') ?>" required></div>
    <div class="field"><label for="email">Email</label><input class="input" id="email" name="email" type="email" value="<?= old('email') ?>" required></div>
    <div class="field"><label for="password">Password</label><input class="input" id="password" name="password" type="password" data-meter="#pw-meter" required><div class="meter" id="pw-meter"><i></i></div></div>
    <div class="field"><label for="confirm_password">Confirm password</label><input class="input" id="confirm_password" name="confirm_password" type="password" required></div>
    <label class="check"><input type="checkbox" name="demo" value="1"<?= $demo ? ' checked' : '' ?>><span>Also create demo HOD, teacher, student and three sample notes (good for a first look).</span></label>
    <button class="btn primary block" type="submit">Create manager</button>
  </form>
<?php endif; ?>
<?php render_footer();
