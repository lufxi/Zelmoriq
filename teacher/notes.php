<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role('teacher', 'hod');
if (is_post()) {
    csrf_check();
    $note = db_one('SELECT id, file_name FROM notes WHERE id = ? AND teacher_id = ?', [post_int('delete'), $u['id']]);
    if ($note) {
        db_exec('DELETE FROM notes WHERE id = ?', [$note['id']]);
        upload_delete('notes', $note['file_name']);
        flash('success', 'Note deleted.');
    } else { flash('error', 'Note not found.'); }
    redirect('teacher/notes.php');
}
$notes = db_all(
    'SELECT n.*, s.name AS subject_name,
            (SELECT ROUND(AVG(rating), 1) FROM ratings WHERE note_id = n.id) AS avg_rating,
            (SELECT COUNT(*) FROM ratings WHERE note_id = n.id) AS rating_count
       FROM notes n JOIN subjects s ON s.id = n.subject_id WHERE n.teacher_id = ? ORDER BY n.created_at DESC', [$u['id']]);

render_header(['title' => 'My notes', 'layout' => 'app', 'active' => 'mynotes', 'heading' => 'My notes', 'sub' => count($notes) . ' shared',
    'actions' => '<a class="btn primary" href="' . e(url('teacher/upload.php')) . '">' . icon('plus', 16) . 'Upload note</a>']);
?>
<div class="card">
<?php if ($notes): ?>
  <div class="table-wrap"><table class="table"><thead><tr><th>Title</th><th>Subject</th><th>Sem</th><th>Rating</th><th class="num">Downloads</th><th>Size</th><th></th></tr></thead><tbody>
  <?php foreach ($notes as $n): ?>
    <tr>
      <td><strong><?= e($n['title']) ?></strong><br><small class="muted"><?= e($n['topic']) ?></small></td>
      <td><?= e($n['subject_name']) ?></td><td><?= (int)$n['semester'] ?></td>
      <td><?= stars($n['avg_rating'], (int)$n['rating_count']) ?></td><td class="num"><?= (int)$n['downloads'] ?></td><td><?= e(human_size((int)$n['file_size'])) ?></td>
      <td class="actions">
        <a class="btn sm" href="<?= e(url('download.php?id=' . (int)$n['id'] . '&inline=1')) ?>" target="_blank" rel="noopener"><?= icon('eye', 15) ?>View</a>
        <a class="btn sm" href="<?= e(url('teacher/edit_note.php?id=' . (int)$n['id'])) ?>"><?= icon('edit', 15) ?>Edit</a>
        <form method="post" class="inline" data-confirm="Delete this note? This cannot be undone."><?= csrf_field() ?><input type="hidden" name="delete" value="<?= (int)$n['id'] ?>"><button class="btn sm danger" type="submit"><?= icon('trash', 15) ?>Delete</button></form>
      </td>
    </tr>
  <?php endforeach; ?></tbody></table></div>
<?php else: ?><?= empty_state('You have not shared anything yet', 'Your uploaded notes will be listed here.', '<a class="btn primary" href="' . e(url('teacher/upload.php')) . '">Upload your first note</a>') ?><?php endif; ?>
</div>
<?php render_footer();
