<?php
require_once __DIR__ . '/includes/bootstrap.php';
$u    = require_login();
$uid  = (int)$u['id'];
$role = $u['role'];
$sp = $role === 'student' ? student_profile($uid) : null;
$tp = $role === 'teacher' ? teacher_profile($uid) : null;
$hodDept = $role === 'hod' ? db_val('SELECT d.name FROM hod_profiles h JOIN departments d ON d.id = h.department_id WHERE h.user_id = ?', [$uid]) : null;
$courses = $role === 'student' ? db_all('SELECT c.id, c.name, d.name AS department_name FROM courses c JOIN departments d ON d.id = c.department_id ORDER BY d.name, c.name') : [];
$depts   = $role === 'teacher' ? db_all('SELECT id, name FROM departments ORDER BY name') : [];
$errors  = [];

if (is_post()) {
    csrf_check();
    $name = post_str('name'); $email = strtolower(post_str('email')); $phone = post_str('phone');
    if ($name === '')                                   { $errors[] = 'Enter your name.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     { $errors[] = 'Enter a valid email address.'; }
    if (!phone_ok($phone))                              { $errors[] = 'Phone number must be exactly 10 digits.'; }
    if (!$errors && db_val('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $uid])) { $errors[] = 'That email belongs to another account.'; }

    if ($role === 'student') {
        $courseId = post_int('course_id'); $sem = post_int('semester'); $roll = post_str('roll_no'); $college = post_str('college');
        if (!db_val('SELECT id FROM courses WHERE id = ?', [$courseId])) { $errors[] = 'Choose your course.'; }
        if ($sem < 1 || $sem > 8 || $roll === '' || $college === '')     { $errors[] = 'Semester, roll number and college are required.'; }
    } elseif ($role === 'teacher') {
        $deptId = post_int('department_id'); $qual = post_str('qualification'); $college = post_str('college'); $bio = post_str('bio');
        if (!db_val('SELECT id FROM departments WHERE id = ?', [$deptId])) { $errors[] = 'Choose your department.'; }
        if ($qual === '' || $college === '') { $errors[] = 'Qualification and college are required.'; }
    }

    $newPhoto = null; $newCert = null;
    if (!$errors && !empty($_FILES['photo']['name'])) {
        $r = upload_save($_FILES['photo'], 'photos', IMG_TYPES, MAX_PHOTO_MB * 1048576);
        if ($r['ok']) { $newPhoto = $r['file']; } else { $errors[] = 'Photo: ' . $r['error']; }
    }
    if (!$errors && $role === 'teacher' && !empty($_FILES['certificate']['name'])) {
        $r = upload_save($_FILES['certificate'], 'certificates', DOC_TYPES, MAX_CERT_MB * 1048576);
        if ($r['ok']) { $newCert = $r['file']; } else { $errors[] = 'Certificate: ' . $r['error']; }
    }

    if (!$errors) {
        try {
            db()->beginTransaction();
            db_exec('UPDATE users SET name = ?, email = ?, phone = ?, photo = COALESCE(?, photo) WHERE id = ?', [$name, $email, $phone, $newPhoto, $uid]);
            if ($role === 'student') {
                db_exec('UPDATE student_profiles SET course_id = ?, semester = ?, roll_no = ?, college = ? WHERE user_id = ?', [$courseId, $sem, $roll, $college, $uid]);
            } elseif ($role === 'teacher') {
                $reverify = ($tp && ((int)$tp['department_id'] !== $deptId || $newCert !== null));
                db_exec('UPDATE teacher_profiles SET department_id = ?, qualification = ?, college = ?, bio = ?, certificate = COALESCE(?, certificate) WHERE user_id = ?',
                    [$deptId, $qual, $college, $bio !== '' ? $bio : null, $newCert, $uid]);
                if ($reverify) { db_exec("UPDATE teacher_profiles SET approval = 'pending', reviewed_by = NULL, reviewed_at = NULL WHERE user_id = ?", [$uid]); }
            }
            db()->commit();
            if ($newPhoto) { upload_delete('photos', $u['photo']); }
            if ($newCert && $tp) { upload_delete('certificates', $tp['certificate']); }
            flash('success', 'Profile saved.' . (!empty($reverify) ? ' Your HOD needs to re-verify the changed details before you can upload again.' : ''));
            redirect('profile.php');
        } catch (Throwable $t) {
            if (db()->inTransaction()) { db()->rollBack(); }
            error_log('Profile update failed: ' . $t->getMessage());
            $errors[] = 'Could not save your profile. Please try again.';
        }
    }
    if ($errors) { upload_delete('photos', $newPhoto); upload_delete('certificates', $newCert); }
}

$v = function (string $k, $current) { return isset($_POST[$k]) ? e($_POST[$k]) : e($current); };
render_header(['title' => 'Profile', 'layout' => 'app', 'active' => 'profile', 'heading' => 'Your profile', 'sub' => 'Keep your details current so classmates and colleagues can recognise you.']);
?>
<?php if ($errors): ?><div class="flash error"><?= icon('alert') ?><span><?php foreach ($errors as $er) { echo e($er) . '<br>'; } ?></span></div><?php endif; ?>
<div class="two-col">
  <form method="post" enctype="multipart/form-data" class="card card-pad">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="field"><label for="name">Full name</label><input class="input" id="name" name="name" value="<?= $v('name', $u['name']) ?>" required></div>
      <div class="field"><label for="email">Email</label><input class="input" id="email" name="email" type="email" value="<?= $v('email', $u['email']) ?>" required></div>
      <div class="field"><label for="phone">Phone</label><input class="input" id="phone" name="phone" maxlength="10" value="<?= $v('phone', $u['phone']) ?>" required></div>
      <div class="field"><label>Role</label><input class="input" value="<?= e(role_label($role)) ?>" readonly></div>
      <?php if ($role === 'student' && $sp): ?>
        <div class="field span-2"><label for="course_id">Course</label><select class="select" id="course_id" name="course_id"><?= course_options($courses, $_POST['course_id'] ?? $sp['course_id']) ?></select></div>
        <div class="field"><label for="semester">Semester</label><select class="select" id="semester" name="semester"><?= semester_options($_POST['semester'] ?? $sp['semester']) ?></select></div>
        <div class="field"><label for="roll_no">Roll number</label><input class="input" id="roll_no" name="roll_no" value="<?= $v('roll_no', $sp['roll_no']) ?>"></div>
        <div class="field span-2"><label for="college">College</label><input class="input" id="college" name="college" value="<?= $v('college', $sp['college']) ?>"></div>
      <?php elseif ($role === 'teacher' && $tp): ?>
        <div class="field"><label for="department_id">Department</label><select class="select" id="department_id" name="department_id"><?= select_options($depts, 'id', 'name', $_POST['department_id'] ?? $tp['department_id']) ?></select></div>
        <div class="field"><label for="qualification">Qualification</label><input class="input" id="qualification" name="qualification" value="<?= $v('qualification', $tp['qualification']) ?>"></div>
        <div class="field span-2"><label for="college">College</label><input class="input" id="college" name="college" value="<?= $v('college', $tp['college']) ?>"></div>
        <div class="field span-2"><label for="bio">Short bio</label><textarea class="textarea" id="bio" name="bio"><?= $v('bio', $tp['bio']) ?></textarea></div>
        <div class="field span-2"><label for="certificate">Replace certificate</label><input class="input" id="certificate" name="certificate" type="file" accept=".pdf,.jpg,.jpeg,.png">
          <span class="hint">Changing your department or certificate sends your account back to your HOD for re-verification.</span></div>
      <?php elseif ($role === 'hod'): ?>
        <div class="field span-2"><label>Department</label><input class="input" value="<?= e($hodDept ?? 'Not assigned') ?>" readonly><span class="hint">Only the manager can change your department.</span></div>
      <?php endif; ?>
      <div class="field span-2"><label for="photo">Profile photo</label><input class="input" id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp"></div>
    </div>
    <div class="form-actions"><button class="btn primary" type="submit">Save profile</button></div>
  </form>
  <aside class="card profile-card">
    <?= avatar($u, 'lg') ?>
    <h2><?= e($u['name']) ?></h2>
    <p class="muted"><?= e(role_label($role)) ?></p>
    <?php if ($tp): ?><p><?= approval_badge($tp['approval']) ?></p><?php endif; ?>
    <p class="small muted">Member since <?= e(fmt_date($u['created_at'])) ?></p>
  </aside>
</div>
<?php render_footer();
