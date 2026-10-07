<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

// Serve static assets if they exist
$filePath = __DIR__ . '/..' . $uri;
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf'
    ];
    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
    }
    readfile($filePath);
    exit;
}

// Route admin panel
if (strpos($uri, '/admin') === 0) {
    if ($uri === '/admin' || $uri === '/admin/') {
        require __DIR__ . '/../admin/index.php';
        exit;
    }
    $adminFile = __DIR__ . '/..' . $uri;
    if (file_exists($adminFile) && !is_dir($adminFile)) {
        require $adminFile;
        exit;
    }
}

// Route standard PHP pages
if ($uri === '/' || $uri === '') {
    require __DIR__ . '/../index.php';
    exit;
}

$page = ltrim($uri, '/');
if (file_exists(__DIR__ . '/../' . $page) && !is_dir(__DIR__ . '/../' . $page)) {
    require __DIR__ . '/../' . $page;
    exit;
}

if (file_exists(__DIR__ . '/../' . $page . '.php')) {
    require __DIR__ . '/../' . $page . '.php';
    exit;
}

// Fallback to home
require __DIR__ . '/../index.php';
