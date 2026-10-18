<?php
require_once __DIR__ . '/includes/bootstrap.php';
$u = require_login();
redirect(role_home($u['role']));
