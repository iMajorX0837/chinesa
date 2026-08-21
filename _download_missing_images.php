<?php
set_time_limit(0);
ini_set('memory_limit', '512M');

include_once __DIR__ . '/config.php';
include_once __DIR__ . '/' . DASH . '/services/database.php';

$remoteBase = 'https://77qw7.com';
$root = __DIR__;
$ok = 0;
$skip = 0;
$fail = 0;
$failed = [];

function normalizeProviderKey($value) {
    $value = trim((string)$value);
    return $value === '' ? '' : strtoupper($value);
}

function getProviderConfig($provider) {
    global $mysqli;
    static $providersMap = null;
    static $providersNameMap = null;
    if ($providersMap === null) {
        $providersMap = [];
        $providersNameMap = [];
        $res = $mysqli->query("SELECT * FROM provedores");
        if ($res) {
            while ($prow = $res->fetch_assoc()) {
                $config = [
                    'name' => $prow['name'],
                    'code' => $prow['code'],
                    'id' => (int)$prow['id'],
                    'status' => (int)$prow['status']
                ];
                $codeKey = normalizeProviderKey($prow['code']);
                $nameKey = normalizeProviderKey($prow['name']);
                if ($codeKey !== '') $providersMap[$codeKey] = $config;
                if ($nameKey !== '') $providersNameMap[$nameKey] = $config;
                if ($prow['code'] !== '') $providersMap[$prow['code']] = $config;
                if ($prow['name'] !== '') $providersNameMap[$prow['name']] = $config;
            }
        }
    }
    $providerKey = normalizeProviderKey($provider);
    if ($providerKey !== '') {
        if (isset($providersMap[$providerKey])) return $providersMap[$providerKey];
        if (isset($providersNameMap[$providerKey])) return $providersNameMap[$providerKey];
        $groups = [
            'PG' => ['PG', 'PGSOFT', 'KKGAME', 'KK'],
            'PP' => ['PP', 'PRAGMATIC', 'ONE_API_PP'],
            'JDB' => ['JDB', 'ONE_API_JDB', 'SLOT-JDB', 'SLOT_JDB'],
            'TADA' => ['TADA', 'ONE_API_TADA', 'SLOT-TADA', 'SLOT_TADA'],
            'FACHAI' => ['FACHAI', 'ONE_API_FACHAI', 'SLOT-FACHAI', 'SLOT_FACHAI'],
            'CQ9' => ['CQ9', 'ONE_API_CQ9'],
            'SPRIBE' => ['SPRIBE', 'ONE_API_SPRIBE'],
        ];
        foreach ($groups as $canonical => $list) {
            if ($providerKey === $canonical || in_array($providerKey, $list, true)) {
                if (isset($providersNameMap[$canonical])) return $providersNameMap[$canonical];
                if (isset($providersMap[$canonical])) return $providersMap[$canonical];
                foreach ($list as $alias) {
                    if (isset($providersMap[$alias])) return $providersMap[$alias];
                    if (isset($providersNameMap[$alias])) return $providersNameMap[$alias];
                }
            }
        }
    }
    return ['name' => $provider, 'code' => $provider, 'id' => 0, 'status' => 1];
}

function buildLogoFlag($providerName, $gameName) {
    $value = str_replace(["\r", "\n"], '', (string)$gameName);
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($ascii !== false) $value = $ascii;
    $value = preg_replace('/[^a-zA-Z0-9]/', '', $value);
    return $providerName . '_' . $value;
}

function isImageBytes($bytes) {
    if (!$bytes || strlen($bytes) < 32) return false;
    $head = substr($bytes, 0, 16);
    if (stripos($head, '<!doctype') !== false || stripos($head, '<html') !== false) return false;
    return strncmp($bytes, "\x89PNG", 4) === 0
        || strncmp($bytes, "\xFF\xD8\xFF", 3) === 0
        || strncmp($bytes, 'GIF8', 4) === 0
        || (strncmp($bytes, 'RIFF', 4) === 0 && substr($bytes, 8, 4) === 'WEBP')
        || stripos($head, '<svg') !== false
        || stripos($head, '<?xml') !== false;
}

function fetchUrl($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_HTTPHEADER => ['Accept: image/*,*/*;q=0.8'],
    ]);
    $bytes = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status >= 400 || $bytes === false) return false;
    return $bytes;
}

function saveIfMissing($localRel, $remotePath, $forceIfSmall = 0) {
    global $remoteBase, $root, $ok, $skip, $fail, $failed;
    $localPath = $root . '/' . ltrim(str_replace('\\', '/', $localRel), '/');
    $dir = dirname($localPath);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $exists = is_file($localPath) && filesize($localPath) > $forceIfSmall;
    if ($exists) {
        $skip++;
        return;
    }
    $bytes = fetchUrl(rtrim($remoteBase, '/') . '/' . ltrim($remotePath, '/'));
    if ($bytes === false || !isImageBytes($bytes)) {
        $fail++;
        $failed[] = $remotePath;
        return;
    }
    file_put_contents($localPath, $bytes);
    $ok++;
}

