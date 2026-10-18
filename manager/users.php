<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_role('manager');
if (is_post()) {
    csrf_check();
    $id = post_int('id'); $target = db_one('SELECT id, role FROM users WHERE id = ?', [$id]);
    if (!$target)                                   { flash('error', 'User not found.'); }
    elseif ((int)$target['id'] === (int)$me['id'])  { flash('error', 'You cannot change your own account here.'); }
    elseif (post_str('action') === 'toggle')        { db_exec("UPDATE users SET status = IF(status = 'active', 'disabled', 'active') WHERE id = ?", [$id]); flash('success', 'Status changed.'); }
    elseif (post_str('action') === 'delete') {
        if ($target['role'] === 'manager' && (int)db_val("SELECT COUNT(*) FROM users WHERE role = 'manager'") < 2) { flash('error', 'You cannot delete the last manager.'); }
        else { delete_user_completely($id); flash('success', 'User deleted.'); }
    }
    redirect('manager/users.php');
}
$rows = db_all('SELECT id, name, email, role, photo, status, created_at FROM users ORDER BY created_at DESC, id DESC');
render_header(['title' => 'All users', 'layout' => 'app', 'active' => 'users', 'heading' => 'All users', 'sub' => count($rows) . ' accounts']);
?>
<div class="card">
  <div class="toolbar">
    <div class="field grow"><label for="f">Search</label><input class="input" id="f" data-filter-table="#users" placeholder="Name or email"></div>
    <div class="field"><label for="fr">Role</label><select class="select" id="fr" data-filter-table="#users" data-col="1"><option value="">All</option><?php foreach (ROLE_LABELS as $label) { echo '<option value="' . e($label) . '">' . e($label) . '</option>'; } ?></select></div>
  </div>
  <div class="table-wrap"><table class="table" id="users"><thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): $self = (int)$r['id'] === (int)$me['id']; ?>
    <tr><td><div class="who"><?= avatar($r, 'sm') ?><div><strong><?= e($r['name']) ?></strong><small><?= e($r['email']) ?></small></div></div></td>
      <td><?= e(role_label($r['role'])) ?></td><td><?= $r['status'] === 'active' ? badge('Active', 'ok') : badge('Disabled', 'bad') ?></td><td><?= e(fmt_date($r['created_at'])) ?></td>
      <td class="actions"><?php if (!$self): ?>
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn sm" type="submit"><?= $r['status'] === 'active' ? 'Disable' : 'Enable' ?></button></form>
        <form method="post" class="inline" data-confirm="Delete this user and all their notes?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn sm danger" type="submit"><?= icon('trash', 15) ?></button></form>
      <?php else: ?><span class="muted small">You</span><?php endif; ?></td></tr>
  <?php endforeach; ?>
    <tr data-empty hidden><td colspan="5"><?= empty_state('No users match') ?></td></tr>
  </tbody></table></div>
</div>
<?php render_footer();
