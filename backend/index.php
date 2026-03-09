<?php
require_once __DIR__ . '/config.php';
$kioskBase = defined('APP_KIOSK_BASE') ? APP_KIOSK_BASE : '/kiosk';
header('Location: ' . $kioskBase . '/');
exit;
