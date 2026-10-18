<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role('teacher', 'hod');
$uid = (int)$u['id'];
$deptId = null; $locked = ''; 
if ($u['role'] === 'teacher') {
    $tp = teacher_profile($uid);
    if (!$tp) { $locked = 'Your teacher profile is missing. Contact the manager.'; }
    elseif ($tp['approval'] !== 'approved') { $locked = 'You can upload notes once your HOD approves your account.'; }
    else { $deptId = (int)$tp['department_id']; }
} else {
    $deptId = hod_dept_id($uid);
    if (!$deptId) { $locked = 'Your account is not linked to a department yet. Contact the manager.'; }
}
$subjects = $deptId ? db_all('SELECT id, name FROM subjects WHERE department_id = ? ORDER BY name', [$deptId]) : [];
$errors = [];

if (!$locked && is_post()) {
    csrf_check();
    $title = post_str('title'); $topic = post_str('topic'); $desc = post_str('description');
    $subjectId = post_int('subject_id'); $sem = post_int('semester');
    if ($title === '' || mb_strlen($title) > 200) { $errors[] = 'Enter a title (up to 200 characters).'; }
    if ($topic === '' || mb_strlen($topic) > 200) { $errors[] = 'Enter a topic (up to 200 characters).'; }
    if (!db_val('SELECT id FROM subjects WHERE id = ? AND department_id = ?', [$subjectId, $deptId])) { $errors[] = 'Choose a subject from your department.'; }
    if ($sem < 1 || $sem > 8) { $errors[] = 'Choose a semester.'; }
    $file = null;
    if (!$errors) {
        $r = upload_save($_FILES['note'] ?? [], 'notes', NOTE_TYPES, MAX_NOTE_MB * 1048576);
        if ($r['ok']) { $file = $r; } else { $errors[] = 'Note file: ' . $r['error']; }
    }
    if (!$errors) {
        try {
            db_exec('INSERT INTO notes (teacher_id, department_id, subject_id, title, topic, description, semester, file_name, original_name, file_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$uid, $deptId, $subjectId, $title, $topic, $desc !== '' ? $desc : null, $sem, $file['file'], $file['orig'], $file['size']]);
            flash('success', 'Note uploaded. Students can see it now.');
            redirect('teacher/notes.php');
        } catch (Throwable $t) {
            upload_delete('notes', $file['file']);
            error_log('Upload failed: ' . $t->getMessage());
            $errors[] = 'Could not save the note. Please try again.';
        }
    }
}

render_header(['title' => 'Upload note', 'layout' => 'app', 'active' => 'upload', 'heading' => 'Upload a note', 'sub' => 'PDF only, up to ' . MAX_NOTE_MB . ' MB.']);
?>
<?php if ($locked): ?>
  <div class="card"><?= empty_state('Uploads are locked', $locked) ?></div>
<?php elseif (!$subjects): ?>
  <div class="card"><?= empty_state('No subjects yet', 'Your department has no subjects to file notes under.',
      $u['role'] === 'hod' ? '<a class="btn primary" href="' . e(url('hod/subjects.php')) . '">Add subjects</a>' : '<span class="muted">Ask your HOD or the manager to add subjects.</span>') ?></div>
<?php else: ?>
  <?php if ($errors): ?><div class="flash error"><?= icon('alert') ?><span><?php foreach ($errors as $er) { echo e($er) . '<br>'; } ?></span></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="card card-pad" style="max-width:820px">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="field span-2"><label for="title">Title</label><input class="input" id="title" name="title" value="<?= old('title') ?>" maxlength="200" required></div>
      <div class="field"><label for="subject_id">Subject</label><select class="select" id="subject_id" name="subject_id" required><?= select_options($subjects, 'id', 'name', $_POST['subject_id'] ?? null, 'Select subject') ?></select></div>
      <div class="field"><label for="semester">Semester</label><select class="select" id="semester" name="semester" required><?= semester_options($_POST['semester'] ?? null) ?></select></div>
      <div class="field span-2"><label for="topic">Topic</label><input class="input" id="topic" name="topic" value="<?= old('topic') ?>" maxlength="200" placeholder="e.g. Binary trees and traversals" required></div>
      <div class="field span-2"><label for="description">Description <span class="muted">(optional)</span></label><textarea class="textarea" id="description" name="description"><?= old('description') ?></textarea></div>
      <div class="field span-2"><label for="note">PDF file</label><input class="input" id="note" name="note" type="file" accept="application/pdf,.pdf" required></div>
    </div>
    <div class="form-actions"><button class="btn primary" type="submit"><?= icon('upload', 16) ?>Upload note</button><a class="btn" href="<?= e(url('teacher/notes.php')) ?>">Cancel</a></div>
  </form>
<?php endif; ?>
<?php render_footer();
