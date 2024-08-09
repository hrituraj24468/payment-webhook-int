<?php
// Test autoload
require '../vendor/autoload.php';

if (class_exists('Stripe\Stripe')) {
    echo 'Stripe library loaded successfully!';
} else {
    echo 'Failed to load Stripe library.';
}
?>