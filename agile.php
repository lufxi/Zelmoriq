<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Mirrors docs/AGILE_PLAN.md. Points are Fibonacci story points.
$sprints = [
  ['Sprint 0', 'Discovery and audit', 'Understand what exists and what is broken.',
     ['Code audit of the original portal', 'Bug log and risk list', 'Product vision and backlog', 'Pick the Zelmoriq name and look']],
  ['Sprint 1', 'Foundation', 'One secure way in for every role.',
     ['Unified users table and login', 'CSRF, prepared statements, safe uploads', 'Automatic base URL (no hard-coded paths)', 'Design system and layouts']],
  ['Sprint 2', 'Teacher and student journeys', 'Upload a note, find a note.',
     ['Registration with certificate', 'Upload, edit, delete notes', 'Browse, filter, preview, download', 'Star ratings']],
  ['Sprint 3', 'HOD verification', 'Only verified teachers publish.',
     ['Approve and reject teachers', 'Certificate viewing with access control', 'Department notes moderation', 'Student list with CSV export']],
  ['Sprint 4', 'Manager console', 'Run the whole portal.',
     ['Departments, courses, subjects', 'Create and manage HODs', 'User management and messages', 'Contact form that works']],
  ['Sprint 5', 'Hardening and release', 'Prove it works, then ship.',
     ['Regression and UAT with real users', 'Accessibility and mobile pass', 'Backup and deployment guide', 'Retrospective and next backlog']],
];
$backlog = [
  ['ZQ-01','Visitor','see what Zelmoriq is and sign up','Must',3,1],
  ['ZQ-02','User','log in once whatever my role','Must',3,1],
  ['ZQ-03','User','reset a forgotten password','Should',3,1],
  ['ZQ-04','Teacher','register with my department and certificate','Must',5,2],
  ['ZQ-05','Student','register with my course and semester','Must',3,2],
  ['ZQ-06','Teacher','upload a PDF note with subject, topic and semester','Must',5,2],
  ['ZQ-07','Student','filter notes by department, subject, semester','Must',5,2],
  ['ZQ-08','Student','preview and download a note','Must',3,2],
  ['ZQ-09','Student','rate a note from 1 to 5 stars','Should',3,2],
  ['ZQ-10','Teacher','edit or delete my notes','Should',3,2],
  ['ZQ-11','HOD','approve or reject teachers in my department','Must',5,3],
  ['ZQ-12','HOD','view a teacher\'s certificate privately','Must',3,3],
  ['ZQ-13','HOD','remove inappropriate notes','Should',2,3],
  ['ZQ-14','HOD','see and export my department\'s students','Could',3,3],
  ['ZQ-15','HOD','manage subjects for my department','Should',2,3],
  ['ZQ-16','Manager','manage departments, courses and subjects','Must',5,4],
  ['ZQ-17','Manager','create and disable HOD accounts','Must',5,4],
  ['ZQ-18','Manager','see all users and disable abusers','Should',3,4],
  ['ZQ-19','Manager','read messages from the contact form','Should',2,4],
  ['ZQ-20','Everyone','use the site comfortably on a phone','Must',5,5],
  ['ZQ-21','Team','run a user acceptance test and fix findings','Must',8,5],
  ['ZQ-22','Visitor','get e-mailed reset links and notifications','Could',8,6],
  ['ZQ-23','Student','save favourite notes and see recently viewed','Could',5,6],
];
$byS = [];
foreach ($backlog as $b) { $byS[$b[5]] = ($byS[$b[5]] ?? 0) + $b[4]; }
$maxPts = max($byS);
$tone = ['Must' => 'bad', 'Should' => 'warn', 'Could' => 'info'];

render_header(['title' => 'Roadmap and Agile plan', 'layout' => 'public', 'description' => 'How Zelmoriq is planned and built using Agile Scrum.']);
?>
<section class="section tight"><div class="wrap">
  <div class="section-head">
    <h1 style="font-size:clamp(34px,4.4vw,54px);font-weight:800;letter-spacing:-.03em">Built in sprints, not in one big bang</h1>
    <p>Zelmoriq follows Agile Scrum: a prioritised backlog, two-week sprints, a working increment at the end of each, and a retrospective before the next.</p>
  </div>
  <div class="sprints">
    <?php foreach ($sprints as $i => $s): ?>
      <article class="card sprint">
        <header><h3><?= e($s[0]) ?></h3><?= badge($i <= 4 ? 'Built' : 'UAT pending', $i <= 4 ? 'ok' : 'warn') ?></header>
        <p class="muted small" style="margin:0 0 8px"><?= e($s[1]) ?></p>
        <p class="goal">Goal: <?= e($s[2]) ?></p>
        <ul><?php foreach ($s[3] as $li) { echo '<li>' . e($li) . '</li>'; } ?></ul>
      </article>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="section tight"><div class="wrap two-col">
  <div class="card">
    <div class="card-head"><h2>Product backlog</h2><span class="muted small"><?= count($backlog) ?> stories</span></div>
    <div class="table-wrap"><table class="table" id="backlog">
      <thead><tr><th>ID</th><th>User story</th><th>Priority</th><th class="num">Points</th><th>Sprint</th></tr></thead>
      <tbody>
      <?php foreach ($backlog as $b): ?>
        <tr><td class="nowrap"><?= e($b[0]) ?></td>
            <td>As a <b><?= e($b[1]) ?></b> I want to <?= e($b[2]) ?>.</td>
            <td><?= badge($b[3], $tone[$b[3]]) ?></td><td class="num"><?= (int)$b[4] ?></td><td>S<?= (int)$b[5] ?></td></tr>
      <?php endforeach; ?>
      </tbody></table></div>
  </div>
  <div class="stack">
    <div class="card card-pad">
      <h3>Planned scope per sprint</h3>
      <div class="velocity">
        <?php foreach ($byS as $s => $pts): ?>
          <div class="vel"><span>Sprint <?= (int)$s ?></span><span class="vel-track"><i style="width:<?= (int)round($pts / $maxPts * 100) ?>%"></i></span><b><?= (int)$pts ?> pts</b></div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card card-pad prose">
      <h3 style="margin-top:0">Definition of done</h3>
      <ul>
        <li>Acceptance criteria met and demoed to the product owner</li>
        <li>Forms validated on the server, actions protected by CSRF</li>
        <li>Works on phone and desktop, keyboard reachable</li>
        <li>No PHP warnings; queries use prepared statements</li>
        <li>Documented in the README or changelog</li>
      </ul>
      <h3>Ceremonies</h3>
      <ul><li>Sprint planning (start)</li><li>Daily stand-up, 10 minutes</li><li>Sprint review with a live demo</li><li>Retrospective: keep, stop, try</li></ul>
    </div>
  </div>
</div></section>
<?php render_footer();
