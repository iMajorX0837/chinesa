<?php
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=300');

require_once __DIR__ . '/config.php';
require_once DASH . '/services/database.php';

$config = ['nome' => 'App', 'favicon' => ''];
$base_url = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

try {
    $result = $mysqli->query('SELECT nome, favicon, logo FROM config LIMIT 1');
    if ($result && ($row = $result->fetch_assoc())) {
        $config = array_merge($config, $row);
    }
} catch (Exception $e) {
    // manifest mínimo se o banco falhar
}

$icon = trim((string)($config['favicon'] ?: $config['logo'] ?? ''));
if ($icon !== '' && strpos($icon, 'http') !== 0) {
    if (strpos($icon, '/') !== 0) {
        $icon = '/uploads/' . $icon;
    }
    $icon = $base_url . $icon;
}
if ($icon === '') {
    $icon = $base_url . '/favicon.ico';
}

$name = trim((string)($config['nome'] ?? 'App'));
if ($name === '') {
    $name = 'App';
}

echo json_encode([
    'id' => $base_url . '/',
    'name' => $name,
    'short_name' => mb_substr($name, 0, 12),
    'description' => $name,
    'start_url' => '/?source=pwa',
    'scope' => '/',
    'display' => 'standalone',
    'orientation' => 'portrait',
    'background_color' => '#000000',
    'theme_color' => '#f5f7fb',
    'prefer_related_applications' => false,
    'icons' => [
        ['src' => $icon, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => $icon, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
