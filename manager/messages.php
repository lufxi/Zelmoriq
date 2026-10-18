<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('manager');
if (is_post()) {
    csrf_check();
    $id = post_int('id');
    if (post_str('action') === 'delete')      { db_exec('DELETE FROM contact_messages WHERE id = ?', [$id]); flash('success', 'Message deleted.'); }
    elseif (post_str('action') === 'read')    { db_exec('UPDATE contact_messages SET is_read = 1 WHERE id = ?', [$id]); }
    elseif (post_str('action') === 'unread')  { db_exec('UPDATE contact_messages SET is_read = 0 WHERE id = ?', [$id]); }
    redirect('manager/messages.php');
}
$rows = db_all('SELECT * FROM contact_messages ORDER BY is_read, created_at DESC, id DESC');
render_header(['title' => 'Messages', 'layout' => 'app', 'active' => 'messages', 'heading' => 'Messages', 'sub' => 'Sent from the contact form on the public site.']);
?>
<?php if (!$rows): ?><div class="card"><?= empty_state('Inbox is empty', 'Messages from the contact form show up here.') ?></div><?php endif; ?>
<div class="stack">
<?php foreach ($rows as $m): ?>
  <article class="card card-pad">
    <div class="row between"><div><strong><?= e($m['subject']) ?></strong> <?= $m['is_read'] ? '' : badge('New', 'info') ?><br>
      <small class="muted"><?= e($m['name']) ?> (<?= e($m['email']) ?>), <?= e(fmt_date($m['created_at'], true)) ?></small></div>
      <div class="btn-row">
        <a class="btn sm" href="mailto:<?= e($m['email']) ?>?subject=<?= e(rawurlencode('Re: ' . $m['subject'])) ?>"><?= icon('mail', 15) ?>Reply</a>
        <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="action" value="<?= $m['is_read'] ? 'unread' : 'read' ?>"><button class="btn sm" type="submit"><?= $m['is_read'] ? 'Mark unread' : 'Mark read' ?></button></form>
        <form method="post" class="inline" data-confirm="Delete this message?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn sm danger" type="submit"><?= icon('trash', 15) ?></button></form>
      </div></div>
    <p style="margin:14px 0 0;white-space:pre-line"><?= e($m['message']) ?></p>
  </article>
<?php endforeach; ?>
</div>
<?php render_footer();
