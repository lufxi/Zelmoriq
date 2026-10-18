<?php
require_once __DIR__ . '/includes/bootstrap.php';

$stats = [
    'notes'  => (int)db_val('SELECT COUNT(*) FROM notes'),
    'depts'  => (int)db_val('SELECT COUNT(DISTINCT department_id) FROM notes'),
    'people' => (int)db_val("SELECT COUNT(*) FROM teacher_profiles WHERE approval = 'approved'"),
];
$recent = db_all(
    'SELECT n.*, t.name AS teacher_name, t.photo AS teacher_photo, d.name AS dept_name, s.name AS subject_name,
            (SELECT ROUND(AVG(rating), 1) FROM ratings WHERE note_id = n.id) AS avg_rating,
            (SELECT COUNT(*) FROM ratings WHERE note_id = n.id) AS rating_count
       FROM notes n
       JOIN users t ON t.id = n.teacher_id
       JOIN departments d ON d.id = n.department_id
       JOIN subjects s ON s.id = n.subject_id
      ORDER BY n.created_at DESC, n.id DESC LIMIT 6'
);
$team = [
    ['Fathima Nahan E.A', 'Lead Developer'],
    ['Fathimma Asif',     'Database Specialist'],
    ['Saeeda K S',        'UI/UX Designer'],
];

$ld = '<script type="application/ld+json">' . json_encode([
    '@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => APP_NAME,
    'description' => 'Class notes shared by HOD-verified teachers for college students.',
    'url' => absolute_url('index.php'),
], JSON_UNESCAPED_SLASHES) . '</script>';
render_header(['title' => APP_NAME, 'layout' => 'public', 'head' => $ld]);
?>
<section class="hero">
  <div class="wrap hero-in">
    <div>
      <h1>Class notes you can trust, from the teachers who wrote them.</h1>
      <p class="lead">Zelmoriq lets teachers verified by their Head of Department share notes with the whole department. Students search by subject and semester, rate what helped, and download in one tap.</p>
      <div class="hero-cta">
        <a class="btn primary" href="<?= e(url('register.php')) ?>">Create a free account</a>
        <a class="btn" href="<?= e(url('login.php')) ?>">Log in</a>
      </div>
      <p class="hero-note">Teachers: register with your certificate and your HOD will verify you.</p>
    </div>
    <div class="desk" aria-hidden="true">
      <div class="sheet" style="--h:236"><span class="sheet-tab">Computer Science</span><h3 class="sheet-title">Trees and Graphs</h3>
        <div class="chips"><span class="chip">Data Structures</span><span class="chip">Semester 3</span></div><div class="lines"><i></i><i></i><i class="short"></i></div></div>
      <div class="sheet" style="--h:8"><span class="sheet-tab">Commerce</span><h3 class="sheet-title">Final Accounts Walkthrough</h3>
        <div class="chips"><span class="chip">Financial Accounting</span><span class="chip">Semester 2</span></div><div class="lines"><i></i><i class="short"></i></div>
        <div class="sheet-rating"><span class="stars" style="--p:92%"></span><span class="rating-num">4.6</span></div></div>
      <div class="sheet" style="--h:164"><span class="sheet-tab">Physics</span><h3 class="sheet-title">Electromagnetism Revision</h3>
        <div class="chips"><span class="chip">Electromagnetism</span><span class="chip">Semester 4</span></div><div class="lines"><i></i><i></i><i class="short"></i></div></div>
      <span class="stamp"><?= icon('shield', 16) ?>Verified by HOD</span>
    </div>
  </div>
</section>

<section class="live-line"><div class="wrap">
  <?php if ($stats['notes'] > 0): ?>
    <p>Right now Zelmoriq holds <b><?= $stats['notes'] ?> note<?= $stats['notes'] === 1 ? '' : 's' ?></b> across <b><?= $stats['depts'] ?> department<?= $stats['depts'] === 1 ? '' : 's' ?></b>, shared by <b><?= $stats['people'] ?> verified teacher<?= $stats['people'] === 1 ? '' : 's' ?></b>.</p>
  <?php else: ?>
    <p>Zelmoriq is ready. The first verified teacher to upload gets the shelf to themselves.</p>
  <?php endif; ?>
</div></section>

<section class="section" id="how"><div class="wrap">
  <div class="section-head"><h2>How a note earns its place</h2><p>Nothing reaches students until a Head of Department has verified the person who wrote it.</p></div>
  <ol class="steps">
    <li class="step"><?= badge('Teacher', 'info') ?><h3>Registers with proof</h3><p>Teachers sign up with their department and a teaching certificate or ID.</p></li>
    <li class="step"><?= badge('HOD', 'ok') ?><h3>Verifies the teacher</h3><p>The Head of Department checks the certificate and approves or rejects.</p></li>
    <li class="step"><?= badge('Teacher', 'info') ?><h3>Uploads notes</h3><p>Approved teachers upload PDFs tagged by subject, topic and semester.</p></li>
    <li class="step"><?= badge('Student', 'warn') ?><h3>Finds, rates, downloads</h3><p>Students filter by what they study, preview, download and rate each note.</p></li>
  </ol>
</div></section>

<section class="section tight" id="notes"><div class="wrap">
  <div class="section-head"><h2>Latest on the shelf</h2><p>Log in to preview and download.</p></div>
  <?php if ($recent): ?>
    <div class="sheets-grid">
      <?php foreach ($recent as $n) { echo note_card($n, ['actions' => false]); } ?>
    </div>
  <?php else: ?>
    <div class="card"><?= empty_state('No notes yet', 'Notes appear here as soon as a verified teacher uploads the first one.') ?></div>
  <?php endif; ?>
</div></section>

<section class="section" id="about"><div class="wrap">
  <div class="section-head"><h2>Built by a student team</h2><p>Zelmoriq started as a college project to end the scramble for reliable notes in scattered chat groups.</p></div>
  <div class="about-grid">
    <?php foreach ($team as $p): ?>
      <div class="card person"><?= avatar(['name' => $p[0]]) ?><h3><?= e($p[0]) ?></h3><p><?= e($p[1]) ?></p></div>
    <?php endforeach; ?>
  </div>
</div></section>

<section class="section tight" id="contact"><div class="wrap contact-grid">
  <div>
    <h2 style="font-size:clamp(28px,3.4vw,40px);font-weight:800">Questions or ideas?</h2>
    <p class="muted">Tell us what is missing or what broke. Messages go straight to the manager's inbox.</p>
    <ul>
      <li><?= icon('mail') ?>Need a department or subject added? Ask here.</li>
      <li><?= icon('shield') ?>Teacher verification stuck? Mention your department.</li>
      <li><?= icon('clock') ?>We reply by email.</li>
    </ul>
  </div>
  <form class="card card-pad stack" method="post" action="<?= e(url('contact.php')) ?>">
    <?= csrf_field() ?>
    <input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
    <div class="form-grid">
      <div class="field"><label for="c-name">Your name</label><input class="input" id="c-name" name="name" required></div>
      <div class="field"><label for="c-email">Email</label><input class="input" id="c-email" name="email" type="email" required></div>
      <div class="field span-2"><label for="c-subject">Subject</label><input class="input" id="c-subject" name="subject" required></div>
      <div class="field span-2"><label for="c-message">Message</label><textarea class="textarea" id="c-message" name="message" maxlength="4000" required></textarea></div>
    </div>
    <div><button class="btn primary" type="submit">Send message</button></div>
  </form>
</div></section>
<?php render_footer();
