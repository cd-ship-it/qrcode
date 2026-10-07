<?php
// Dev-only router for `php -S`.
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (preg_match('#/\.#', $uri)) {
    http_response_code(403);
    echo '403 Forbidden';
    return true;
}

if ($uri === '/') {
    $uri = '/index.html';
}

$file = __DIR__ . $uri;

if (is_file($file)) {
    return false;
}

http_response_code(404);
echo '404 Not Found';
