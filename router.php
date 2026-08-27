<?php
// Dev-only router for `php -S`, mirrors the .htaccess rewrite for local testing.
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (preg_match('#/\.#', $uri) || preg_match('#^/data/#', $uri)) {
    http_response_code(403);
    echo '403 Forbidden';
    return true;
}

if (preg_match('#(?:^|/)r/([A-Za-z0-9]+)/?$#', $uri, $m)) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/r.php';
    return true;
}

if ($uri === '/') {
    $uri = '/index.html';
}

$file = __DIR__ . $uri;

if (is_dir($file)) {
    $indexFile = rtrim($file, '/') . '/index.php';
    if (is_file($indexFile)) {
        require $indexFile;
        return true;
    }
}

if (is_file($file)) {
    return false;
}

http_response_code(404);
echo '404 Not Found';
