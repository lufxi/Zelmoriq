<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role('hod');
$dept = hod_dept_id((int)$u['id']);
if (!$dept) { flash('error', 'Your account is not linked to a department.'); redirect('hod/dashboard.php'); }

if (is_post()) {
    csrf_check();
    $action = post_str('action');
    $n = review_teacher(post_int('teacher_id'), $action, (int)$u['id'], $dept);
    flash($n ? 'success' : 'error', $n ? 'Teacher marked as ' . $action . '.' : 'No change made.');
    redirect('hod/teachers.php' . (get_str('status') ? '?status=' . urlencode(get_str('status')) : ''));
}
$status = get_str('status');
if (!in_array($status, ['pending', 'approved', 'rejected'], true)) { $status = ''; }
$rows = db_all(
    'SELECT u.id, u.name, u.email, u.phone, u.photo, tp.qualification, tp.college, tp.approval, tp.certificate,
            (SELECT COUNT(*) FROM notes WHERE teacher_id = u.id) AS note_count
       FROM teacher_profiles tp JOIN users u ON u.id = tp.user_id
      WHERE tp.department_id = ?' . ($status ? ' AND tp.approval = ?' : '') . ' ORDER BY FIELD(tp.approval, \'pending\', \'approved\', \'rejected\'), u.name',
    $status ? [$dept, $status] : [$dept]);

render_header(['title' => 'Teachers', 'layout' => 'app', 'active' => 'teachers', 'heading' => 'Teachers', 'sub' => 'Review certificates and decide who can publish.',
    'actions' => '<div class="btn-row">' . implode('', array_map(function ($s) use ($status) {
        return '<a class="btn sm' . ($status === $s[0] ? ' primary' : '') . '" href="?status=' . $s[0] . '">' . $s[1] . '</a>';
    }, [['', 'All'], ['pending', 'Pending'], ['approved', 'Approved'], ['rejected', 'Rejected']])) . '</div>']);
?>
<div class="card">
<?php if ($rows): ?>
  <div class="table-wrap"><table class="table"><thead><tr><th>Teacher</th><th>College</th><th>Status</th><th class="num">Notes</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $t): ?>
    <tr>
      <td><div class="who"><?= avatar($t, 'sm') ?><div><strong><?= e($t['name']) ?></strong><small><?= e($t['qualification']) ?>, <?= e($t['email']) ?></small></div></div></td>
      <td><?= e($t['college']) ?></td><td><?= approval_badge($t['approval']) ?></td><td class="num"><?= (int)$t['note_count'] ?></td>
      <td class="actions">
        <?php if ($t['certificate']): ?><a class="btn sm" href="<?= e(url('file.php?cert=' . (int)$t['id'])) ?>" target="_blank" rel="noopener"><?= icon('eye', 15) ?>Certificate</a><?php endif; ?>
        <?php foreach (['approved' => ['Approve', 'ok', 'check'], 'rejected' => ['Reject', 'danger', 'x']] as $act => $d): if ($t['approval'] === $act) { continue; } ?>
          <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="teacher_id" value="<?= (int)$t['id'] ?>"><input type="hidden" name="action" value="<?= $act ?>"><button class="btn sm <?= $d[1] ?>" type="submit"><?= icon($d[2], 15) ?><?= $d[0] ?></button></form>
        <?php endforeach; ?>
      </td>
    </tr>
  <?php endforeach; ?></tbody></table></div>
<?php else: ?><?= empty_state('No teachers here', 'Nobody matches this filter yet.') ?><?php endif; ?>
</div>
<?php render_footer();
