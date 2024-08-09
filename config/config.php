<?php
require __DIR__ . '/../vendor/autoload.php';  // Include the autoload file

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}
?>
