<?php
require_once __DIR__ . '/includes/bootstrap.php';
$u = require_login();
$note = db_one('SELECT id, title, file_name FROM notes WHERE id = ?', [get_int('id')]);
$path = $note ? UPLOAD_DIR . '/notes/' . basename($note['file_name']) : '';
if (!$note || !is_file($path)) {
    flash('error', 'That note could not be found.');
    redirect(role_home($u['role']));
}
$inline = isset($_GET['inline']);
if (!$inline && $u['role'] === 'student') {
    db_exec('UPDATE notes SET downloads = downloads + 1 WHERE id = ?', [$note['id']]);
}
$safe = trim(preg_replace('/[^A-Za-z0-9 _\-]/', '', $note['title'])) ?: 'note';
while (ob_get_level()) { ob_end_clean(); }
header('Content-Type: application/pdf');
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $safe . '.pdf"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=0');
readfile($path);
exit;
