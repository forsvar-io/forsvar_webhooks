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

use Google\Cloud\PubSub\PubSubClient;

// ---------------------------
// ENVIRONMENT VARIABLES
// ---------------------------
$projectId        = getenv('GOOGLE_PROJECT_ID');
$subscriptionName = getenv('PUBSUB_SUBSCRIPTION');

if (!$projectId || !$subscriptionName) {
    echo "❌ ERROR: Missing environment variables GOOGLE_PROJECT_ID or PUBSUB_SUBSCRIPTION\n";
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
    ];

    if ($hmacSecret) {
        $signature = hash_hmac("sha256", $payload, $hmacSecret);
        $headers[] = "X-FORSVAR-SIGNATURE: $signature";
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
        "error"       => $error ?: null,
    ];
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

    // 1️⃣ Insertamos el webhook recibido en DB
    $webhook = insert_row($conn, "webhooks_events", [
        "event_name" => $eventName,
        "payload"    => $jsonPayload,
        "message_id" => $messageId,
        "company_id" => $company_id,
        "processed"  => 0,
        "process_log"=> ""
    ]);

    $webhook_id = $webhook['lastid'];

    if (!$webhook_id) {
        echo "❌ Failed to insert webhook\n";
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
            "process_log"=> "Company not found"
        ], ["ID = $webhook_id"]);

        return;
    }

    $webhookUrl = $companies[0]['webhook_url'];
    $hmacSecret = $companies[0]['webhook_secret'];

    // 3️⃣ Enviar webhook
    $result = send_webhook_post($webhookUrl, $jsonPayload, $hmacSecret);

    // 4️⃣ Actualizar registro
    if ($result["success"]) {

        echo "📬 Webhook delivered to $webhookUrl\n";

        update_data($conn, "webhooks_events", [
            "processed"  => 1,
            "process_log"=> "Delivered successfully",
            "webhook_url" => $webhookUrl
        ], ["ID = $webhook_id"]);

    } else {

        $error_msg = $result["error"] 
            ? $result["error"] 
            : "HTTP " . $result["status_code"];

        echo "❌ Webhook delivery failed: $error_msg\n";

        update_data($conn, "webhooks_events", [
            "processed"  => 0,
            "process_log"=> $error_msg,
            "webhook_url" => $webhookUrl
        ], ["ID = $webhook_id"]);
    }
}
