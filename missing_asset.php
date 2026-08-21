<?php
$uri = $_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'] ?? '';
if (stripos($uri, 'missing_asset.php') !== false) {
    $uri = $_SERVER['REQUEST_URI'] ?? $uri;
}
$path = parse_url($uri, PHP_URL_PATH);
$relativePath = ltrim((string)$path, '/');

if ($relativePath === '' || strpos($relativePath, '..') !== false) {
    http_response_code(400);
    exit;
}

$localPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
$ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
$mimeMap = [
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'jfif' => 'image/jpeg',
    'gif' => 'image/gif',
    'svg' => 'image/svg+xml',
    'webp' => 'image/webp',
    'ico' => 'image/x-icon',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

if (is_file($localPath) && filesize($localPath) > 8) {
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=86400');
    readfile($localPath);
    exit;
}

$remoteBases = [
    'https://77qw7.com/',
    'https://panda99.vip/',
    'https://a89s.com/',
    'https://upload-sys-pics.bcbd123.com/',
    'https://upload-sys-pics.f-1-g-h.com/',
    'https://upload-us.bcbd123.com/',
    'https://upload-us.f-1-g-h.com/',
];

$remoteRelativePath = $relativePath;
if (strpos($relativePath, 'img_icons/') === 0) {
    $remoteRelativePath = str_replace('img_icons/', 'icons/', $relativePath);
}

function isValidImageContent($content, $ext) {
    if (!$content || strlen($content) < 8) return false;
    $sig = substr($content, 0, 12);
    $head = substr($content, 0, 2);
    if ($head === '<!' || $head === '<h' || $head === '<H' || $head === '<?' || stripos($sig, '<html') !== false || stripos($sig, '<!doc') !== false) {
        return $ext === 'svg' && (stripos($sig, '<svg') !== false || stripos($sig, '<?xml') !== false);
    }
    switch ($ext) {
        case 'png':  return substr($content, 0, 8) === "\x89PNG\r\n\x1a\n";
        case 'jpg':
        case 'jpeg':
        case 'jfif': return substr($content, 0, 3) === "\xFF\xD8\xFF";
        case 'gif':  return substr($content, 0, 6) === 'GIF87a' || substr($content, 0, 6) === 'GIF89a';
        case 'webp': return substr($content, 0, 4) === 'RIFF' && substr($content, 8, 4) === 'WEBP';
        case 'ico':  return substr($content, 0, 4) === "\x00\x00\x01\x00";
        case 'svg':  return stripos($sig, '<svg') !== false || stripos($sig, '<?xml') !== false;
    }
    return false;
}

function fetchRemoteImage($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_HTTPHEADER => ['Accept: image/*,*/*;q=0.8'],
    ]);
    $content = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    if ($content === false || $status >= 400 || $content === '') {
        return false;
    }
    if (stripos($contentType, 'text/html') !== false) {
        return false;
    }
    return $content;
}

$candidates = array_unique([$remoteRelativePath, $relativePath]);
foreach ($remoteBases as $remoteBase) {
    foreach ($candidates as $remotePath) {
        $content = fetchRemoteImage(rtrim($remoteBase, '/') . '/' . ltrim($remotePath, '/'));
        if ($content === false) continue;
        if (!isValidImageContent($content, $ext)) continue;

        $dir = dirname($localPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($localPath, $content);
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        echo $content;
        exit;
    }
}

http_response_code(404);
