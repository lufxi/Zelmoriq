<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role('manager');
$c = function (string $sql) { return (int)db_val($sql); };
$counts = [
    'students' => $c("SELECT COUNT(*) FROM users WHERE role = 'student'"),
    'teachers' => $c("SELECT COUNT(*) FROM users WHERE role = 'teacher'"),
    'hods'     => $c("SELECT COUNT(*) FROM users WHERE role = 'hod'"),
    'notes'    => $c('SELECT COUNT(*) FROM notes'),
    'pending'  => $c("SELECT COUNT(*) FROM teacher_profiles WHERE approval = 'pending'"),
    'unread'   => $c('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0'),
];
$perDept = db_all('SELECT d.id, d.name, (SELECT COUNT(*) FROM notes WHERE department_id = d.id) AS n FROM departments d ORDER BY n DESC, d.name');
$maxN    = max(1, ...array_map(function ($r) { return (int)$r['n']; }, $perDept ?: [['n' => 1]]));
$newest  = db_all('SELECT name, email, role, photo, created_at FROM users ORDER BY created_at DESC, id DESC LIMIT 6');
$noHod   = db_all('SELECT d.name FROM departments d LEFT JOIN hod_profiles h ON h.department_id = d.id WHERE h.user_id IS NULL ORDER BY d.name');

render_header(['title' => 'Dashboard', 'layout' => 'app', 'active' => 'dashboard', 'heading' => 'Manager console', 'sub' => 'Everything happening across Zelmoriq.']);
?>
<div class="stats">
  <div class="stat"><span class="stat-ic"><?= icon('cap', 22) ?></span><div><b><?= $counts['students'] ?></b><span>Students</span></div></div>
  <div class="stat"><span class="stat-ic mint"><?= icon('award', 22) ?></span><div><b><?= $counts['teachers'] ?></b><span>Teachers</span></div></div>
  <div class="stat"><span class="stat-ic rose"><?= icon('shield', 22) ?></span><div><b><?= $counts['hods'] ?></b><span>HODs</span></div></div>
  <div class="stat"><span class="stat-ic amber"><?= icon('file', 22) ?></span><div><b><?= $counts['notes'] ?></b><span>Notes</span></div></div>
</div>
<?php if ($counts['pending'] || $counts['unread']): ?>
  <div class="banner warn"><?= icon('clock', 22) ?><div><h3>Needs attention</h3><p>
    <?php if ($counts['pending']): ?><a href="<?= e(url('manager/teachers.php?status=pending')) ?>"><?= $counts['pending'] ?> teacher<?= $counts['pending'] === 1 ? '' : 's' ?> awaiting approval</a>. <?php endif; ?>
    <?php if ($counts['unread']): ?><a href="<?= e(url('manager/messages.php')) ?>"><?= $counts['unread'] ?> unread message<?= $counts['unread'] === 1 ? '' : 's' ?></a>.<?php endif; ?></p></div></div>
<?php endif; ?>
<?php if ($noHod): ?>
  <div class="banner warn"><?= icon('info', 22) ?><div><h3>Departments without a HOD</h3><p><?= e(implode(', ', array_column($noHod, 'name'))) ?>. <a href="<?= e(url('manager/hods.php')) ?>">Add a HOD</a> so teachers there can be verified.</p></div></div>
<?php endif; ?>
<div class="two-col">
  <div class="card card-pad"><h3>Notes by department</h3>
    <ul class="bars"><?php foreach ($perDept as $d): ?>
      <li style="--h:<?= dept_hue((int)$d['id']) ?>"><span><?= e($d['name']) ?></span><span class="track"><i style="width:<?= (int)round($d['n'] / $maxN * 100) ?>%"></i></span><b><?= (int)$d['n'] ?></b></li>
    <?php endforeach; ?></ul>
  </div>
  <div class="card"><div class="card-head"><h3>Newest accounts</h3><a href="<?= e(url('manager/users.php')) ?>">All users</a></div>
    <div class="table-wrap"><table class="table"><tbody>
    <?php foreach ($newest as $n): ?>
      <tr><td><div class="who"><?= avatar($n, 'sm') ?><div><strong><?= e($n['name']) ?></strong><small><?= e(role_label($n['role'])) ?>, <?= e(fmt_date($n['created_at'])) ?></small></div></div></td></tr>
    <?php endforeach; ?></tbody></table></div>
  </div>
</div>
<?php render_footer();
