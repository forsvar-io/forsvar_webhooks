<?php

require_once __DIR__ . '/utils.php';

/**
 * Handle Pub/Sub messages delivered via push subscription.
 * Pub/Sub POST format:
 * {
 *   "message": {
 *     "data": "BASE64",
 *     "attributes": { ... }
 *   },
 *   "subscription": "projects/.../subscriptions/..."
 * }
 */
function handle_pubsub_push() {
    $raw = file_get_contents("php://input");
    log_info("Raw PubSub Payload: " . $raw);

    $request = json_decode($raw, true);

    if (!$request || !isset($request["message"]["data"])) {
        log_error("Invalid PubSub message");
        http_response_code(400);
        return;
    }

    // Decode base64 JSON payload
    $payloadJson = base64_decode($request["message"]["data"]);
    $payload = json_decode($payloadJson, true);

    log_info("Decoded payload: " . $payloadJson);

    if (!$payload) {
        log_error("Could not decode JSON payload");
        http_response_code(400);
        return;
    }

    // Extract event
    $event = $payload["event"] ?? "unknown";

    // Your webhook URL — podés guardarlo en DB o env
    $webhookUrl = "http://forsvar_frontend:80/receive_webhooks.php";

    if (!$webhookUrl) {
        log_error("WEBHOOK_TARGET_URL is not configured");
        http_response_code(200); // ACK igual
        return;
    }

    // Send webhook
    $res = send_webhook($webhookUrl, $payload);

    if ($res["success"]) {
        log_info("Webhook sent OK");
        http_response_code(200); // ACK Pub/Sub
        return;
    }

    log_error("Webhook ERROR: " . $res["error"]);
    http_response_code(200); // STILL ACK to avoid infinite retries
}
