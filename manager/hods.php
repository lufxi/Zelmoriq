<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_role('manager');
$editId = get_int('edit');
$errors = [];

if (is_post()) {
    csrf_check();
    $a = post_str('action');
    if ($a === 'delete') {
        $id = post_int('id');
        if (db_val("SELECT id FROM users WHERE id = ? AND role = 'hod'", [$id])) { delete_user_completely($id); flash('success', 'HOD removed.'); }
        redirect('manager/hods.php');
    }
    if ($a === 'toggle') {
        db_exec("UPDATE users SET status = IF(status = 'active', 'disabled', 'active') WHERE id = ? AND role = 'hod'", [post_int('id')]);
        flash('success', 'HOD status changed.');
        redirect('manager/hods.php');
    }
    $id = post_int('id'); $name = post_str('name'); $email = strtolower(post_str('email')); $phone = post_str('phone'); $dept = post_int('department_id'); $pw = (string)($_POST['password'] ?? '');
    if ($name === '')                                 { $errors[] = 'Enter a name.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))   { $errors[] = 'Enter a valid email.'; }
    if (!phone_ok($phone))                            { $errors[] = 'Phone must be 10 digits.'; }
    if (!db_val('SELECT id FROM departments WHERE id = ?', [$dept])) { $errors[] = 'Choose a department.'; }
    if (db_val('SELECT id FROM users WHERE email = ? AND id <> ?', [$email, $id]))  { $errors[] = 'That email is already used.'; }
    if (db_val('SELECT user_id FROM hod_profiles WHERE department_id = ? AND user_id <> ?', [$dept, $id])) { $errors[] = 'That department already has a HOD.'; }
    if ($a === 'add' || $pw !== '') { if ($pe = password_error($pw)) { $errors[] = $pe; } }
    $photo = null;
    if (!$errors && !empty($_FILES['photo']['name'])) {
        $r = upload_save($_FILES['photo'], 'photos', IMG_TYPES, MAX_PHOTO_MB * 1048576);
        if ($r['ok']) { $photo = $r['file']; } else { $errors[] = 'Photo: ' . $r['error']; }
    }
    if (!$errors) {
        try {
            db()->beginTransaction();
            if ($a === 'add') {
                db_exec("INSERT INTO users (name, email, password, role, phone, photo) VALUES (?, ?, ?, 'hod', ?, ?)", [$name, $email, password_hash($pw, PASSWORD_DEFAULT), $phone, $photo]);
                db_exec('INSERT INTO hod_profiles (user_id, department_id) VALUES (?, ?)', [db_id(), $dept]);
            } else {
                $old = db_one("SELECT photo FROM users WHERE id = ? AND role = 'hod'", [$id]);
                if (!$old) { throw new RuntimeException('HOD not found'); }
                db_exec('UPDATE users SET name = ?, email = ?, phone = ?, photo = COALESCE(?, photo) WHERE id = ?', [$name, $email, $phone, $photo, $id]);
                if ($pw !== '') { db_exec('UPDATE users SET password = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $id]); }
                db_exec('UPDATE hod_profiles SET department_id = ? WHERE user_id = ?', [$dept, $id]);
                if ($photo) { upload_delete('photos', $old['photo']); }
            }
            db()->commit();
            flash('success', $a === 'add' ? 'HOD account created.' : 'HOD updated.');
            redirect('manager/hods.php');
        } catch (Throwable $t) {
            if (db()->inTransaction()) { db()->rollBack(); }
            upload_delete('photos', $photo);
            error_log('HOD save failed: ' . $t->getMessage());
            $errors[] = 'Could not save the HOD.';
        }
    }
    if ($errors && $a === 'edit') { $editId = $id; }
}

$hods = db_all("SELECT u.id, u.name, u.email, u.phone, u.photo, u.status, h.department_id, d.name AS department_name
                  FROM users u JOIN hod_profiles h ON h.user_id = u.id JOIN departments d ON d.id = h.department_id WHERE u.role = 'hod' ORDER BY d.name");
$edit = null;
foreach ($hods as $h) { if ((int)$h['id'] === $editId) { $edit = $h; } }
$depts = db_all('SELECT d.id, d.name FROM departments d WHERE NOT EXISTS (SELECT 1 FROM hod_profiles h WHERE h.department_id = d.id' . ($edit ? ' AND h.user_id <> ' . (int)$edit['id'] : '') . ') ORDER BY d.name');
$val = function (string $k, $cur = '') { return isset($_POST[$k]) ? e($_POST[$k]) : e($cur); };

render_header(['title' => 'HODs', 'layout' => 'app', 'active' => 'hods', 'heading' => 'Heads of Department', 'sub' => 'One HOD per department verifies that department\'s teachers.']);
?>
<?php if ($errors): ?><div class="flash error"><?= icon('alert') ?><span><?php foreach ($errors as $er) { echo e($er) . '<br>'; } ?></span></div><?php endif; ?>
<div class="two-col">
  <div class="card">
  <?php if ($hods): ?><div class="table-wrap"><table class="table"><thead><tr><th>HOD</th><th>Department</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($hods as $h): ?>
      <tr><td><div class="who"><?= avatar($h, 'sm') ?><div><strong><?= e($h['name']) ?></strong><small><?= e($h['email']) ?></small></div></div></td>
        <td><?= e($h['department_name']) ?></td><td><?= $h['status'] === 'active' ? badge('Active', 'ok') : badge('Disabled', 'bad') ?></td>
        <td class="actions"><a class="btn sm" href="?edit=<?= (int)$h['id'] ?>"><?= icon('edit', 15) ?>Edit</a>
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$h['id'] ?>"><button class="btn sm" type="submit"><?= $h['status'] === 'active' ? 'Disable' : 'Enable' ?></button></form>
          <form method="post" class="inline" data-confirm="Delete this HOD account?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$h['id'] ?>"><button class="btn sm danger" type="submit"><?= icon('trash', 15) ?></button></form></td></tr>
    <?php endforeach; ?></tbody></table></div>
  <?php else: ?><?= empty_state('No HODs yet', 'Create one with the form so teachers can be verified.') ?><?php endif; ?>
  </div>
  <form method="post" enctype="multipart/form-data" class="card card-pad stack"><?= csrf_field() ?>
    <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>"><input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">
    <h3 style="margin:0"><?= $edit ? 'Edit HOD' : 'Add a HOD' ?></h3>
    <div class="field"><label for="name">Full name</label><input class="input" id="name" name="name" value="<?= $val('name', $edit['name'] ?? '') ?>" required></div>
    <div class="field"><label for="email">Email</label><input class="input" id="email" name="email" type="email" value="<?= $val('email', $edit['email'] ?? '') ?>" required></div>
    <div class="field"><label for="phone">Phone</label><input class="input" id="phone" name="phone" maxlength="10" value="<?= $val('phone', $edit['phone'] ?? '') ?>" required></div>
    <div class="field"><label for="department_id">Department</label><select class="select" id="department_id" name="department_id" required><?= select_options($depts, 'id', 'name', $_POST['department_id'] ?? ($edit['department_id'] ?? null), 'Select department') ?></select><span class="hint">Only departments without a HOD are listed.</span></div>
    <div class="field"><label for="password"><?= $edit ? 'New password (leave blank to keep)' : 'Temporary password' ?></label><input class="input" id="password" name="password" type="password" autocomplete="new-password" data-meter="#pw-meter"<?= $edit ? '' : ' required' ?>><div class="meter" id="pw-meter"><i></i></div></div>
    <div class="field"><label for="photo">Photo (optional)</label><input class="input" id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp"></div>
    <div class="btn-row"><button class="btn primary" type="submit"><?= $edit ? 'Save HOD' : 'Create HOD' ?></button><?php if ($edit): ?><a class="btn" href="<?= e(url('manager/hods.php')) ?>">Cancel</a><?php endif; ?></div>
  </form>
</div>
<?php render_footer();
