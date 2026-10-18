<?php
/**
 * View helpers: formatting, icons, avatars, badges, rating stars, pagination, note cards.
 */

function human_size(int $bytes): string
{
    if ($bytes >= 1048576) { return round($bytes / 1048576, 1) . ' MB'; }
    if ($bytes >= 1024)    { return round($bytes / 1024) . ' KB'; }
    return $bytes . ' B';
}

function fmt_date($ts, bool $withTime = false): string
{
    if (!$ts) { return ''; }
    $t = is_numeric($ts) ? (int)$ts : strtotime((string)$ts);
    return $t ? date($withTime ? 'd M Y, h:i A' : 'd M Y', $t) : '';
}

/** Stable colour for a department (used for the tab on each note sheet). */
function dept_hue(int $id): int
{
    $hues = [236, 8, 164, 36, 292, 196, 336, 96];
    return $hues[$id % count($hues)];
}

function icon(string $name, int $size = 18): string
{
    static $paths = [
        'home'     => '<path d="M3 11l9-8 9 8v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'file'     => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
        'upload'   => '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/>',
        'download' => '<path d="M12 4v12M7 11l5 5 5-5"/><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5a6.5 6.5 0 0 1 3.5 5.5"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'lock'     => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'logout'   => '<path d="M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3"/><path d="M16 8l4 4-4 4M20 12H9"/>',
        'check'    => '<path d="M5 12.5l4.5 4.5L19 7"/>',
        'x'        => '<path d="M6 6l12 12M18 6L6 18"/>',
        'eye'      => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'trash'    => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/>',
        'edit'     => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13.5 6.5l4 4"/>',
        'building' => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M9 15h2M13 15h2"/>',
        'layers'   => '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'search'   => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'shield'   => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'award'    => '<circle cx="12" cy="9" r="6"/><path d="M8.5 14L7 21l5-3 5 3-1.5-7"/>',
        'grid'     => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
        'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'book'     => '<path d="M5 4h9a4 4 0 0 1 4 4v12H9a4 4 0 0 1-4-4z"/><path d="M5 16a4 4 0 0 1 4-4h9"/>',
        'cap'      => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-4.5"/>',
        'list'     => '<path d="M4 6h16M4 12h16M4 18h10"/>',
        'inbox'    => '<path d="M4 13l2-8h12l2 8v6H4z"/><path d="M4 13h5l1 2h4l1-2h5"/>',
        'alert'    => '<path d="M12 3l10 18H2z"/><path d="M12 10v5M12 18.5v.1"/>',
        'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5v.1"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
        'sheet'    => '<path d="M6 3h9l4 4v14H6z"/><path d="M15 3v4h4"/>',
    ];
    $p = $paths[$name] ?? $paths['info'];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
         . 'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p . '</svg>';
}

function logo_mark(int $size = 34): string
{
    return '<svg class="logo-mark" width="' . $size . '" height="' . $size . '" viewBox="0 0 32 32" aria-hidden="true">'
         . '<path d="M0 9a9 9 0 0 1 9-9h14l9 9v14a9 9 0 0 1-9 9H9a9 9 0 0 1-9-9z" fill="#101935"/>'
         . '<path d="M23 0v9h9z" fill="#FFD93D"/>'
         . '<path d="M9 11h12l-9 11h11" fill="none" stroke="#fff" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>'
         . '</svg>';
}

/** $u needs 'name' and optionally 'photo' (a file in uploads/photos). */
function avatar(array $u, string $size = ''): string
{
    $cls  = 'avatar' . ($size !== '' ? ' ' . $size : '');
    $name = trim((string)($u['name'] ?? '?'));
    if (!empty($u['photo'])) {
        return '<img class="' . $cls . '" src="' . e(url('uploads/photos/' . $u['photo'])) . '" alt="" loading="lazy">';
    }
    $parts = preg_split('/\s+/', $name) ?: ['?'];
    $ini   = mb_strtoupper(mb_substr($parts[0], 0, 1));
    if (count($parts) > 1) { $ini .= mb_strtoupper(mb_substr(end($parts), 0, 1)); }
    $hue = abs(crc32($name)) % 360;
    return '<span class="' . $cls . '" style="--h:' . $hue . '" aria-hidden="true">' . e($ini) . '</span>';
}

function badge(string $text, string $tone = ''): string
{
    return '<span class="badge' . ($tone !== '' ? ' ' . e($tone) : '') . '">' . e($text) . '</span>';
}
function approval_badge(string $status): string
{
    switch ($status) {
        case 'approved': return badge('Approved', 'ok');
        case 'rejected': return badge('Rejected', 'bad');
    }
    return badge('Pending review', 'warn');
}

function stars($avg, int $count = 0): string
{
    if ($avg === null || $count < 1) {
        return '<span class="muted small">No ratings yet</span>';
    }
    $avg = (float)$avg;
    $pct = max(0, min(100, (int)round($avg / 5 * 100)));
    $txt = number_format($avg, 1);
    return '<span class="stars" style="--p:' . $pct . '%" title="' . e($txt) . ' out of 5"></span>'
         . '<span class="rating-num">' . e($txt) . '</span><span class="muted small">(' . $count . ')</span>';
}

