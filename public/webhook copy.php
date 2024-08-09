<?php
require_once '../vendor/autoload.php';

// Stripe::setApiKey('sk_test_51PkoFIEuszaLU5xo5UZpB33YRfJXb0RzfmJbJ69sAJSg6Uu8eM6oTanw749ZLQ188QNlru10dHAEftXg3nk6DNzv00AQOP3PAu');

$stripe = new \Stripe\StripeClient('secret key ');
echo "Stripe client instantiated successfully!";
var_dump($stripe);

exit();
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null; // Handle case where header is missing
$endpoint_secret = 'whsec.....';

$payload = @file_get_contents('php://input');

if ($sig_header === null) {
    http_response_code(400);
    echo json_encode(["error" => "Stripe signature header is missing."]);
    
    file_put_contents('webhook_log.txt', "Signature Header: 'not found as it is `null`'\nPayload: $payload\n", FILE_APPEND);
    exit();
}

// Log the signature header and payload to a file for debugging purposes
file_put_contents('webhook_log.txt', "Signature Header: $sig_header\nPayload: $payload\n", FILE_APPEND);

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpoint_secret
    );

    // Process the event
    switch ($event->type) {
        case 'payment_intent.succeeded':
            $paymentIntent = $event->data->object; // contains a StripePaymentIntent
            $paymentId = $paymentIntent->id;
            $amount = $paymentIntent->amount;
            $status = 'succeeded';

            // Example: Update your database with payment success status
            $conn = new mysqli('localhost', 'username', 'password', 'database');
            if ($conn->connect_error) {
                http_response_code(500);
                echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
                exit();
            }

            $stmt = $conn->prepare("UPDATE payments SET payment_status = ? WHERE payment_intent_id = ?");
            $stmt->bind_param("ss", $status, $paymentId); // Change the parameter type to "ss" for string types
            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode(["status" => "success"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to update payment status."]);
            }
            $stmt->close();
            $conn->close();
            break;

        default:
            http_response_code(400);
            echo json_encode(["error" => "Unexpected event type."]);
            exit();
    }

} catch (\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    echo json_encode(["error" => "Invalid payload."]);

} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    echo json_encode(["error" => "Error verifying webhook signature: " . $e->getMessage()]);
}
?>
