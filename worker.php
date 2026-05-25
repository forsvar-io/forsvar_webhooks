<?php

error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * Forsvar - Pub/Sub Pull Worker
 * -------------------------------------
 * - Lee mensajes de Pub/Sub usando Workload Identity
 * - Procesa eventos internos (webhooks, triggers, etc.)
 * - No expone endpoints, 100% privado
 */

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/utils.php';

use Google\Cloud\PubSub\PubSubClient;


// ---------------------------
// ENVIRONMENT VARIABLES
// ---------------------------
$projectId        = getenv('GOOGLE_PROJECT_ID');
$subscriptionName = getenv('PUBSUB_SUBSCRIPTION');

if (!$projectId || !$subscriptionName) {
    echo "❌ ERROR: Missing environment variables GOOGLE_PROJECT_ID or PUBSUB_SUBSCRIPTION\n";
    webhooks_notify_notifications_center_error(
        'forsvar_webhooks: missing GOOGLE_PROJECT_ID or PUBSUB_SUBSCRIPTION',
        ['phase' => 'startup']
    );
    exit(1);
}

echo "🚀 Forsvar Worker starting...\n";
echo "📌 Project: $projectId\n";
echo "📌 Subscription: $subscriptionName\n";

// ---------------------------
// INITIALIZE PUBSUB CLIENT
// ---------------------------
$pubsub = new PubSubClient([
    'projectId' => $projectId,
    // Workload Identity → no credentials file needed
]);

$subscription = $pubsub->subscription($subscriptionName);

// ---------------------------
// MAIN LOOP
// ---------------------------
while (true) {

    try {
        // Pull up to 10 messages
        $messages = $subscription->pull([
            'returnImmediately' => false,  // Long-poll
            'maxMessages' => 10
        ]);

        if (empty($messages)) {
            continue;
        }

        foreach ($messages as $message) {

            $rawData = $message->data();
            $messageId = $message->id();

            echo "📩 Received message ID: $messageId\n";

            // Parse JSON safely
            $payload = json_decode($rawData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "⚠️ Invalid JSON in message. Sending to DLQ...\n";
                webhooks_notify_notifications_center_error(
                    'forsvar_webhooks: invalid JSON in Pub/Sub message',
                    [
                        'message_id' => $messageId,
                        'json_error' => json_last_error_msg(),
                    ],
                    null
                );
                // ACK igual para que pase al DLQ
                $subscription->acknowledge($message);
                continue;
            }

            // -----------------------
            // PROCESS THE MESSAGE
            // -----------------------

            try {
                process_webhook($payload,$messageId);
                echo "✔ Processed\n";

            } catch (Throwable $err) {
                echo "❌ Error processing message: " . $err->getMessage() . "\n";
                $cid = isset($payload['company_id']) ? (int) $payload['company_id'] : null;
                webhooks_notify_notifications_center_error(
                    'forsvar_webhooks: error processing Pub/Sub message — ' . $err->getMessage(),
                    [
                        'message_id' => $messageId,
                        'exception' => get_class($err),
                        'file' => $err->getFile(),
                        'line' => $err->getLine(),
                    ],
                    ($cid !== null && $cid > 0) ? $cid : null
                );
                // No ACK → Pub/Sub lo reintenta y luego DLQ
                continue;
            }

            // -----------------------
            // ACK MESSAGE
            // -----------------------
            $subscription->acknowledge($message);
            echo "✔ ACK ($messageId)\n\n";
        }

    } catch (Throwable $ex) {
        echo "❌ Worker error: " . $ex->getMessage() . "\n";
        webhooks_notify_notifications_center_error(
            'forsvar_webhooks: worker loop error — ' . $ex->getMessage(),
            [
                'exception' => get_class($ex),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
            ],
            null
        );
        echo "⏳ Sleeping 2 seconds...\n";
        sleep(2);
    }
}

