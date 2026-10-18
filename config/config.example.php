<?php
/**
 * Zelmoriq configuration.
 * Edit the database settings below to match your XAMPP / hosting setup.
 */
define('APP_NAME',    'Zelmoriq');
define('APP_TAGLINE', 'Class notes you can trust');

// 'local'      -> shows PHP errors and prints password-reset links on screen (no mail server needed)
// 'production' -> hides errors, never prints reset links
define('APP_ENV', 'local');
define('APP_TZ',  'Asia/Kolkata');

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'zelmoriq');
define('DB_USER', 'root');
define('DB_PASS', '');

// Leave empty to auto-detect the folder (e.g. "/Zelmoriq").
// Set it manually (e.g. "/apps/zelmoriq") only if the site sits behind an alias or proxy.
define('BASE_URL_OVERRIDE', '');

// Password-reset e-mails. Keep false on a laptop without a mail server.
define('MAIL_ENABLED', false);
define('MAIL_FROM',    'no-reply@zelmoriq.local');

// Upload limits (MB). Also check upload_max_filesize / post_max_size in php.ini.
define('MAX_PHOTO_MB', 2);
define('MAX_CERT_MB',  5);
define('MAX_NOTE_MB',  20);

define('NOTES_PER_PAGE', 12);
