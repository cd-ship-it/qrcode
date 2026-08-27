<?php
require __DIR__ . '/../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method not allowed']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$target = $body['target'] ?? null;
$label = $body['label'] ?? '';
$styles = $body['styles'] ?? ['default'];

if (!$target || !is_string($target)) {
    http_response_code(400);
    echo json_encode(['error' => 'target is required']);
    exit;
}

$parts = parse_url($target);
if (!$parts || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
    http_response_code(400);
    echo json_encode(['error' => 'target must be a valid http(s) URL']);
    exit;
}

if (!is_array($styles) || empty($styles)) $styles = ['default'];

$created = [];
withLinks(function (&$links) use ($target, $label, $styles, &$created) {
    foreach ($styles as $style) {
        $record = [
            'id' => generateId(),
            'target' => $target,
            'label' => mb_substr((string) $label, 0, 200),
            'style' => mb_substr((string) $style, 0, 60),
            'createdAt' => gmdate('c'),
            'clickCount' => 0,
            'clicks' => []
        ];
        $links[] = $record;
        $created[] = $record;
    }
});

$base = currentBaseUrl();
echo json_encode(array_map(function ($r) use ($base) {
    return [
        'id' => $r['id'],
        'style' => $r['style'],
        'redirectUrl' => $base . '/r/' . $r['id']
    ];
}, $created));
