<?php

function send_webhook($url, $payload) {

    $dataString = json_encode($payload, JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "X-Forsvar-Event: " . ($payload["event"] ?? "unknown")
        ],
        CURLOPT_POSTFIELDS => $dataString,
        CURLOPT_TIMEOUT => 5
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($error) {
        return ["success" => false, "error" => $error];
    }

    if ($status < 200 || $status >= 300) {
        return ["success" => false, "error" => "HTTP $status: $response"];
    }

    return ["success" => true];
}

function log_info($msg) {
    error_log("[INFO] " . $msg);
}

function log_error($msg) {
    error_log("[ERROR] " . $msg);
}

/**
 * POST a notifications_center /errors.php (misma mecánica y env que forsvar_onboarding `forsvar_post_notifications_center_error`).
 * Ejecución síncrona (worker CLI); si faltan FORSVAR_SLACK_* o CURL falla, no lanza.
 *
 * @param array<string, mixed> $details
 */
function webhooks_notify_notifications_center_error(string $message, array $details = [], ?int $companyId = null): void
{
    $channel = getenv('FORSVAR_SLACK_ERRORS_CHANNEL') ?: '';
    $webhook = getenv('FORSVAR_SLACK_ERRORS_WEBHOOK') ?: '';
    $channel = trim((string) $channel);
    $webhook = trim((string) $webhook);
    if ($channel === '' || $webhook === '') {
        return;
    }

    $base = getenv('FORSVAR_NOTIFICATIONS') ?: '';
    $base = rtrim(trim((string) $base), '/');
    if ($base === '') {
        $base = 'http://forsvar_notifications_center:8011';
    }

    $user = getenv('FORSVAR_NOTIFICATIONS_ERRORS_USER') ?: 'Forsvar';
    $pass = getenv('FORSVAR_NOTIFICATIONS_ERRORS_PASSWORD') ?: 'Picoton77';
    $authHeader = 'Basic ' . base64_encode((string) $user . ':' . (string) $pass);

    $payload = [
        'message' => $message,
        'error_message' => $message,
        'level' => 'error',
        'source' => 'forsvar_webhooks',
        'component' => 'worker.php',
        'notification_identifier' => 'system_error',
        'details' => $details,
        'slack_channel' => $channel,
        'slack_webhook' => $webhook,
    ];
    if ($companyId !== null && $companyId > 0) {
        $payload['company_id'] = $companyId;
    }

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return;
    }

    $url = $base . '/errors.php';
    $ch = curl_init($url);
    if ($ch === false) {
        return;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: ' . $authHeader,
        ],
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_NOSIGNAL => true,
    ]);
    @curl_exec($ch);
    curl_close($ch);
}
