<?php
// Vercel Serverless PHP Router Entrypoint

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedUrl = parse_url($requestUri, PHP_URL_PATH);
$path = ltrim($parsedUrl, '/');

if (empty($path) || $path === '/') {
    $path = 'user/index.php';
}

$rootDir = dirname(__DIR__);
$targetFile = $rootDir . '/' . $path;

// Fallback lookup for static assets (CSS, JS, Images, Fonts, Uploads)
if (!file_exists($targetFile) && !is_dir($targetFile)) {
    if (file_exists($rootDir . '/user/' . $path)) {
        $targetFile = $rootDir . '/user/' . $path;
    } elseif (file_exists($rootDir . '/public/' . $path)) {
        $targetFile = $rootDir . '/public/' . $path;
    } elseif (file_exists($rootDir . '/admin/' . $path)) {
        $targetFile = $rootDir . '/admin/' . $path;
    }
}

if (is_dir($targetFile)) {
    $targetFile = rtrim($targetFile, '/') . '/index.php';
}

if (file_exists($targetFile) && !is_dir($targetFile)) {
    $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    if ($ext === 'php') {
        $_SERVER['SCRIPT_FILENAME'] = realpath($targetFile);
        $_SERVER['SCRIPT_NAME'] = '/' . ltrim(str_replace('\\', '/', str_replace($rootDir, '', $targetFile)), '/');
        chdir(dirname($targetFile));
        require $targetFile;
        exit;
    }

    $mimeTypes = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'json'  => 'application/json',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'webp'  => 'image/webp',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
    ];

    if (isset($mimeTypes[$ext])) {
        header("Content-Type: " . $mimeTypes[$ext]);
    } else {
        header("Content-Type: " . (mime_content_type($targetFile) ?: 'application/octet-stream'));
    }
    
    header("Cache-Control: public, max-age=31536000, immutable");
    readfile($targetFile);
    exit;
}

http_response_code(404);
echo "404 - Page Not Found";
