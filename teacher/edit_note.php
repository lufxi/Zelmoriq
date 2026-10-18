<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role('teacher', 'hod');
$note = db_one('SELECT * FROM notes WHERE id = ? AND teacher_id = ?', [get_int('id'), $u['id']]);
if (!$note) { flash('error', 'Note not found.'); redirect('teacher/notes.php'); }
$subjects = db_all('SELECT id, name FROM subjects WHERE department_id = ? ORDER BY name', [$note['department_id']]);
$errors = [];
if (is_post()) {
    csrf_check();
    $title = post_str('title'); $topic = post_str('topic'); $desc = post_str('description'); $sid = post_int('subject_id'); $sem = post_int('semester');
    if ($title === '' || $topic === '') { $errors[] = 'Title and topic are required.'; }
    if (!db_val('SELECT id FROM subjects WHERE id = ? AND department_id = ?', [$sid, $note['department_id']])) { $errors[] = 'Choose a valid subject.'; }
    if ($sem < 1 || $sem > 8) { $errors[] = 'Choose a semester.'; }
    if (!$errors) {
        db_exec('UPDATE notes SET title = ?, topic = ?, description = ?, subject_id = ?, semester = ? WHERE id = ? AND teacher_id = ?',
            [mb_substr($title, 0, 200), mb_substr($topic, 0, 200), $desc !== '' ? $desc : null, $sid, $sem, $note['id'], $u['id']]);
        flash('success', 'Note updated.');
        redirect('teacher/notes.php');
    }
}
$f = function (string $k) use ($note) { return isset($_POST[$k]) ? e($_POST[$k]) : e($note[$k]); };
render_header(['title' => 'Edit note', 'layout' => 'app', 'active' => 'mynotes', 'heading' => 'Edit note', 'sub' => 'The PDF itself cannot be replaced. Delete and re-upload for a new file.']);
?>
<?php if ($errors): ?><div class="flash error"><?= icon('alert') ?><span><?php foreach ($errors as $er) { echo e($er) . '<br>'; } ?></span></div><?php endif; ?>
<form method="post" class="card card-pad" style="max-width:820px">
  <?= csrf_field() ?>
  <div class="form-grid">
    <div class="field span-2"><label for="title">Title</label><input class="input" id="title" name="title" value="<?= $f('title') ?>" required></div>
    <div class="field"><label for="subject_id">Subject</label><select class="select" id="subject_id" name="subject_id"><?= select_options($subjects, 'id', 'name', $_POST['subject_id'] ?? $note['subject_id']) ?></select></div>
    <div class="field"><label for="semester">Semester</label><select class="select" id="semester" name="semester"><?= semester_options($_POST['semester'] ?? $note['semester']) ?></select></div>
    <div class="field span-2"><label for="topic">Topic</label><input class="input" id="topic" name="topic" value="<?= $f('topic') ?>" required></div>
    <div class="field span-2"><label for="description">Description</label><textarea class="textarea" id="description" name="description"><?= $f('description') ?></textarea></div>
  </div>
  <div class="form-actions"><button class="btn primary" type="submit">Save changes</button><a class="btn" href="<?= e(url('teacher/notes.php')) ?>">Cancel</a></div>
</form>
<?php render_footer();
