<?php
// Universal router for Vercel Serverless PHP Runtime
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$root = dirname(__DIR__);

// 1. Static Assets (CSS, JS, Images, Fonts)
$staticExts = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'webp'];
$ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));

if (in_array($ext, $staticExts)) {
    $staticFile = $root . $uri;
    if (file_exists($staticFile) && !is_dir($staticFile)) {
        $mimes = [
            'css'   => 'text/css',
            'js'    => 'application/javascript',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'gif'   => 'image/gif',
            'svg'   => 'image/svg+xml',
            'ico'   => 'image/x-icon',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
            'webp'  => 'image/webp'
        ];
        if (isset($mimes[$ext])) {
            header('Content-Type: ' . $mimes[$ext]);
        }
        readfile($staticFile);
        exit;
    }
}

// 2. Normalize URI for PHP Script Routing
if ($uri === '/' || $uri === '') {
    $script = '/index.php';
} else {
    $script = $uri;
    if (substr($script, -4) !== '.php') {
        if (is_dir($root . $script)) {
            $script = rtrim($script, '/') . '/index.php';
        } else {
            $script = $script . '.php';
        }
    }
}

$targetFile = $root . $script;

// Fallback to home if target file does not exist
if (!file_exists($targetFile)) {
    $targetFile = $root . '/index.php';
}

// Set up execution context to mimic direct script call
$_SERVER['SCRIPT_FILENAME'] = $targetFile;
$_SERVER['SCRIPT_NAME'] = $script;
$_SERVER['PHP_SELF'] = $script;

chdir(dirname($targetFile));
require $targetFile;