$iconPaths = [
    'icons/heart.png',
    'icons/maintain.png',
    'icons/customer_service.png',
    'icons/No_record.png',
    'icons/minecart.png',
    'icons/texture_bg.png',
    'icons/ic_raid_install.png',
    'icons/ic_actived.png',
    'icons/ic_sd.png',
    'icons/favorite_on.png',
    'icons/favorite_off.png',
    'icons/blue-default-all-email.png',
    'icons/platform/ONE_API_HOT-2.png',
    'icons/sort/POPULAR_on.png',
    'icons/sort/POPULAR_on.svg',
    'icons/sort/POPULAR_on1.png',
    'icons/sort/RECENT_on.png',
    'icons/sort/RECENT_on.svg',
    'icons/sort/SEARCH_on.png',
    'icons/sort/SEARCH_on.svg',
    'icons/sort/FAVORITE_on.png',
    'icons/sort/FAVORITE_on.svg',
    'icons/sort/ELECTRONIC_on.png',
    'icons/sort/CHESS_on.png',
    'icons/sort/FISHING_on.png',
    'icons/sort/VIDEO_on.png',
    'icons/sort/SPORTS_on.png',
    'icons/sort/LOTTERY_on.png',
    'icons/sort/ALL_on.png',
    'icons/tabbar/inicio3.png',
    'icons/tabbar/inicio-fx.png',
    'icons/tabbar/promo3.png',
    'icons/tabbar/promo-fx.png',
    'icons/tabbar/withdraw3.png',
    'icons/tabbar/withdraw-fx.png',
    'icons/tabbar/perfil3.png',
    'icons/tabbar/perfil-fx.png',
    'icons/tabbar/agency3.png',
    'icons/tabbar/agency-fx.png',
    'icons/tabbar/entrar3.png',
    'icons/tabbar/vip3.png',
];

foreach (['vip0~10','vip11~20','vip21~30','vip31~40','vip41~50','vip51~60','vip61~70','vip71~80','vip81~90','vip91~100'] as $vip) {
    $iconPaths[] = 'icons/perfil/' . $vip . '.png';
}
foreach (['convidar','customer','detail','logout','security','resgate'] as $p) {
    $iconPaths[] = 'icons/perfil/' . $p . '.png';
    $iconPaths[] = 'icons/perfil/' . $p . '.svg';
}
for ($i = 1; $i <= 30; $i++) {
    $iconPaths[] = 'first/vip/vip' . $i . '.png';
}

echo "Baixando icones...\n";
foreach ($iconPaths as $rel) {
    $force = ($rel === 'icons/heart.png') ? 500 : 0;
    saveIfMissing($rel, $rel, $force);
}

echo "Baixando capas dos jogos...\n";
$cacheDir = $root . '/uploads/game-logos';
if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

$res = $mysqli->query("SELECT id, game_name, provider FROM games WHERE status = 1");
$flags = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $cfg = getProviderConfig($row['provider']);
        $flag = buildLogoFlag($cfg['name'], $row['game_name']);
        $flags[$flag] = true;
    }
}

$batch = [];
foreach (array_keys($flags) as $flag) {
    $cacheFile = $cacheDir . '/' . preg_replace('/[^a-zA-Z0-9._-]/', '', $flag);
    if (is_file($cacheFile) && filesize($cacheFile) > 32) {
        $skip++;
        continue;
    }
    $batch[] = $flag;
}

$mh = curl_multi_init();
$handles = [];
$chunkSize = 12;
$chunks = array_chunk($batch, $chunkSize);
foreach ($chunks as $i => $chunk) {
    $handles = [];
    foreach ($chunk as $flag) {
        $url = $remoteBase . '/api/frontend/game-logo/style1/en/' . rawurlencode($flag) . '.jpg';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
            CURLOPT_HTTPHEADER => ['Accept: image/*,*/*;q=0.8'],
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[(int)$ch] = [$ch, $flag];
    }
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) curl_multi_select($mh, 1.0);
    } while ($running && $status === CURLM_OK);

    foreach ($handles as $item) {
        [$ch, $flag] = $item;
        $bytes = curl_multi_getcontent($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        $cacheFile = $cacheDir . '/' . preg_replace('/[^a-zA-Z0-9._-]/', '', $flag);
        if ($statusCode < 400 && isImageBytes($bytes)) {
            file_put_contents($cacheFile, $bytes);
            $ok++;
        } else {
            $fail++;
            $failed[] = 'game-logo/' . $flag;
        }
    }
    echo "lote " . ($i + 1) . "/" . count($chunks) . " ok=$ok skip=$skip fail=$fail\n";
}
curl_multi_close($mh);

echo "\nCONCLUIDO ok=$ok skip=$skip fail=$fail\n";
if ($failed) {
    echo "FALHAS (" . count($failed) . "):\n";
    echo implode("\n", array_slice($failed, 0, 40)) . "\n";
}
