<?php
require_once __DIR__ . '/webhook_dispatcher.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// HEALTHCHECK
if ($path === '/healthz') {
    echo "OK";
    http_response_code(200);
    exit;
}

// PUB/SUB PUSH ENDPOINT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handle_pubsub_push();
    exit;
}

http_response_code(404);
echo "Not Found";
?>