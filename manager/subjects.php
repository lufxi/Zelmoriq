<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('manager');
$depts = db_all('SELECT id, name FROM departments ORDER BY name');
if (is_post()) {
    csrf_check();
    try {
        if (post_str('action') === 'add') {
            $name = post_str('name'); $dept = post_int('department_id');
            if ($name === '' || !db_val('SELECT id FROM departments WHERE id = ?', [$dept])) { flash('error', 'Choose a department and enter a name.'); }
            else { db_exec('INSERT INTO subjects (department_id, name) VALUES (?, ?)', [$dept, mb_substr($name, 0, 100)]); flash('success', 'Subject added.'); }
        } elseif (post_str('action') === 'delete') {
            $n = db_exec('DELETE FROM subjects WHERE id = ?', [post_int('id')]);
            flash($n ? 'success' : 'error', $n ? 'Subject deleted.' : 'Not found.');
        }
    } catch (Throwable $t) {
        flash('error', is_constraint_error($t) ? 'That already exists in this department, or it is still in use.' : 'Something went wrong.');
    }
    redirect('manager/subjects.php');
}
$filter = get_int('department');
$rows = db_all('SELECT x.id, x.name, d.name AS department_name FROM subjects x JOIN departments d ON d.id = x.department_id'
    . ($filter ? ' WHERE x.department_id = ?' : '') . ' ORDER BY d.name, x.name', $filter ? [$filter] : []);
render_header(['title' => 'Subjects', 'layout' => 'app', 'active' => 'subjects', 'heading' => 'Subjects', 'sub' => count($rows) . ' listed']);
?>
<div class="two-col">
  <div class="card">
    <form method="get" class="toolbar" data-autosubmit><div class="field"><label for="department">Show department</label><select class="select" id="department" name="department"><?= select_options($depts, 'id', 'name', $filter, 'All departments') ?></select></div></form>
    <?php if ($rows): ?><div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Department</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td><strong><?= e($r['name']) ?></strong></td><td><span class="chip"><?= e($r['department_name']) ?></span></td>
        <td class="actions"><form method="post" class="inline" data-confirm="Delete this?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn sm danger" type="submit"><?= icon('trash', 15) ?>Delete</button></form></td></tr>
    <?php endforeach; ?></tbody></table></div>
    <?php else: ?><?= empty_state('Nothing here yet', 'Add one with the form.') ?><?php endif; ?>
  </div>
  <form method="post" class="card card-pad stack"><?= csrf_field() ?><input type="hidden" name="action" value="add"><h3 style="margin:0">Add</h3>
    <div class="field"><label for="d2">Department</label><select class="select" id="d2" name="department_id" required><?= select_options($depts, 'id', 'name', $filter ?: null, 'Select department') ?></select></div>
    <div class="field"><label for="n2">Name</label><input class="input" id="n2" name="name" maxlength="100" required></div>
    <div><button class="btn primary" type="submit"><?= icon('plus', 16) ?>Add</button></div></form>
</div>
<?php render_footer();
