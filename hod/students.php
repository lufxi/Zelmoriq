<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role('hod');
$dept = hod_dept_id((int)$u['id']);
if (!$dept) { flash('error', 'Your account is not linked to a department.'); redirect('hod/dashboard.php'); }
$rows = db_all(
    'SELECT u.name, u.email, u.phone, u.photo, sp.roll_no, sp.semester, sp.college, c.name AS course_name, u.created_at
       FROM student_profiles sp JOIN users u ON u.id = sp.user_id JOIN courses c ON c.id = sp.course_id
      WHERE c.department_id = ? ORDER BY c.name, sp.semester, u.name', [$dept]);
$courses = db_all('SELECT name FROM courses WHERE department_id = ? ORDER BY name', [$dept]);
render_header(['title' => 'Students', 'layout' => 'app', 'active' => 'students', 'heading' => 'Students', 'sub' => count($rows) . ' in your department',
    'actions' => '<button class="btn" type="button" data-export-csv="#students" data-filename="students.csv">' . icon('download', 16) . 'Export CSV</button>']);
?>
<div class="card">
  <div class="toolbar">
    <div class="field grow"><label for="f">Search</label><input class="input" id="f" data-filter-table="#students" placeholder="Name, email or roll number"></div>
    <div class="field"><label for="fc">Course</label><select class="select" id="fc" data-filter-table="#students" data-col="1"><option value="">All</option><?php foreach ($courses as $c) { echo '<option value="' . e($c['name']) . '">' . e($c['name']) . '</option>'; } ?></select></div>
    <div class="field"><label for="fs">Semester</label><select class="select" id="fs" data-filter-table="#students" data-col="2"><option value="">All</option><?php for ($i = 1; $i <= 8; $i++) { echo '<option value="' . $i . '">' . $i . '</option>'; } ?></select></div>
  </div>
  <div class="table-wrap"><table class="table" id="students"><thead><tr><th>Student</th><th>Course</th><th>Semester</th><th>Roll no</th><th>Phone</th><th>College</th></tr></thead><tbody>
  <?php foreach ($rows as $s): ?>
    <tr><td><div class="who"><span data-nocsv><?= avatar($s, 'sm') ?></span><div><strong><?= e($s['name']) ?></strong><small><?= e($s['email']) ?></small></div></div></td>
      <td><?= e($s['course_name']) ?></td><td><?= (int)$s['semester'] ?></td><td><?= e($s['roll_no']) ?></td><td><?= e($s['phone']) ?></td><td><?= e($s['college']) ?></td></tr>
  <?php endforeach; ?>
    <tr data-empty<?= $rows ? ' hidden' : '' ?>><td colspan="6"><?= empty_state('No students found') ?></td></tr>
  </tbody></table></div>
</div>
<?php render_footer();
