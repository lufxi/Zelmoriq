<?php
/**
 * Zelmoriq bootstrap: configuration, session, database, security and auth helpers.
 * Every page starts with: require_once __DIR__ . '/includes/bootstrap.php';
 */
if (defined('ZQ_BOOTED')) { return; }
define('ZQ_BOOTED', true);

define('APP_ROOT',   str_replace('\\', '/', dirname(__DIR__)));
define('UPLOAD_DIR', APP_ROOT . '/uploads');
if (!is_file(APP_ROOT . '/config/config.php')) {
    http_response_code(500);
    exit('Zelmoriq is not configured yet. Copy config/config.example.php to config/config.php and enter your database details.');
}
require_once APP_ROOT . '/config/config.php';

date_default_timezone_set(APP_TZ);
error_reporting(E_ALL);
ini_set('display_errors', APP_ENV === 'local' ? '1' : '0');
ini_set('log_errors', '1');

/* ---------- Session & headers ---------- */
if (session_status() === PHP_SESSION_NONE) {
    $zqSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $zqSecure,
    ]);
    session_name('zelmoriq_sid');
    session_start();
}
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
}

/* ---------- URLs ---------- */
function zq_detect_base(): string
{
    if (BASE_URL_OVERRIDE !== '') {
        return rtrim(BASE_URL_OVERRIDE, '/');
    }
    $root = realpath(APP_ROOT);
    $doc  = !empty($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    if ($root !== false && $doc !== false) {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $doc  = rtrim(str_replace('\\', '/', $doc), '/');
        if (stripos($root, $doc) === 0) {
            return substr($root, strlen($doc));
        }
    }
    // Fallback: work it out from the script that is running right now
    $script = !empty($_SERVER['SCRIPT_FILENAME']) ? realpath($_SERVER['SCRIPT_FILENAME']) : false;
    if ($script !== false && $root !== false) {
        $rel  = substr(str_replace('\\', '/', $script), strlen(rtrim(str_replace('\\', '/', $root), '/')));
        $name = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($rel !== '' && substr($name, -strlen($rel)) === $rel) {
            return substr($name, 0, -strlen($rel));
        }
    }
    return '';
}
define('BASE_URL', zq_detect_base());

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = APP_ROOT . '/' . ltrim($path, '/');
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return url($path) . $v;
}

function absolute_url(string $path): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($https ? 'https' : 'http') . '://' . $host . url($path);
}

function redirect(string $to): void
{
    if (!preg_match('#^(https?:)?//#', $to) && ($to === '' || $to[0] !== '/')) {
        $to = url($to);
    }
    header('Location: ' . $to);
    exit;
}

/* ---------- Request helpers ---------- */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function is_post(): bool             { return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }
function post_str(string $k): string { return trim((string)($_POST[$k] ?? '')); }
function post_int(string $k): int    { return (int)($_POST[$k] ?? 0); }
function get_str(string $k): string  { return trim((string)($_GET[$k] ?? '')); }
function get_int(string $k): int     { return (int)($_GET[$k] ?? 0); }
/** Escaped previous POST value, for re-filling forms after a validation error. */
function old(string $k, string $default = ''): string
{
    return e($_POST[$k] ?? $default);
}
function client_ip(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

/* ---------- Flash messages ---------- */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['t' => $type, 'm' => $message];
}
function flash_all(): array
{
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}
/** Call at the top of every POST handler. */
function csrf_check(): void
{
    $sent = (string)($_POST['_csrf'] ?? '');
    $real = (string)($_SESSION['csrf'] ?? '');
    if ($sent === '' || $real === '' || !hash_equals($real, $sent)) {
        flash('error', 'Your session expired or the form was invalid. Please try again.');
        redirect($_SERVER['REQUEST_URI'] ?? url());
    }
}

