<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role('hod');
$dept = hod_dept_id((int)$u['id']);
$deptName = $dept ? db_val('SELECT name FROM departments WHERE id = ?', [$dept]) : null;

render_header(['title' => 'Dashboard', 'layout' => 'app', 'active' => 'dashboard',
    'heading' => $deptName ? $deptName . ' department' : 'Dashboard', 'sub' => 'Head of Department overview']);
if (!$dept): ?>
  <div class="card"><?= empty_state('No department assigned', 'Ask the manager to link your account to a department.') ?></div>
<?php else:
  $teachers = (int)db_val("SELECT COUNT(*) FROM teacher_profiles WHERE department_id = ? AND approval = 'approved'", [$dept]);
  $pending  = db_all("SELECT u.id, u.name, u.photo, tp.qualification, u.created_at FROM teacher_profiles tp JOIN users u ON u.id = tp.user_id WHERE tp.department_id = ? AND tp.approval = 'pending' ORDER BY u.created_at", [$dept]);
  $notes    = (int)db_val('SELECT COUNT(*) FROM notes WHERE department_id = ?', [$dept]);
  $students = (int)db_val('SELECT COUNT(*) FROM student_profiles sp JOIN courses c ON c.id = sp.course_id WHERE c.department_id = ?', [$dept]);
?>
<div class="stats">
  <div class="stat"><span class="stat-ic amber"><?= icon('clock', 22) ?></span><div><b><?= count($pending) ?></b><span>Awaiting review</span></div></div>
  <div class="stat"><span class="stat-ic mint"><?= icon('award', 22) ?></span><div><b><?= $teachers ?></b><span>Verified teachers</span></div></div>
  <div class="stat"><span class="stat-ic"><?= icon('file', 22) ?></span><div><b><?= $notes ?></b><span>Notes</span></div></div>
  <div class="stat"><span class="stat-ic rose"><?= icon('cap', 22) ?></span><div><b><?= $students ?></b><span>Students</span></div></div>
</div>
<div class="card">
  <div class="card-head"><h2>Teachers waiting for you</h2><a href="<?= e(url('hod/teachers.php')) ?>">All teachers</a></div>
  <?php if ($pending): ?>
  <div class="table-wrap"><table class="table"><tbody>
    <?php foreach ($pending as $t): ?>
      <tr><td><div class="who"><?= avatar($t, 'sm') ?><div><strong><?= e($t['name']) ?></strong><small><?= e($t['qualification']) ?>, applied <?= e(fmt_date($t['created_at'])) ?></small></div></div></td>
        <td class="actions">
          <a class="btn sm" href="<?= e(url('file.php?cert=' . (int)$t['id'])) ?>" target="_blank" rel="noopener"><?= icon('eye', 15) ?>Certificate</a>
          <form method="post" action="<?= e(url('hod/teachers.php')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="teacher_id" value="<?= (int)$t['id'] ?>"><input type="hidden" name="action" value="approved"><button class="btn sm ok" type="submit"><?= icon('check', 15) ?>Approve</button></form>
          <form method="post" action="<?= e(url('hod/teachers.php')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="teacher_id" value="<?= (int)$t['id'] ?>"><input type="hidden" name="action" value="rejected"><button class="btn sm danger" type="submit"><?= icon('x', 15) ?>Reject</button></form>
        </td></tr>
    <?php endforeach; ?></tbody></table></div>
  <?php else: ?><?= empty_state('All caught up', 'No teachers are waiting for verification.') ?><?php endif; ?>
</div>
<?php endif; render_footer();
