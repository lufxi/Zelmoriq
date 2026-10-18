<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role('hod');
$dept = hod_dept_id((int)$u['id']);
if (!$dept) { flash('error', 'Your account is not linked to a department.'); redirect('hod/dashboard.php'); }
if (is_post()) {
    csrf_check();
    try {
        if (post_str('action') === 'add') {
            $name = post_str('name');
            if ($name === '' || mb_strlen($name) > 100) { flash('error', 'Enter a subject name.'); }
            else { db_exec('INSERT INTO subjects (department_id, name) VALUES (?, ?)', [$dept, $name]); flash('success', 'Subject added.'); }
        } elseif (post_str('action') === 'delete') {
            $n = db_exec('DELETE FROM subjects WHERE id = ? AND department_id = ?', [post_int('id'), $dept]);
            flash($n ? 'success' : 'error', $n ? 'Subject deleted.' : 'Subject not found.');
        }
    } catch (Throwable $t) {
        flash('error', is_constraint_error($t) ? 'That subject already exists, or notes are still filed under it.' : 'Something went wrong.');
    }
    redirect('hod/subjects.php');
}
$subjects = db_all('SELECT s.id, s.name, (SELECT COUNT(*) FROM notes WHERE subject_id = s.id) AS note_count FROM subjects s WHERE s.department_id = ? ORDER BY s.name', [$dept]);
render_header(['title' => 'Subjects', 'layout' => 'app', 'active' => 'subjects', 'heading' => 'Subjects', 'sub' => 'Teachers file their notes under these.']);
?>
<div class="two-col">
  <div class="card">
    <?php if ($subjects): ?><div class="table-wrap"><table class="table"><thead><tr><th>Subject</th><th class="num">Notes</th><th></th></tr></thead><tbody>
      <?php foreach ($subjects as $s): ?>
        <tr><td><?= e($s['name']) ?></td><td class="num"><?= (int)$s['note_count'] ?></td>
          <td class="actions"><form method="post" class="inline" data-confirm="Delete this subject?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="btn sm danger" type="submit"><?= icon('trash', 15) ?>Delete</button></form></td></tr>
      <?php endforeach; ?></tbody></table></div>
    <?php else: ?><?= empty_state('No subjects yet', 'Add the first one using the form.') ?><?php endif; ?>
  </div>
  <form method="post" class="card card-pad stack"><?= csrf_field() ?><input type="hidden" name="action" value="add">
    <h3 style="margin:0">Add a subject</h3>
    <div class="field"><label for="name">Name</label><input class="input" id="name" name="name" maxlength="100" required></div>
    <div><button class="btn primary" type="submit"><?= icon('plus', 16) ?>Add subject</button></div>
  </form>
</div>
<?php render_footer();
