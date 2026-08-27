<?php
session_start();
require __DIR__ . '/../config.php';
header('Content-Type: application/json');

if (empty($_SESSION['admin_authenticated'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $links = loadLinks();
    usort($links, fn($a, $b) => strcmp($b['createdAt'], $a['createdAt']));
    echo json_encode($links);
    exit;
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? '';
    $found = false;
    withLinks(function (&$links) use ($id, &$found) {
        $before = count($links);
        $links = array_values(array_filter($links, fn($l) => $l['id'] !== $id));
        $found = count($links) !== $before;
    });
    if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => 'not found']);
        exit;
    }
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method not allowed']);
