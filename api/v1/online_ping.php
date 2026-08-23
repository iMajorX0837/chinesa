<?php
session_start();
include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../' . DASH . '/services/database.php';
include_once __DIR__ . '/../../' . DASH . '/services/crud.php';

header('Content-Type: application/json; charset=utf-8');

if (isset($_GET['count'])) {
    echo json_encode(['success' => true, 'count' => (int)get_online_count(120)]);
    exit;
}

$isOffline = isset($_POST['offline']) || isset($_GET['offline']);

$user = '';
if (!empty($_POST['user_code'])) {
    $user = trim((string)$_POST['user_code']);
} elseif (!empty($_SESSION['data']['user_code'])) {
    $user = trim((string)$_SESSION['data']['user_code']);
} elseif (!empty($_SESSION['data_user']['email'])) {
    $user = trim((string)$_SESSION['data_user']['email']);
} elseif (!empty($_SESSION['data_adm']['id'])) {
    $user = 'adm-' . $_SESSION['data_adm']['id'];
}

if ($user === '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $user = ($ip !== '' ? $ip : 'anon') . '-' . substr(sha1($ua), 0, 8);
}

if ($isOffline) {
    unregister_user_online($user, 120);
    echo json_encode(['success' => true, 'offline' => true]);
    exit;
}

register_user_online($user, 120);
echo json_encode(['success' => true, 'offline' => false, 'count' => (int)get_online_count(120)]);
