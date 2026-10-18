<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('manager');
if (is_post()) {
    csrf_check();
    $a = post_str('action'); $name = post_str('name'); $id = post_int('id');
    try {
        if ($a === 'add' && $name !== '')       { db_exec('INSERT INTO departments (name) VALUES (?)', [mb_substr($name, 0, 100)]); flash('success', 'Department added.'); }
        elseif ($a === 'rename' && $name !== '') { db_exec('UPDATE departments SET name = ? WHERE id = ?', [mb_substr($name, 0, 100), $id]); flash('success', 'Department renamed.'); }
        elseif ($a === 'delete')                 { $n = db_exec('DELETE FROM departments WHERE id = ?', [$id]); flash($n ? 'success' : 'error', $n ? 'Department deleted.' : 'Department not found.'); }
        else { flash('error', 'Enter a department name.'); }
    } catch (Throwable $t) {
        flash('error', is_constraint_error($t) ? 'That name already exists, or the department still has courses, subjects, people or notes.' : 'Something went wrong.');
    }
    redirect('manager/departments.php');
}
$rows = db_all('SELECT d.*, (SELECT COUNT(*) FROM courses WHERE department_id = d.id) AS courses,
                (SELECT COUNT(*) FROM subjects WHERE department_id = d.id) AS subjects,
                (SELECT COUNT(*) FROM notes WHERE department_id = d.id) AS notes,
                (SELECT u.name FROM hod_profiles h JOIN users u ON u.id = h.user_id WHERE h.department_id = d.id) AS hod
                FROM departments d ORDER BY d.name');
render_header(['title' => 'Departments', 'layout' => 'app', 'active' => 'departments', 'heading' => 'Departments', 'sub' => 'Departments group courses, subjects, teachers and notes.']);
?>
<div class="two-col">
  <div class="card"><div class="table-wrap"><table class="table"><thead><tr><th>Department</th><th>HOD</th><th class="num">Courses</th><th class="num">Subjects</th><th class="num">Notes</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $d): ?>
    <tr>
      <td><form method="post" class="row" style="flex-wrap:nowrap"><?= csrf_field() ?><input type="hidden" name="action" value="rename"><input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
        <input class="input" name="name" value="<?= e($d['name']) ?>" aria-label="Department name" style="min-height:38px"><button class="btn sm" type="submit">Rename</button></form></td>
      <td><?= $d['hod'] ? e($d['hod']) : '<span class="muted">None</span>' ?></td><td class="num"><?= (int)$d['courses'] ?></td><td class="num"><?= (int)$d['subjects'] ?></td><td class="num"><?= (int)$d['notes'] ?></td>
      <td class="actions"><form method="post" class="inline" data-confirm="Delete this department?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$d['id'] ?>"><button class="btn sm danger" type="submit"><?= icon('trash', 15) ?>Delete</button></form></td>
    </tr>
  <?php endforeach; ?></tbody></table></div></div>
  <form method="post" class="card card-pad stack"><?= csrf_field() ?><input type="hidden" name="action" value="add"><h3 style="margin:0">Add a department</h3>
    <div class="field"><label for="name">Name</label><input class="input" id="name" name="name" maxlength="100" required></div>
    <div><button class="btn primary" type="submit"><?= icon('plus', 16) ?>Add department</button></div></form>
</div>
<?php render_footer();
