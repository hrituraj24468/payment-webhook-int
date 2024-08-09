<?php
require '../vendor/autoload.php';
require '../config/config.php';

\Stripe\Stripe::setApiKey('secret key');

header('Content-Type: application/json');

try {
    // Create Payment Intent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => 1099, // Amount in cents
        'currency' => 'usd',
        'automatic_payment_methods' => ['enabled' => true],
    ]);

    // Connect to the database
    $mysqli = new mysqli('localhost', 'root', '1234', 'stripe_payments');

    if ($mysqli->connect_error) {
        error_log('Database connection failed: ' . $mysqli->connect_error);
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    // Prepare SQL query with placeholders
    $stmt = $mysqli->prepare("INSERT INTO payments (payment_intent_id, amount, payment_status) VALUES (?, ?, ?)");

    if (!$stmt) {
        error_log('Prepare statement failed: ' . $mysqli->error);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare SQL statement']);
        exit();
    }

    // Bind parameters: 's' for string, 'i' for integer
    $paymentIntentId = $paymentIntent->id;
    $amount = $paymentIntent->amount;
    $status = 'pending'; // Initial status before confirmation

    $stmt->bind_param("sis", $paymentIntentId, $amount, $status);

    // Execute SQL query
    if (!$stmt->execute()) {
        error_log('Execute failed: ' . $stmt->error);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to execute SQL statement']);
        exit();
    }

    // Close statement and connection
    $stmt->close();
    $mysqli->close();

    // Return client secret
    echo json_encode(['clientSecret' => $paymentIntent->client_secret]);

} catch (\Stripe\Exception\ApiErrorException $e) {
    // Handle Stripe API errors
    error_log('Stripe API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Stripe API error: ' . $e->getMessage()]);
} catch (\Exception $e) {
    // Handle general exceptions
    error_log('General error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to create payment intent: ' . $e->getMessage()]);
}
?>