function pager(int $total, int $per, int $page): array
{
    $pages = max(1, (int)ceil($total / max(1, $per)));
    $page  = min(max(1, $page), $pages);
    return ['total' => $total, 'per' => $per, 'page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $per];
}
function pager_html(array $pg, array $query = []): string
{
    if ($pg['pages'] <= 1) { return ''; }
    unset($query['page']);
    $link = function (int $p, string $label, string $cls = '') use ($query): string {
        return '<a class="' . $cls . '" href="?' . e(http_build_query(array_merge($query, ['page' => $p]))) . '">' . $label . '</a>';
    };
    $out = '<nav class="pager" aria-label="Pages">';
    if ($pg['page'] > 1) { $out .= $link($pg['page'] - 1, 'Previous'); }
    for ($i = 1; $i <= $pg['pages']; $i++) {
        if ($i === 1 || $i === $pg['pages'] || abs($i - $pg['page']) <= 2) {
            $out .= $i === $pg['page']
                ? '<span class="current" aria-current="page">' . $i . '</span>'
                : $link($i, (string)$i);
        } elseif (abs($i - $pg['page']) === 3) {
            $out .= '<span class="gap">&hellip;</span>';
        }
    }
    if ($pg['page'] < $pg['pages']) { $out .= $link($pg['page'] + 1, 'Next'); }
    return $out . '</nav>';
}

function select_options(array $rows, string $valKey, string $labelKey, $selected = null, string $placeholder = ''): string
{
    $out = $placeholder !== '' ? '<option value="">' . e($placeholder) . '</option>' : '';
    foreach ($rows as $r) {
        $sel  = ($selected !== null && (string)$selected === (string)$r[$valKey]) ? ' selected' : '';
        $out .= '<option value="' . e($r[$valKey]) . '"' . $sel . '>' . e($r[$labelKey]) . '</option>';
    }
    return $out;
}
/** Courses grouped by department. Rows need id, name, department_name. */
function course_options(array $courses, $selected = null, string $placeholder = 'Select your course'): string
{
    $out = '<option value="">' . e($placeholder) . '</option>';
    $current = null;
    foreach ($courses as $c) {
        if ($c['department_name'] !== $current) {
            if ($current !== null) { $out .= '</optgroup>'; }
            $out .= '<optgroup label="' . e($c['department_name']) . '">';
            $current = $c['department_name'];
        }
        $sel  = ($selected !== null && (string)$selected === (string)$c['id']) ? ' selected' : '';
        $out .= '<option value="' . (int)$c['id'] . '"' . $sel . '>' . e($c['name']) . '</option>';
    }
    return $out . ($current !== null ? '</optgroup>' : '');
}
function semester_options($selected = null, string $placeholder = 'Select semester'): string
{
    $out = '<option value="">' . e($placeholder) . '</option>';
    for ($i = 1; $i <= 8; $i++) {
        $sel  = ((string)$selected === (string)$i) ? ' selected' : '';
        $out .= '<option value="' . $i . '"' . $sel . '>Semester ' . $i . '</option>';
    }
    return $out;
}

function empty_state(string $title, string $text = '', string $cta = ''): string
{
    return '<div class="empty"><div class="empty-art">' . icon('sheet', 28) . '</div>'
         . '<h3>' . e($title) . '</h3>' . ($text !== '' ? '<p>' . e($text) . '</p>' : '') . $cta . '</div>';
}

/**
 * A note rendered as a paper sheet.
 * $n: note row + dept_name, subject_name, teacher_name, teacher_photo, avg_rating, rating_count, my_rating
 * $o: rate (bool) show the star picker, actions (bool, default true) show preview/download
 */
function note_card(array $n, array $o = []): string
{
    $rate    = !empty($o['rate']);
    $actions = $o['actions'] ?? true;
    $id      = (int)$n['id'];
    $mine    = isset($n['my_rating']) ? (int)$n['my_rating'] : 0;
    ob_start(); ?>
    <article class="sheet" style="--h:<?= dept_hue((int)$n['department_id']) ?>">
      <span class="sheet-tab"><?= e($n['dept_name']) ?></span>
      <h3 class="sheet-title"><?= e($n['title']) ?></h3>
      <p class="sheet-topic"><?= e($n['topic']) ?></p>
      <div class="chips">
        <span class="chip"><?= e($n['subject_name']) ?></span>
        <span class="chip">Semester <?= (int)$n['semester'] ?></span>
      </div>
      <div class="sheet-by">
        <?= avatar(['name' => $n['teacher_name'], 'photo' => $n['teacher_photo'] ?? null], 'sm') ?>
        <span><?= e($n['teacher_name']) ?><small><?= e(fmt_date($n['created_at'])) ?></small></span>
      </div>
      <div class="sheet-rating"><?= stars($n['avg_rating'] ?? null, (int)($n['rating_count'] ?? 0)) ?></div>
      <?php if ($rate): ?>
        <form method="post" action="<?= e(url('student/rate.php')) ?>" class="rate-form">
          <?= csrf_field() ?>
          <input type="hidden" name="note_id" value="<?= $id ?>">
          <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
          <span class="rate-label"><?= $mine ? 'Your rating' : 'Rate this note' ?></span>
          <div class="rate" role="radiogroup" aria-label="Rate this note">
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <input type="radio" name="rating" id="r<?= $id ?>-<?= $i ?>" value="<?= $i ?>"<?= $mine === $i ? ' checked' : '' ?>>
              <label for="r<?= $id ?>-<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">&#9733;</label>
            <?php endfor; ?>
          </div>
          <noscript><button class="btn sm" type="submit">Save</button></noscript>
        </form>
      <?php endif; ?>
      <?php if ($actions): ?>
        <div class="sheet-actions">
          <a class="btn sm" href="<?= e(url('download.php?id=' . $id . '&inline=1')) ?>" target="_blank" rel="noopener"><?= icon('eye', 16) ?>Preview</a>
          <a class="btn sm primary" href="<?= e(url('download.php?id=' . $id)) ?>"><?= icon('download', 16) ?>Download</a>
          <span class="sheet-dl" title="Downloads"><?= (int)($n['downloads'] ?? 0) ?> downloads</span>
        </div>
      <?php endif; ?>
    </article>
    <?php
    return (string)ob_get_clean();
}
