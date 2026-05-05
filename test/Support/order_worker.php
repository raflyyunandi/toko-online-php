<?php

declare(strict_types=1);

if ($argc < 5) {
    fwrite(STDERR, "Usage: php order_worker.php <baseUrl> <productId> <qty> <customer>\n");
    exit(2);
}

$baseUrl = rtrim((string) $argv[1], '/');
$productId = (int) $argv[2];
$qty = (int) $argv[3];
$customer = (string) $argv[4];

$payload = [
    'customer_name' => $customer,
    'items' => [
        ['product_id' => $productId, 'quantity' => $qty],
    ],
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => json_encode($payload),
        'ignore_errors' => true,
        'timeout' => 10,
    ],
]);

$body = @file_get_contents($baseUrl . '/api/orders', false, $context);
$status = 0;

if (isset($http_response_header) && is_array($http_response_header)) {
    foreach ($http_response_header as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m)) {
            $status = (int) $m[1];
            break;
        }
    }
}

fwrite(STDOUT, json_encode([
    'status' => $status,
    'body' => $body,
]));

