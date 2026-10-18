<?php
/**
 * Page chrome. Usage:
 *   render_header(['title'=>'Browse notes', 'layout'=>'app', 'active'=>'notes', 'heading'=>'Browse notes', 'sub'=>'...', 'actions'=>'<a ...>']);
 *   ... page content ...
 *   render_footer();
 * layout: 'public' (marketing pages), 'auth' (split login/register), 'app' (sidebar dashboard)
 */

function nav_items(string $role): array
{
    $pendingTeachers = 0;
    $unreadMessages  = 0;
    $u = user();
    if ($role === 'hod' && $u) {
        $d = hod_dept_id((int)$u['id']);
        if ($d) {
            $pendingTeachers = (int)db_val("SELECT COUNT(*) FROM teacher_profiles WHERE department_id = ? AND approval = 'pending'", [$d]);
        }
    }
    if ($role === 'manager') {
        $pendingTeachers = (int)db_val("SELECT COUNT(*) FROM teacher_profiles WHERE approval = 'pending'");
        $unreadMessages  = (int)db_val('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0');
    }

    $account = [
        ['profile',  'Profile',         'profile.php',  'user'],
        ['password', 'Change password', 'password.php', 'lock'],
    ];
    switch ($role) {
        case 'student':
            $work = [
                ['dashboard', 'Dashboard',    'student/dashboard.php', 'home'],
                ['notes',     'Browse notes', 'student/notes.php',     'search'],
            ];
            break;
        case 'teacher':
            $work = [
                ['dashboard', 'Dashboard',   'teacher/dashboard.php', 'home'],
                ['upload',    'Upload note', 'teacher/upload.php',    'upload'],
                ['mynotes',   'My notes',    'teacher/notes.php',     'file'],
            ];
            break;
        case 'hod':
            $work = [
                ['dashboard', 'Dashboard',        'hod/dashboard.php', 'home'],
                ['teachers',  'Teachers',         'hod/teachers.php',  'award',    $pendingTeachers],
                ['notes',     'Department notes', 'hod/notes.php',     'layers'],
                ['students',  'Students',         'hod/students.php',  'cap'],
                ['subjects',  'Subjects',         'hod/subjects.php',  'list'],
                ['upload',    'Upload note',      'teacher/upload.php','upload'],
                ['mynotes',   'My notes',         'teacher/notes.php', 'file'],
            ];
            break;
        default: // manager
            $work = [
                ['dashboard',   'Dashboard',   'manager/dashboard.php',   'home'],
                ['hods',        'HODs',        'manager/hods.php',        'shield'],
                ['departments', 'Departments', 'manager/departments.php', 'building'],
                ['courses',     'Courses',     'manager/courses.php',     'book'],
                ['subjects',    'Subjects',    'manager/subjects.php',    'list'],
                ['teachers',    'Teachers',    'manager/teachers.php',    'award', $pendingTeachers],
                ['users',       'All users',   'manager/users.php',       'users'],
                ['messages',    'Messages',    'manager/messages.php',    'inbox', $unreadMessages],
            ];
    }
    return [['Workspace', $work], ['Account', $account]];
}

function render_flash(): void
{
    foreach (flash_all() as $f) {
        $tone = in_array($f['t'], ['success', 'error', 'info', 'warn'], true) ? $f['t'] : 'info';
        $ic   = $tone === 'success' ? 'check' : ($tone === 'error' || $tone === 'warn' ? 'alert' : 'info');
        echo '<div class="flash ' . $tone . '" role="status" data-flash>' . icon($ic, 18)
           . '<span>' . e($f['m']) . '</span><button type="button" class="flash-x" data-flash-close aria-label="Dismiss">'
           . icon('x', 16) . '</button></div>';
    }
}

