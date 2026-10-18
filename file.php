<?php
/** Serves a teacher's verification certificate to the teacher, their HOD, or a manager only. */
require_once __DIR__ . '/includes/bootstrap.php';
$u  = require_login();
$tid = get_int('cert');
$tp = db_one('SELECT user_id, department_id, certificate FROM teacher_profiles WHERE user_id = ?', [$tid]);
$allowed = false;
if ($tp) {
    if ($u['role'] === 'manager' || (int)$u['id'] === (int)$tp['user_id']) { $allowed = true; }
    elseif ($u['role'] === 'hod') { $allowed = hod_dept_id((int)$u['id']) === (int)$tp['department_id']; }
}
$path = ($tp && $tp['certificate']) ? UPLOAD_DIR . '/certificates/' . basename($tp['certificate']) : '';
if (!$allowed || !is_file($path)) {
    http_response_code(404);
    exit('File not available.');
}
$types = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'];
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
while (ob_get_level()) { ob_end_clean(); }
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: inline; filename="certificate.' . $ext . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=0');
readfile($path);
exit;
