<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (user()) { redirect(role_home(user()['role'])); }

$courses = db_all('SELECT c.id, c.name, d.name AS department_name FROM courses c JOIN departments d ON d.id = c.department_id ORDER BY d.name, c.name');
$depts   = db_all('SELECT id, name FROM departments ORDER BY name');
$errors  = [];
$role    = $_POST['role'] ?? ($_GET['role'] ?? 'student');
if (!in_array($role, ['student', 'teacher'], true)) { $role = 'student'; }

if (is_post()) {
    csrf_check();
    $name  = post_str('name');
    $email = strtolower(post_str('email'));
    $pw    = (string)($_POST['password'] ?? '');
    $pw2   = (string)($_POST['confirm_password'] ?? '');
    $phone = post_str('phone');

    if ($name === '' || mb_strlen($name) > 100)          { $errors[] = 'Enter your full name.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))      { $errors[] = 'Enter a valid email address.'; }
    if (!phone_ok($phone))                               { $errors[] = 'Phone number must be exactly 10 digits.'; }
    if ($pe = password_error($pw))                       { $errors[] = $pe; }
    elseif ($pw !== $pw2)                                { $errors[] = 'The two passwords do not match.'; }
    if (!$errors && db_val('SELECT id FROM users WHERE email = ?', [$email])) {
        $errors[] = 'An account with this email already exists. Try logging in.';
    }

    $college = post_str('college');
    if ($college === '') { $errors[] = 'Enter your college name.'; }
    if ($role === 'student') {
        $courseId = post_int('course_id');
        $sem      = post_int('semester');
        $roll     = post_str('roll_no');
        if (!db_val('SELECT id FROM courses WHERE id = ?', [$courseId])) { $errors[] = 'Choose your course.'; }
        if ($sem < 1 || $sem > 8)  { $errors[] = 'Choose your semester.'; }
        if ($roll === '')          { $errors[] = 'Enter your roll number.'; }
    } else {
        $deptId = post_int('department_id');
        $qual   = post_str('qualification');
        $bio    = post_str('bio');
        if (!db_val('SELECT id FROM departments WHERE id = ?', [$deptId])) { $errors[] = 'Choose your department.'; }
        if ($qual === '') { $errors[] = 'Enter your qualification.'; }
    }

    $photo = null; $cert = null;
    if (!$errors && !empty($_FILES['photo']['name'])) {
        $r = upload_save($_FILES['photo'], 'photos', IMG_TYPES, MAX_PHOTO_MB * 1048576);
        if ($r['ok']) { $photo = $r['file']; } else { $errors[] = 'Photo: ' . $r['error']; }
    }
    if (!$errors && $role === 'teacher') {
        $r = upload_save($_FILES['certificate'] ?? [], 'certificates', DOC_TYPES, MAX_CERT_MB * 1048576);
        if ($r['ok']) { $cert = $r['file']; } else { $errors[] = 'Certificate: ' . ($r['none'] ? 'upload a certificate so your HOD can verify you.' : $r['error']); }
    }

    if (!$errors) {
        try {
            db()->beginTransaction();
            db_exec('INSERT INTO users (name, email, password, role, phone, photo) VALUES (?, ?, ?, ?, ?, ?)',
                [$name, $email, password_hash($pw, PASSWORD_DEFAULT), $role, $phone, $photo]);
            $uid = db_id();
            if ($role === 'student') {
                db_exec('INSERT INTO student_profiles (user_id, course_id, semester, roll_no, college) VALUES (?, ?, ?, ?, ?)',
                    [$uid, $courseId, $sem, $roll, $college]);
            } else {
                db_exec('INSERT INTO teacher_profiles (user_id, department_id, qualification, bio, college, certificate) VALUES (?, ?, ?, ?, ?, ?)',
                    [$uid, $deptId, $qual, $bio !== '' ? $bio : null, $college, $cert]);
            }
            db()->commit();
            flash('success', $role === 'teacher'
                ? 'Account created. Your Head of Department will review your certificate; you can log in now and upload once approved.'
                : 'Account created. Log in to start browsing notes.');
            redirect('login.php');
        } catch (Throwable $t) {
            if (db()->inTransaction()) { db()->rollBack(); }
            error_log('Register failed: ' . $t->getMessage());
            $errors[] = 'We could not create your account. Please try again.';
        }
    }
    if ($errors) { upload_delete('photos', $photo); upload_delete('certificates', $cert); }
}

render_header([
    'title' => 'Create account', 'layout' => 'auth', 'index' => true, 'card_class' => 'wide',
    'side_title' => 'Share what you know. Find what you need.',
    'side_text'  => 'Students get notes organised by department, subject and semester. Teachers get a verified badge once their HOD approves them.',
]);
?>
<h1>Create your account</h1>
<p class="sub">It takes about a minute.</p>
<?php if ($errors): ?>
  <div class="flash error"><?= icon('alert') ?><span><?php foreach ($errors as $er) { echo e($er) . '<br>'; } ?></span></div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" class="stack" novalidate>
  <?= csrf_field() ?>
  <div class="seg" role="radiogroup" aria-label="I am a">
    <input type="radio" name="role" id="role-student" value="student" data-role-toggle<?= $role === 'student' ? ' checked' : '' ?>>
    <label for="role-student"><?= icon('cap', 18) ?>Student</label>
    <input type="radio" name="role" id="role-teacher" value="teacher" data-role-toggle<?= $role === 'teacher' ? ' checked' : '' ?>>
    <label for="role-teacher"><?= icon('award', 18) ?>Teacher</label>
  </div>

  <div class="form-grid">
    <div class="field"><label for="name">Full name</label><input class="input" id="name" name="name" value="<?= old('name') ?>" autocomplete="name" required></div>
    <div class="field"><label for="email">Email</label><input class="input" id="email" name="email" type="email" value="<?= old('email') ?>" autocomplete="email" required></div>
    <div class="field"><label for="phone">Phone</label><input class="input" id="phone" name="phone" inputmode="numeric" maxlength="10" value="<?= old('phone') ?>" placeholder="10 digits" required></div>
    <div class="field"><label for="college">College</label><input class="input" id="college" name="college" value="<?= old('college') ?>" required></div>
    <div class="field"><label for="password">Password</label>
      <input class="input" id="password" name="password" type="password" autocomplete="new-password" data-meter="#pw-meter" required>
      <div class="meter" id="pw-meter"><i></i></div>
      <span class="hint">8+ characters with upper, lower, number and symbol.</span></div>
    <div class="field"><label for="confirm_password">Confirm password</label><input class="input" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required></div>
  </div>

  <div class="role-fields" data-for="student"<?= $role === 'student' ? '' : ' hidden' ?>>
    <div class="form-grid">
      <div class="field span-2"><label for="course_id">Course</label>
        <select class="select" id="course_id" name="course_id" data-req><?= course_options($courses, $_POST['course_id'] ?? null) ?></select></div>
      <div class="field"><label for="semester">Semester</label>
        <select class="select" id="semester" name="semester" data-req><?= semester_options($_POST['semester'] ?? null) ?></select></div>
      <div class="field"><label for="roll_no">Roll number</label><input class="input" id="roll_no" name="roll_no" value="<?= old('roll_no') ?>" data-req></div>
    </div>
  </div>

  <div class="role-fields" data-for="teacher"<?= $role === 'teacher' ? '' : ' hidden' ?>>
    <div class="form-grid">
      <div class="field"><label for="department_id">Department</label>
        <select class="select" id="department_id" name="department_id" data-req><?= select_options($depts, 'id', 'name', $_POST['department_id'] ?? null, 'Select department') ?></select></div>
      <div class="field"><label for="qualification">Qualification</label><input class="input" id="qualification" name="qualification" value="<?= old('qualification') ?>" placeholder="e.g. M.Tech" data-req></div>
      <div class="field span-2"><label for="bio">Short bio <span class="muted">(optional)</span></label><textarea class="textarea" id="bio" name="bio"><?= old('bio') ?></textarea></div>
      <div class="field span-2"><label for="certificate">Teaching certificate or ID</label>
        <input class="input" id="certificate" name="certificate" type="file" accept=".pdf,.jpg,.jpeg,.png" data-req>
        <span class="hint">PDF, JPG or PNG up to <?= MAX_CERT_MB ?> MB. Only your HOD and the manager can see it.</span></div>
    </div>
  </div>

  <div class="field"><label for="photo">Profile photo <span class="muted">(optional)</span></label>
    <input class="input" id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp">
    <span class="hint">JPG, PNG or WebP up to <?= MAX_PHOTO_MB ?> MB.</span></div>

  <button class="btn primary block" type="submit">Create account</button>
</form>
<p class="auth-links">Already registered? <a href="<?= e(url('login.php')) ?>">Log in</a></p>
<?php render_footer();
