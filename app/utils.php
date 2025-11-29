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
