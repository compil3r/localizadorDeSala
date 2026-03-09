<?php
/**
 * Retorna a variável JavaScript APP_BASE para uso no frontend.
 * Defina KIOSK_BASE_PATH no servidor (ex: /salas) quando o projeto estiver em subpasta.
 */
header('Content-Type: application/javascript; charset=utf-8');
$config = require __DIR__ . '/config.php';
$base = $config['base_path'] ?? '';
echo 'window.APP_BASE = ' . json_encode($base) . ';';
