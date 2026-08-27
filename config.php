<?php

function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim(trim($value), "\"'");
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

loadEnv(__DIR__ . '/.env');

define('ADMIN_PASSWORD', $_ENV['ADMIN_PASSWORD'] ?? '');
define('CONFIGURED_BASE_URL', rtrim($_ENV['BASE_URL'] ?? '', '/'));
define('DATA_FILE', __DIR__ . '/data/links.json');

function currentBaseUrl() {
    if (CONFIGURED_BASE_URL !== '') return CONFIGURED_BASE_URL;
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $_SERVER['HTTP_HOST'];
}

function ensureDataFile() {
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!file_exists(DATA_FILE)) file_put_contents(DATA_FILE, '[]');
}

function loadLinks() {
    ensureDataFile();
    $data = json_decode(file_get_contents(DATA_FILE), true);
    return is_array($data) ? $data : [];
}

// Locks the file for the duration of $callback($links) and persists its return value.
function withLinks(callable $callback) {
    ensureDataFile();
    $fp = fopen(DATA_FILE, 'c+');
    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $links = json_decode($raw, true);
    if (!is_array($links)) $links = [];

    $result = $callback($links);

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($links, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $result;
}

function generateId() {
    return bin2hex(random_bytes(5));
}