function get_conn() {
    $conn = new mysqli(
        getenv('DB_HOST'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD'),
        getenv('DB_DATABASE'),
        getenv('DB_PORT')
    );

    if ($conn->connect_error) {
        throw new Exception("MySQL connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

    return $conn;
}

function send_webhook_post($url, $payload, $hmacSecret = null)
{
    $ch = curl_init($url);

    $headers = [
        "Content-Type: application/json",
        "User-Agent: ForsvarWebhook/1.0"
    ];

    if ($hmacSecret) {
        $signature = hash_hmac("sha256", $payload, $hmacSecret);
        $headers[] = "x-forsvar-signature: $signature";
    }

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return [
        "success"     => ($error === "" && $status >= 200 && $status < 300),
        "status_code" => $status,
        "response"    => $response,
        "headers"     => $headers,
        "error"       => $error ?: null,
    ];
}

function extractEntityId(array $payload)
{
    // Define allowed / known ID fields
    $possibleKeys = [
        "party_id",
        "case_id",
        "alert_id",
        "kyc_session_id",
        "risk_id",
        "transaction_id",
        "event_id",
        "entity_id",        // generic fallback
    ];

    foreach ($possibleKeys as $key) {
        if (isset($payload[$key]) && $payload[$key] !== "" && $payload[$key] !== null) {
            return $payload[$key];
        }
    }

    return 0; // not found
}

/**
 * -----------------------------
 * PROCESSOR FUNCTION
 * -----------------------------
 * Toda tu lógica de webhook/eventos va acá.
 *
 * NO hagas exit() dentro de esta función.
 */
function process_webhook(array $data,$messageId)
{
    include_once('datahandler/datahandler.php');

    $conn = get_conn();

    $company_id  = $data["company_id"] ?? null;
    unset($data['company_id']);

    $jsonPayload = json_encode($data, JSON_UNESCAPED_UNICODE);
    $eventName   = $data["event"] ?? "unknown";

    $webhook_events_data = [
        "event_name" => $eventName,
        "payload"    => $jsonPayload,
        "message_id" => $messageId,
        "company_id" => $company_id,
        "entity_id" => extractEntityId($data),
        "processed"  => 0,
        "process_log"=> ""
    ];


    // 1️⃣ Insertamos el webhook recibido en DB
    $webhook = insert_row($conn, "webhooks_events", $webhook_events_data);

    

    $webhook_id = $webhook['lastid'];

    if (!$webhook_id) {
        echo "❌ Failed to insert webhook\n";
        $cid = $company_id !== null && $company_id !== '' ? (int) $company_id : null;
        webhooks_notify_notifications_center_error(
            'forsvar_webhooks: failed to insert webhooks_events row',
            [
                'message_id' => $messageId,
                'event' => $eventName,
                'insert_lastid' => $webhook['lastid'] ?? null,
                'insert_error' => $webhook['error'] ?? null,
            ],
            ($cid !== null && $cid > 0) ? $cid : null
        );
        return;
    }

    echo "💾 Webhook stored with ID $webhook_id\n";

    // 2️⃣ Buscamos webhook URL + secret del cliente
    $tableName = "companies";
    $columns = 'webhook_url,webhook_secret'; 
    $filters = array("ID" => $company_id);
    $orderBy = "";

    $companies = selectData($conn, $tableName, $columns, $filters, $orderBy)['data'];

    if (sizeof($companies) == 0) {
        echo "❌ Company not found\n";

        update_data($conn, "webhooks_events", [
            "processed"  => 0,
            "process_log"=> "Company not found",
            "http_code" => 500
        ], ["ID = $webhook_id"]);

        $cid = $company_id !== null && $company_id !== '' ? (int) $company_id : null;
        webhooks_notify_notifications_center_error(
            'forsvar_webhooks: company not found for webhook delivery',
            [
                'webhook_event_id' => $webhook_id,
                'message_id' => $messageId,
                'company_id_attempted' => $cid,
            ],
            ($cid !== null && $cid > 0) ? $cid : null
        );

        return;
    }

    
    $webhookUrl = $companies[0]['webhook_url'];
    $hmacSecret = $companies[0]['webhook_secret'];

    // 3️⃣ Enviar webhook
    $result = send_webhook_post($webhookUrl, $jsonPayload, $hmacSecret);

    $parsedUrl = @parse_url($webhookUrl);
    $webhookHost = is_array($parsedUrl) && !empty($parsedUrl['host']) ? (string) $parsedUrl['host'] : '';
    webhooks_gcp_json_log('INFO', 'forsvar_webhooks_outbound_http', [
        'pubsub_message_id' => $messageId,
        'webhook_event_id' => $webhook_id,
        'company_id' => $company_id,
        'event' => $eventName,
        'webhook_host' => $webhookHost,
        'delivery_success' => !empty($result['success']),
        'http_status' => $result['status_code'] ?? 0,
        'curl_error' => $result['error'] ?? null,
        'request_payload' => $jsonPayload,
        'response_body' => webhooks_log_truncate(isset($result['response']) ? (string) $result['response'] : '', 4096),
    ]);

    // 4️⃣ Actualizar registro
    if ($result["success"]) {

        echo "📬 Webhook delivered to $webhookUrl\n";

        $update_data = [
            "processed"  => 1,
            "process_log"=> "Delivered successfully",
            "webhook_url" => $webhookUrl,
            "http_code" => $result["status_code"],
            "header"   => json_encode($result['headers'])
        ];

        update_data($conn, "webhooks_events", $update_data, ["ID = $webhook_id"]);

    } else {

        $error_msg = $result["error"] 
            ? $result["error"] 
            : "HTTP " . $result["status_code"];

        echo "❌ Webhook delivery failed: $error_msg\n";

        update_data($conn, "webhooks_events", [
            "processed"  => 0,
            "process_log"=> $error_msg,
            "webhook_url" => $webhookUrl,
            "http_code" => $result["status_code"],
            "header"   => json_encode($result['headers'])
        ], ["ID = $webhook_id"]);

        $cid = $company_id !== null && $company_id !== '' ? (int) $company_id : null;
        $respSnippet = is_string($result['response'] ?? null)
            ? substr($result['response'], 0, 500)
            : null;
        webhooks_notify_notifications_center_error(
            'forsvar_webhooks: outbound webhook delivery failed — ' . $error_msg,
            [
                'webhook_event_id' => $webhook_id,
                'message_id' => $messageId,
                'http_code' => $result['status_code'],
                'response_snippet' => $respSnippet,
            ],
            ($cid !== null && $cid > 0) ? $cid : null
        );
    }
}
