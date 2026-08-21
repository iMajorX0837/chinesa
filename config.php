<?php

define('DASH', 'admin');

if (!function_exists('app_force_https_from_proxy')) {
    function app_force_https_from_proxy()
    {
        $https = false;
        $fwd = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if (strpos($fwd, 'https') !== false) {
            $https = true;
        }
        $cf = (string)($_SERVER['HTTP_CF_VISITOR'] ?? '');
        if ($cf !== '' && stripos($cf, 'https') !== false) {
            $https = true;
        }
        $forwarded = (string)($_SERVER['HTTP_FORWARDED'] ?? '');
        if (stripos($forwarded, 'proto=https') !== false) {
            $https = true;
        }
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $https = true;
        }
        $siteUrl = (string)(getenv('SITE_URL') ?: '');
        if (stripos($siteUrl, 'https://') === 0) {
            $https = true;
        }
        if ($https) {
            $_SERVER['HTTPS'] = 'on';
            $_SERVER['SERVER_PORT'] = 443;
            $_SERVER['REQUEST_SCHEME'] = 'https';
        }
    }
}
app_force_https_from_proxy();
