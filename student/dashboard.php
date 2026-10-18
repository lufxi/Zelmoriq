<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u  = require_role('student');
$sp = student_profile((int)$u['id']);
$deptId = $sp ? (int)$sp['department_id'] : 0;

$inDept  = $sp ? (int)db_val('SELECT COUNT(*) FROM notes WHERE department_id = ?', [$deptId]) : 0;
$inSem   = $sp ? (int)db_val('SELECT COUNT(*) FROM notes WHERE department_id = ? AND semester = ?', [$deptId, $sp['semester']]) : 0;
$rated   = (int)db_val('SELECT COUNT(*) FROM ratings WHERE student_id = ?', [$u['id']]);
$latest  = $sp ? db_all(
    'SELECT n.*, t.name AS teacher_name, t.photo AS teacher_photo, d.name AS dept_name, s.name AS subject_name,
            (SELECT ROUND(AVG(rating), 1) FROM ratings WHERE note_id = n.id) AS avg_rating,
            (SELECT COUNT(*) FROM ratings WHERE note_id = n.id) AS rating_count,
            (SELECT rating FROM ratings WHERE note_id = n.id AND student_id = ?) AS my_rating
       FROM notes n JOIN users t ON t.id = n.teacher_id JOIN departments d ON d.id = n.department_id JOIN subjects s ON s.id = n.subject_id
      WHERE n.department_id = ? ORDER BY (n.semester = ?) DESC, n.created_at DESC LIMIT 6',
    [$u['id'], $deptId, $sp['semester']]
) : [];

render_header(['title' => 'Dashboard', 'layout' => 'app', 'active' => 'dashboard',
    'heading' => 'Hi, ' . explode(' ', $u['name'])[0],
    'sub' => $sp ? $sp['course_name'] . ', semester ' . $sp['semester'] : 'Complete your profile to see notes for your course.',
    'actions' => '<a class="btn primary" href="' . e(url('student/notes.php')) . '">' . icon('search', 16) . 'Browse notes</a>']);
?>
<?php if (!$sp): ?>
  <div class="banner warn"><?= icon('alert', 22) ?><div><h3>Your course is missing</h3><p>Add your course and semester in your <a href="<?= e(url('profile.php')) ?>">profile</a>.</p></div></div>
<?php endif; ?>
<div class="stats">
  <div class="stat"><span class="stat-ic"><?= icon('layers', 22) ?></span><div><b><?= $inDept ?></b><span>Notes in your department</span></div></div>
  <div class="stat"><span class="stat-ic mint"><?= icon('book', 22) ?></span><div><b><?= $inSem ?></b><span>For your semester</span></div></div>
  <div class="stat"><span class="stat-ic amber"><?= icon('award', 22) ?></span><div><b><?= $rated ?></b><span>Notes you rated</span></div></div>
</div>
<div class="row between" style="margin-bottom:16px"><h2 style="margin:0;font-size:22px">Fresh for you</h2><a href="<?= e(url('student/notes.php')) ?>">See all notes</a></div>
<?php if ($latest): ?>
  <div class="sheets-grid"><?php foreach ($latest as $n) { echo note_card($n, ['rate' => true]); } ?></div>
<?php else: ?>
  <div class="card"><?= empty_state('Nothing here yet', 'Your department has no notes yet. Check back soon, or browse other departments.', '<a class="btn primary" href="' . e(url('student/notes.php?department=0')) . '">Browse all notes</a>') ?></div>
<?php endif; ?>
<?php render_footer();