function render_header(array $o = []): void
{
    $GLOBALS['zq_layout'] = $o['layout'] ?? 'public';
    $layout  = $GLOBALS['zq_layout'];
    $title   = $o['title'] ?? APP_NAME;
    $full    = $title === APP_NAME ? APP_NAME . ': ' . APP_TAGLINE : $title . ' | ' . APP_NAME;
    $desc    = $o['description'] ?? 'Zelmoriq is a notes-sharing portal where HOD-verified teachers share class notes and students find, rate and download them.';
    $u       = user();
    $indexable = ($layout === 'public');
    $https   = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $canon   = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . strtok((string)($_SERVER['REQUEST_URI'] ?? '/'), '?');
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($full) ?></title>
  <meta name="description" content="<?= e($desc) ?>">
  <meta name="theme-color" content="#101935">
<?php if ($indexable): ?>
  <meta name="robots" content="index, follow, max-image-preview:large">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= e(APP_NAME) ?>">
  <meta property="og:title" content="<?= e($full) ?>">
  <meta property="og:description" content="<?= e($desc) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e($ogImage) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($full) ?>">
  <meta name="twitter:description" content="<?= e($desc) ?>">
  <meta name="twitter:image" content="<?= e($ogImage) ?>">
<?php if (!empty($o['schema'])): ?>
  <script type="application/ld+json"><?= json_encode($o['schema'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endif; ?>
<?php else: ?>
  <meta name="robots" content="noindex, nofollow">
<?php endif; ?>
  <meta name="robots" content="<?= $indexable ? 'index, follow' : 'noindex, nofollow' ?>">
<?php if ($indexable): ?>
  <link rel="canonical" href="<?= e($canon) ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= e(APP_NAME) ?>">
  <meta property="og:title" content="<?= e($full) ?>">
  <meta property="og:description" content="<?= e($desc) ?>">
  <meta property="og:url" content="<?= e($canon) ?>">
  <meta name="twitter:card" content="summary">
<?php endif; ?>
<?= $o['head'] ?? '' ?>
  <link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/favicon.svg')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&amp;family=Figtree:wght@400;500;600;700&amp;display=swap">
  <link rel="stylesheet" href="<?= e(asset('css/zelmoriq.css')) ?>">
</head>
<body class="layout-<?= e($layout) ?>">
<a class="skip" href="#main">Skip to content</a>
<?php
    if ($layout === 'public') {
        ?>
<header class="site-header">
  <div class="wrap site-header-in">
    <a class="brand" href="<?= e(url('index.php')) ?>"><?= logo_mark(34) ?><span>Zelmoriq</span></a>
    <nav class="site-nav" id="site-nav" aria-label="Main">
      <a href="<?= e(url('index.php#how')) ?>">How it works</a>
      <a href="<?= e(url('index.php#notes')) ?>">Latest notes</a>
      <a href="<?= e(url('index.php#about')) ?>">About</a>
      <a href="<?= e(url('agile.php')) ?>">Roadmap</a>
      <a href="<?= e(url('index.php#contact')) ?>">Contact</a>
    </nav>
    <div class="site-actions">
      <?php if ($u): ?>
        <a class="btn primary" href="<?= e(url(role_home($u['role']))) ?>">Open dashboard</a>
      <?php else: ?>
        <a class="btn ghost" href="<?= e(url('login.php')) ?>">Log in</a>
        <a class="btn primary" href="<?= e(url('register.php')) ?>">Sign up</a>
      <?php endif; ?>
    </div>
    <button class="nav-toggle" type="button" data-nav-toggle aria-label="Menu" aria-controls="site-nav"><?= icon('menu', 22) ?></button>
  </div>
</header>
<main id="main">
  <div class="wrap flash-wrap"><?php render_flash(); ?></div>
<?php
    } elseif ($layout === 'auth') {
        ?>
<main id="main" class="auth">
  <aside class="auth-side">
    <a class="brand light" href="<?= e(url('index.php')) ?>"><?= logo_mark(38) ?><span>Zelmoriq</span></a>
    <div class="auth-pitch">
      <h2><?= e($o['side_title'] ?? 'Notes from the people who teach you.') ?></h2>
      <p><?= e($o['side_text'] ?? 'Teachers are verified by their Head of Department before they can share, so what you download is what your department stands behind.') ?></p>
      <div class="auth-sheets" aria-hidden="true">
        <div class="mini-sheet s1"><b>Data Structures</b><i></i><i></i><i class="short"></i></div>
        <div class="mini-sheet s2"><b>Operating Systems</b><i></i><i></i><i class="short"></i></div>
      </div>
    </div>
    <small class="auth-foot">&copy; <?= date('Y') ?> Zelmoriq</small>
  </aside>
  <section class="auth-main">
    <div class="auth-card <?= e($o['card_class'] ?? '') ?>">
      <?php render_flash(); ?>
<?php
    } else { // app
        $role   = $u ? $u['role'] : 'student';
        $groups = $u ? nav_items($role) : [];
        $active = $o['active'] ?? '';
        ?>
<div class="app">
  <aside class="sidebar" id="sidebar" aria-label="Sidebar">
    <a class="brand light" href="<?= e(url(role_home($role))) ?>"><?= logo_mark(32) ?><span>Zelmoriq</span></a>
    <?php foreach ($groups as $g): ?>
      <div class="nav-group">
        <p class="nav-title"><?= e($g[0]) ?></p>
        <?php foreach ($g[1] as $item): ?>
          <a class="nav-link<?= $active === $item[0] ? ' active' : '' ?>" href="<?= e(url($item[2])) ?>"<?= $active === $item[0] ? ' aria-current="page"' : '' ?>>
            <?= icon($item[3], 18) ?><span><?= e($item[1]) ?></span>
            <?php if (!empty($item[4])): ?><em class="nav-count"><?= (int)$item[4] ?></em><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
    <div class="side-user">
      <?php if ($u): ?>
        <?= avatar($u, 'sm') ?>
        <div><strong><?= e($u['name']) ?></strong><small><?= e(role_label($u['role'])) ?></small></div>
      <?php endif; ?>
      <a class="side-logout" href="<?= e(url('logout.php')) ?>" title="Log out" aria-label="Log out"><?= icon('logout', 18) ?></a>
    </div>
  </aside>
  <div class="scrim" data-nav-toggle></div>
  <div class="app-main">
    <header class="app-top">
      <button class="nav-toggle" type="button" data-nav-toggle aria-label="Menu" aria-controls="sidebar"><?= icon('menu', 22) ?></button>
      <a class="app-site-link" href="<?= e(url('index.php')) ?>"><?= icon('external', 16) ?>Public site</a>
    </header>
    <main id="main" class="app-content">
      <?php render_flash(); ?>
      <?php if (!empty($o['heading'])): ?>
        <div class="page-head">
          <div>
            <h1><?= e($o['heading']) ?></h1>
            <?php if (!empty($o['sub'])): ?><p><?= e($o['sub']) ?></p><?php endif; ?>
          </div>
          <?php if (!empty($o['actions'])): ?><div class="page-actions"><?= $o['actions'] ?></div><?php endif; ?>
        </div>
      <?php endif; ?>
<?php
    }
}

function render_footer(): void
{
    $layout = $GLOBALS['zq_layout'] ?? 'public';
    if ($layout === 'public') {
        ?>
</main>
<footer class="site-footer">
  <div class="wrap site-footer-in">
    <div>
      <a class="brand light" href="<?= e(url('index.php')) ?>"><?= logo_mark(30) ?><span>Zelmoriq</span></a>
      <p><?= e(APP_TAGLINE) ?>. Built by students, for students.</p>
    </div>
    <nav aria-label="Footer">
      <a href="<?= e(url('login.php')) ?>">Log in</a>
      <a href="<?= e(url('register.php')) ?>">Create account</a>
      <a href="<?= e(url('agile.php')) ?>">Roadmap</a>
      <a href="<?= e(url('index.php#contact')) ?>">Contact</a>
    </nav>
    <small>&copy; <?= date('Y') ?> Zelmoriq. All rights reserved.</small>
  </div>
</footer>
<?php
    } elseif ($layout === 'auth') {
        ?>
    </div>
  </section>
</main>
<?php
    } else {
        ?>
    </main>
  </div>
</div>
<?php
    }
    ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
<?php
}
