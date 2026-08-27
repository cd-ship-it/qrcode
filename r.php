<?php
require __DIR__ . '/config.php';

$id = $_GET['id'] ?? '';
if (!$id) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$target = null;
withLinks(function (&$links) use ($id, &$target) {
    foreach ($links as &$link) {
        if ($link['id'] === $id) {
            $link['clickCount'] = ($link['clickCount'] ?? 0) + 1;
            if (!isset($link['clicks'])) $link['clicks'] = [];
            $link['clicks'][] = [
                'ts' => gmdate('c'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referrer' => $_SERVER['HTTP_REFERER'] ?? ''
            ];
            if (count($link['clicks']) > 200) {
                $link['clicks'] = array_slice($link['clicks'], -200);
            }
            $target = $link['target'];
            break;
        }
    }
});

if (!$target) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

header('Location: ' . $target, true, 303);
