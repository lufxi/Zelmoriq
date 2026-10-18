<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (!is_post()) { redirect('index.php#contact'); }
csrf_check();
if (post_str('website') !== '') { redirect('index.php'); } // honeypot: bots fill this in

$name = post_str('name'); $email = post_str('email'); $subject = post_str('subject'); $msg = post_str('message');
if ($name === '' || $subject === '' || $msg === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Please fill in every field with a valid email address.');
} elseif (mb_strlen($msg) > 4000) {
    flash('error', 'Your message is too long (4000 characters maximum).');
} else {
    db_exec('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)',
        [mb_substr($name, 0, 100), mb_substr($email, 0, 150), mb_substr($subject, 0, 200), $msg]);
    flash('success', 'Thanks, your message has been sent. We will reply by email.');
}
redirect('index.php#contact');
