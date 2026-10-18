<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role('hod');
$dept = hod_dept_id((int)$u['id']);
if (!$dept) { flash('error', 'Your account is not linked to a department.'); redirect('hod/dashboard.php'); }
if (is_post()) {
    csrf_check();
    $n = db_one('SELECT id, file_name FROM notes WHERE id = ? AND department_id = ?', [post_int('delete'), $dept]);
    if ($n) { db_exec('DELETE FROM notes WHERE id = ?', [$n['id']]); upload_delete('notes', $n['file_name']); flash('success', 'Note removed.'); }
    else { flash('error', 'Note not found in your department.'); }
    redirect('hod/notes.php');
}
$notes = db_all(
    'SELECT n.*, t.name AS teacher_name, s.name AS subject_name,
            (SELECT ROUND(AVG(rating), 1) FROM ratings WHERE note_id = n.id) AS avg_rating,
            (SELECT COUNT(*) FROM ratings WHERE note_id = n.id) AS rating_count
       FROM notes n JOIN users t ON t.id = n.teacher_id JOIN subjects s ON s.id = n.subject_id
      WHERE n.department_id = ? ORDER BY n.created_at DESC', [$dept]);
render_header(['title' => 'Department notes', 'layout' => 'app', 'active' => 'notes', 'heading' => 'Department notes', 'sub' => 'Remove anything that does not belong.']);
?>
<div class="card">
  <div class="toolbar"><div class="field grow"><label for="f">Search</label><input class="input" id="f" data-filter-table="#notes" placeholder="Title, teacher or subject"></div></div>
<?php if ($notes): ?>
  <div class="table-wrap"><table class="table" id="notes"><thead><tr><th>Title</th><th>Teacher</th><th>Subject</th><th>Sem</th><th>Rating</th><th class="num">Downloads</th><th></th></tr></thead><tbody>
  <?php foreach ($notes as $n): ?>
    <tr><td><strong><?= e($n['title']) ?></strong><br><small class="muted"><?= e(fmt_date($n['created_at'])) ?></small></td><td><?= e($n['teacher_name']) ?></td><td><?= e($n['subject_name']) ?></td><td><?= (int)$n['semester'] ?></td>
      <td><?= stars($n['avg_rating'], (int)$n['rating_count']) ?></td><td class="num"><?= (int)$n['downloads'] ?></td>
      <td class="actions"><a class="btn sm" href="<?= e(url('download.php?id=' . (int)$n['id'] . '&inline=1')) ?>" target="_blank" rel="noopener"><?= icon('eye', 15) ?>View</a>
        <form method="post" class="inline" data-confirm="Remove this note for everyone?"><?= csrf_field() ?><input type="hidden" name="delete" value="<?= (int)$n['id'] ?>"><button class="btn sm danger" type="submit"><?= icon('trash', 15) ?>Remove</button></form></td></tr>
  <?php endforeach; ?></tbody></table></div>
<?php else: ?><?= empty_state('No notes in your department yet') ?><?php endif; ?>
</div>
<?php render_footer();