/* ---------- Database ---------- */
function render_fatal(string $title, string $detail): void
{
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><title>' . e(APP_NAME) . '</title>'
       . '<body style="font-family:system-ui,sans-serif;max-width:560px;margin:12vh auto;padding:0 20px;color:#101935">'
       . '<h1 style="font-size:24px">' . e($title) . '</h1><p style="line-height:1.6">' . $detail . '</p></body>';
    exit;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            // Keep MySQL's clock and PHP's clock on the same timezone
            $offset = (new DateTime('now', new DateTimeZone(APP_TZ)))->format('P');
            $pdo->exec("SET time_zone = '" . $offset . "'");
        } catch (PDOException $ex) {
            error_log('Zelmoriq DB error: ' . $ex->getMessage());
            $hint = 'Zelmoriq could not connect to the database. Make sure MySQL is running, that you imported '
                  . '<code>database/zelmoriq.sql</code>, and that <code>config/config.php</code> has the right credentials.';
            if (APP_ENV === 'local') {
                $hint .= '<br><br><small>' . e($ex->getMessage()) . '</small>';
            }
            render_fatal('Database connection problem', $hint);
        }
    }
    return $pdo;
}
function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}
function db_one(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}
function db_val(string $sql, array $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $v = $st->fetchColumn();
    return $v === false ? null : $v;
}
function db_exec(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}
function db_id(): int
{
    return (int)db()->lastInsertId();
}
/** True when a throwable is a foreign-key / duplicate-key violation. */
function is_constraint_error(Throwable $t): bool
{
    return $t instanceof PDOException && (string)$t->getCode() === '23000';
}

/* ---------- Auth ---------- */
const ROLE_LABELS = [
    'manager' => 'Manager',
    'hod'     => 'Head of Department',
    'teacher' => 'Teacher',
    'student' => 'Student',
];
function role_label(string $role): string
{
    return ROLE_LABELS[$role] ?? ucfirst($role);
}
function role_home(string $role): string
{
    switch ($role) {
        case 'manager': return 'manager/dashboard.php';
        case 'hod':     return 'hod/dashboard.php';
        case 'teacher': return 'teacher/dashboard.php';
        case 'student': return 'student/dashboard.php';
    }
    return 'login.php';
}

/** The logged-in user row, or null. Pass true after changing the user's data. */
function user(bool $refresh = false): ?array
{
    static $cache = false;
    if ($refresh) { $cache = false; }
    if ($cache !== false) { return $cache; }
    $cache = null;
    if (!empty($_SESSION['uid'])) {
        $row = db_one('SELECT * FROM users WHERE id = ?', [(int)$_SESSION['uid']]);
        if ($row && $row['status'] === 'active') {
            $cache = $row;
        } else {
            unset($_SESSION['uid']);
        }
    }
    return $cache;
}
function login_user(array $row): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$row['id'];
    user(true);
}
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
function require_login(): array
{
    $u = user();
    if (!$u) {
        flash('info', 'Please log in to continue.');
        redirect('login.php');
    }
    return $u;
}
/** require_role('teacher', 'hod') -> returns the user row or redirects away. */
function require_role(string ...$roles): array
{
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        flash('error', 'You do not have access to that page.');
        redirect(role_home($u['role']));
    }
    return $u;
}

function password_error(string $pw): ?string
{
    if (strlen($pw) < 8 || !preg_match('/[a-z]/', $pw) || !preg_match('/[A-Z]/', $pw)
        || !preg_match('/\d/', $pw) || !preg_match('/[^A-Za-z0-9]/', $pw)) {
        return 'Password needs at least 8 characters with an uppercase letter, a lowercase letter, a number and a symbol.';
    }
    return null;
}
function phone_ok(string $p): bool
{
    return (bool)preg_match('/^\d{10}$/', $p);
}

/* Login throttling: 5 failed attempts per e-mail + IP in 10 minutes */
function login_blocked(string $email): bool
{
    $n = (int)db_val(
        'SELECT COUNT(*) FROM login_attempts WHERE email = ? AND ip = ? AND attempted_at > (NOW() - INTERVAL 10 MINUTE)',
        [$email, client_ip()]
    );
    return $n >= 5;
}
function login_fail(string $email): void
{
    db_exec('INSERT INTO login_attempts (email, ip) VALUES (?, ?)', [$email, client_ip()]);
    db_exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
}
function login_clear(string $email): void
{
    db_exec('DELETE FROM login_attempts WHERE email = ? AND ip = ?', [$email, client_ip()]);
}

