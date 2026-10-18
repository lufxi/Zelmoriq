<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role('student');
if (!is_post()) { redirect('student/notes.php'); }
csrf_check();

$back = (string)($_POST['back'] ?? '');
$okBack = BASE_URL . '/student/';
$dest = (strpos($back, $okBack) === 0 && strpos($back, "\n") === false) ? $back : url('student/notes.php');

$noteId = post_int('note_id'); $rating = post_int('rating');
if ($rating < 1 || $rating > 5 || !db_val('SELECT id FROM notes WHERE id = ?', [$noteId])) {
    flash('error', 'That rating could not be saved.');
} else {
    db_exec('INSERT INTO ratings (note_id, student_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), created_at = NOW()', [$noteId, $u['id'], $rating]);
    flash('success', 'Thanks, your rating is saved.');
}
redirect($dest);
