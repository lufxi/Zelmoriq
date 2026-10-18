<?php
// Served as /sitemap.xml through .htaccess. Lists the public pages only.
header('Content-Type: application/xml; charset=utf-8');
$https  = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$origin = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$base   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$pages  = [['', 'index.php', '1.0', 'weekly'], ['register.php', 'register.php', '0.8', 'monthly'], ['agile.php', 'agile.php', '0.5', 'monthly']];
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as $p) {
    echo "  <url>\n    <loc>" . htmlspecialchars($origin . $base . '/' . $p[0], ENT_XML1) . "</loc>\n"
       . '    <lastmod>' . date('Y-m-d', filemtime(__DIR__ . '/' . $p[1])) . "</lastmod>\n"
       . '    <changefreq>' . $p[3] . "</changefreq>\n    <priority>" . $p[2] . "</priority>\n  </url>\n";
}
echo "</urlset>\n";
