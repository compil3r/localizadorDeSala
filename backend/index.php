<?php
require_once __DIR__ . '/config.php';
$base = defined('APP_BASE_PATH') ? APP_BASE_PATH : '';
header('Location: ' . $base . '/backend/kiosk/');
exit;