/* ---------- Profile lookups ---------- */
function hod_dept_id(int $uid): ?int
{
    $v = db_val('SELECT department_id FROM hod_profiles WHERE user_id = ?', [$uid]);
    return $v === null ? null : (int)$v;
}
function teacher_profile(int $uid): ?array
{
    return db_one(
        'SELECT tp.*, d.name AS department_name
           FROM teacher_profiles tp JOIN departments d ON d.id = tp.department_id
          WHERE tp.user_id = ?',
        [$uid]
    );
}
function student_profile(int $uid): ?array
{
    return db_one(
        'SELECT sp.*, c.name AS course_name, c.department_id, d.name AS department_name
           FROM student_profiles sp
           JOIN courses c ON c.id = sp.course_id
           JOIN departments d ON d.id = c.department_id
          WHERE sp.user_id = ?',
        [$uid]
    );
}
/** Approve / reject / reset a teacher. $deptLimit restricts a HOD to their own department. */
function review_teacher(int $teacherId, string $status, int $reviewerId, ?int $deptLimit = null): int
{
    if (!in_array($status, ['approved', 'rejected', 'pending'], true)) { return 0; }
    $sql    = 'UPDATE teacher_profiles SET approval = ?, reviewed_by = ?, reviewed_at = NOW() WHERE user_id = ?';
    $params = [$status, $reviewerId, $teacherId];
    if ($deptLimit !== null) {
        $sql .= ' AND department_id = ?';
        $params[] = $deptLimit;
    }
    return db_exec($sql, $params);
}

/* ---------- Files ---------- */
const IMG_TYPES  = ['jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'webp' => ['image/webp']];
const DOC_TYPES  = ['pdf' => ['application/pdf'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png']];
const NOTE_TYPES = ['pdf' => ['application/pdf']];

/**
 * Validate and store an uploaded file under uploads/<subdir>/ with a random name.
 * Returns ['ok'=>true,'file'=>..., 'size'=>..., 'orig'=>...] or ['ok'=>false,'error'=>..., 'none'=>bool].
 */
function upload_save(array $f, string $subdir, array $allowed, int $maxBytes): array
{
    if (!isset($f['error']) || is_array($f['error'])) {
        return ['ok' => false, 'none' => false, 'error' => 'The upload was not valid.'];
    }
    if ($f['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'none' => true, 'error' => 'Please choose a file.'];
    }
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'none' => false, 'error' => 'That file is larger than the server allows.'];
    }
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'none' => false, 'error' => 'The upload failed. Please try again.'];
    }
    if ((int)$f['size'] > $maxBytes) {
        return ['ok' => false, 'none' => false, 'error' => 'The file is too large. Maximum size is ' . human_size($maxBytes) . '.'];
    }
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        return ['ok' => false, 'none' => false, 'error' => 'Unsupported file type. Allowed: ' . strtoupper(implode(', ', array_keys($allowed))) . '.'];
    }
    if (!is_uploaded_file($f['tmp_name'])) {
        return ['ok' => false, 'none' => false, 'error' => 'The upload was not valid.'];
    }
    if (class_exists('finfo')) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!in_array($mime, $allowed[$ext], true)) {
            return ['ok' => false, 'none' => false, 'error' => 'The file content does not match its extension.'];
        }
    }
    $dir = UPLOAD_DIR . '/' . $subdir;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return ['ok' => false, 'none' => false, 'error' => 'The upload folder is not writable.'];
    }
    if ($ext === 'jpeg') { $ext = 'jpg'; }
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        return ['ok' => false, 'none' => false, 'error' => 'Could not save the file. Check folder permissions.'];
    }
    return [
        'ok'   => true,
        'file' => $name,
        'size' => (int)$f['size'],
        'orig' => mb_substr(basename((string)$f['name']), 0, 200),
    ];
}
function upload_delete(string $subdir, ?string $file): void
{
    if ($file && preg_match('/^[a-f0-9]{24}\.[a-z]{3,4}$/', $file)) {
        $path = UPLOAD_DIR . '/' . $subdir . '/' . $file;
        if (is_file($path)) { @unlink($path); }
    }
}
/** Remove a user and every file that belonged to them. */
function delete_user_completely(int $uid): void
{
    $u     = db_one('SELECT photo FROM users WHERE id = ?', [$uid]);
    $notes = db_all('SELECT file_name FROM notes WHERE teacher_id = ?', [$uid]);
    $cert  = db_val('SELECT certificate FROM teacher_profiles WHERE user_id = ?', [$uid]);
    db_exec('DELETE FROM users WHERE id = ?', [$uid]);
    if ($u)    { upload_delete('photos', $u['photo']); }
    if ($cert) { upload_delete('certificates', $cert); }
    foreach ($notes as $n) { upload_delete('notes', $n['file_name']); }
}

function mail_send(string $to, string $subject, string $body): bool
{
    if (!MAIL_ENABLED) { return false; }
    $headers = 'From: ' . MAIL_FROM . "\r\nContent-Type: text/plain; charset=UTF-8";
    return @mail($to, $subject, $body, $headers);
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/layout.php';
