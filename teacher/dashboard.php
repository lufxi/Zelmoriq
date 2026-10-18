<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u  = require_role('teacher');
$tp = teacher_profile((int)$u['id']);
$mine = (int)db_val('SELECT COUNT(*) FROM notes WHERE teacher_id = ?', [$u['id']]);
$dl   = (int)db_val('SELECT COALESCE(SUM(downloads), 0) FROM notes WHERE teacher_id = ?', [$u['id']]);
$avg  = db_val('SELECT ROUND(AVG(r.rating), 1) FROM ratings r JOIN notes n ON n.id = r.note_id WHERE n.teacher_id = ?', [$u['id']]);
$recent = db_all(
    'SELECT n.id, n.title, n.semester, n.downloads, n.created_at, s.name AS subject_name,
            (SELECT ROUND(AVG(rating), 1) FROM ratings WHERE note_id = n.id) AS avg_rating,
            (SELECT COUNT(*) FROM ratings WHERE note_id = n.id) AS rating_count
       FROM notes n JOIN subjects s ON s.id = n.subject_id WHERE n.teacher_id = ? ORDER BY n.created_at DESC LIMIT 5', [$u['id']]);
$approved = $tp && $tp['approval'] === 'approved';

render_header(['title' => 'Dashboard', 'layout' => 'app', 'active' => 'dashboard',
    'heading' => 'Welcome, ' . explode(' ', $u['name'])[0],
    'sub' => $tp ? $tp['department_name'] . ' department' : '',
    'actions' => $approved ? '<a class="btn primary" href="' . e(url('teacher/upload.php')) . '">' . icon('upload', 16) . 'Upload a note</a>' : '']);
?>
<?php if (!$tp): ?>
  <div class="banner bad"><?= icon('alert', 22) ?><div><h3>Teacher profile missing</h3><p>Contact the manager to fix your account.</p></div></div>
<?php elseif ($tp['approval'] === 'pending'): ?>
  <div class="banner warn"><?= icon('clock', 22) ?><div><h3>Waiting for your HOD</h3><p>Your certificate is being reviewed. You can upload as soon as you are approved.</p></div></div>
<?php elseif ($tp['approval'] === 'rejected'): ?>
  <div class="banner bad"><?= icon('x', 22) ?><div><h3>Verification was not approved</h3><p>Upload a clearer certificate in your <a href="<?= e(url('profile.php')) ?>">profile</a> to be reviewed again, or contact your HOD.</p></div></div>
<?php else: ?>
  <div class="banner ok"><?= icon('shield', 22) ?><div><h3>You are verified</h3><p>Your notes show up for students in <?= e($tp['department_name']) ?>.</p></div></div>
<?php endif; ?>
<div class="stats">
  <div class="stat"><span class="stat-ic"><?= icon('file', 22) ?></span><div><b><?= $mine ?></b><span>Notes shared</span></div></div>
  <div class="stat"><span class="stat-ic mint"><?= icon('download', 22) ?></span><div><b><?= $dl ?></b><span>Downloads</span></div></div>
  <div class="stat"><span class="stat-ic amber"><?= icon('award', 22) ?></span><div><b><?= $avg !== null ? e($avg) : '&ndash;' ?></b><span>Average rating</span></div></div>
</div>
<div class="card">
  <div class="card-head"><h2>Recent notes</h2><a href="<?= e(url('teacher/notes.php')) ?>">Manage all</a></div>
  <?php if ($recent): ?>
  <div class="table-wrap"><table class="table"><thead><tr><th>Title</th><th>Subject</th><th>Sem</th><th>Rating</th><th class="num">Downloads</th></tr></thead><tbody>
    <?php foreach ($recent as $n): ?>
      <tr><td><strong><?= e($n['title']) ?></strong><br><small class="muted"><?= e(fmt_date($n['created_at'])) ?></small></td><td><?= e($n['subject_name']) ?></td><td><?= (int)$n['semester'] ?></td><td><?= stars($n['avg_rating'], (int)$n['rating_count']) ?></td><td class="num"><?= (int)$n['downloads'] ?></td></tr>
    <?php endforeach; ?></tbody></table></div>
  <?php else: ?><?= empty_state('No notes yet', $approved ? 'Upload your first note to get started.' : 'Notes you upload will appear here.') ?><?php endif; ?>
</div>
<?php render_footer();
