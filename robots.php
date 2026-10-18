<?php
// Served as /robots.txt through .htaccess. Keeps private areas out of search results.
header('Content-Type: text/plain; charset=utf-8');
$https  = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$origin = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$base   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
echo "User-agent: *\nAllow: /\n";
foreach (['student/', 'teacher/', 'hod/', 'manager/', 'includes/', 'config/', 'database/', 'uploads/', 'docs/',
          'login.php', 'logout.php', 'setup.php', 'dashboard.php', 'profile.php', 'password.php', 'download.php',
          'file.php', 'contact.php', 'forgot_password.php', 'reset_password.php'] as $path) {
    echo 'Disallow: ' . $base . '/' . $path . "\n";
}
echo "\nSitemap: " . $origin . $base . "/sitemap.xml\n";
