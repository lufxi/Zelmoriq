<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u  = require_role('student');
$sp = student_profile((int)$u['id']);

$q    = get_str('q');
$dept = isset($_GET['department']) ? get_int('department') : ($sp ? (int)$sp['department_id'] : 0);
$subj = get_int('subject');
$sem  = get_int('semester');
$sort = get_str('sort');
if (!in_array($sort, ['new', 'top', 'popular'], true)) { $sort = 'new'; }

$where = []; $params = [];
if ($q !== '')  { $where[] = '(n.title LIKE ? OR n.topic LIKE ? OR s.name LIKE ?)'; $like = '%' . $q . '%'; array_push($params, $like, $like, $like); }
if ($dept > 0)  { $where[] = 'n.department_id = ?'; $params[] = $dept; }
if ($subj > 0)  { $where[] = 'n.subject_id = ?';    $params[] = $subj; }
if ($sem > 0)   { $where[] = 'n.semester = ?';      $params[] = $sem; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$from = 'FROM notes n JOIN users t ON t.id = n.teacher_id JOIN departments d ON d.id = n.department_id JOIN subjects s ON s.id = n.subject_id ';

$total = (int)db_val('SELECT COUNT(*) ' . $from . $w, $params);
$pg    = pager($total, NOTES_PER_PAGE, get_int('page'));
$order = ['new' => 'n.created_at DESC, n.id DESC', 'top' => 'COALESCE(avg_rating, 0) DESC, rating_count DESC', 'popular' => 'n.downloads DESC, n.id DESC'][$sort];

$notes = db_all(
    'SELECT n.*, t.name AS teacher_name, t.photo AS teacher_photo, d.name AS dept_name, s.name AS subject_name,
            (SELECT ROUND(AVG(rating), 1) FROM ratings WHERE note_id = n.id) AS avg_rating,
            (SELECT COUNT(*) FROM ratings WHERE note_id = n.id) AS rating_count,
            (SELECT rating FROM ratings WHERE note_id = n.id AND student_id = ?) AS my_rating '
    . $from . $w . ' ORDER BY ' . $order . ' LIMIT ' . (int)$pg['per'] . ' OFFSET ' . (int)$pg['offset'],
    array_merge([$u['id']], $params)
);
$depts    = db_all('SELECT id, name FROM departments ORDER BY name');
$subjects = $dept > 0 ? db_all('SELECT id, name FROM subjects WHERE department_id = ? ORDER BY name', [$dept])
                      : db_all('SELECT id, name FROM subjects ORDER BY name');
$query = ['q' => $q, 'department' => $dept, 'subject' => $subj, 'semester' => $sem, 'sort' => $sort];

render_header(['title' => 'Browse notes', 'layout' => 'app', 'active' => 'notes', 'heading' => 'Browse notes',
    'sub' => $total . ' note' . ($total === 1 ? '' : 's') . ' found']);
?>
<form method="get" class="card" data-autosubmit style="margin-bottom:24px">
  <div class="toolbar">
    <div class="field grow"><label for="q">Search</label><input class="input" id="q" name="q" value="<?= e($q) ?>" placeholder="Title, topic or subject"></div>
    <div class="field"><label for="department">Department</label><select class="select" id="department" name="department"><?= select_options($depts, 'id', 'name', $dept, 'All departments') ?></select></div>
    <div class="field"><label for="subject">Subject</label><select class="select" id="subject" name="subject"><?= select_options($subjects, 'id', 'name', $subj, 'All subjects') ?></select></div>
    <div class="field"><label for="semester">Semester</label><select class="select" id="semester" name="semester"><?= semester_options($sem, 'All semesters') ?></select></div>
    <div class="field"><label for="sort">Sort by</label><select class="select" id="sort" name="sort">
      <option value="new"<?= $sort === 'new' ? ' selected' : '' ?>>Newest</option><option value="top"<?= $sort === 'top' ? ' selected' : '' ?>>Top rated</option><option value="popular"<?= $sort === 'popular' ? ' selected' : '' ?>>Most downloaded</option></select></div>
    <div class="btn-row"><button class="btn primary" type="submit"><?= icon('search', 16) ?>Search</button><a class="btn" href="<?= e(url('student/notes.php?department=0')) ?>">Clear</a></div>
  </div>
</form>
<?php if ($notes): ?>
  <div class="sheets-grid"><?php foreach ($notes as $n) { echo note_card($n, ['rate' => true]); } ?></div>
  <?= pager_html($pg, $query) ?>
<?php else: ?>
  <div class="card"><?= empty_state('No notes match', 'Try a different subject or semester, or clear the filters.', '<a class="btn primary" href="' . e(url('student/notes.php?department=0')) . '">Show everything</a>') ?></div>
<?php endif; ?>
<?php render_footer();
