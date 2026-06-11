<?php

require 'vendor/autoload.php';

use MMPay\MMPay;
use Dotenv\Dotenv;

// 1. Load Environment Variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Helper to generate random string
function generateSecureRandomString($length = 6) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyz', ceil($length/strlen($x)) )),1,$length);
}


// 2. Initialize SDK
$mmpay = new MMPay([
    'appId'          => $_ENV['APP_ID'],
    'publishableKey' => $_ENV['PUB_KEY'],
    'secretKey'      => $_ENV['SEC_KEY'],
    'apiBaseUrl'     => $_ENV['BASEURL']
]);


function pay() {
    $orderId = generateSecureRandomString(6);
    
    // Start Timer
    $startTime = microtime(true);

    echo "\n-------------------------------------\n";
    echo "Starting Transaction for Order: $orderId\n";
    echo "-------------------------------------\n";

    try {
        $payload = [
            'orderId'  => $orderId,
            'amount'   => 1500,
            'currency' => "MMK",
            'items'    => [
                ['name' => "Items", 'amount' => 3000, 'quantity' => 1]
            ]
        ];

        // 3. Execute Payment
        $response = $mmpay->pay($payload);

        // End Timer
        $endTime = microtime(true);
        $latencyMs = number_format(($endTime - $startTime) * 1000, 3);

        echo "\n--- Transaction Request Successful ---\n";
        echo "Order ID: $orderId\n";
        echo "**Network Latency: $latencyMs ms**\n";
        echo "Response: \n";
        print_r($response);
        echo "\n--------------------------------------\n";

    } catch (Exception $e) {
        $endTime = microtime(true);
        $latencyMs = number_format(($endTime - $startTime) * 1000, 3);

        echo "\n--- Transaction Request Failed ---\n";
        echo "Order ID: $orderId\n";
        echo "**Network Latency: $latencyMs ms**\n";
        echo "Error Message: " . $e->getMessage() . "\n";
        echo "----------------------------------\n";
    }
}


function get($orderId) { 
    // Start Timer
    $startTime = microtime(true);

    echo "\n-------------------------------------\n";
    echo "Starting Transaction for Order: $orderId\n";
    echo "-------------------------------------\n";

    try {
        $payload = [
            'orderId'  => $orderId
        ];

        // 3. Execute Payment
        $response = $mmpay->get($payload);

        // End Timer
        $endTime = microtime(true);
        $latencyMs = number_format(($endTime - $startTime) * 1000, 3);

        echo "\n--- Transaction Request Successful ---\n";
        echo "Order ID: $orderId\n";
        echo "**Network Latency: $latencyMs ms**\n";
        echo "Response: \n";
        print_r($response);
        echo "\n--------------------------------------\n";

    } catch (Exception $e) {
        $endTime = microtime(true);
        $latencyMs = number_format(($endTime - $startTime) * 1000, 3);

        echo "\n--- Transaction Request Failed ---\n";
        echo "Order ID: $orderId\n";
        echo "**Network Latency: $latencyMs ms**\n";
        echo "Error Message: " . $e->getMessage() . "\n";
        echo "----------------------------------\n";
    }
}

function cancel($orderId) { 
    // Start Timer
    $startTime = microtime(true);

    echo "\n-------------------------------------\n";
    echo "Starting Transaction for Order: $orderId\n";
    echo "-------------------------------------\n";

    try {
        $payload = [
            'orderId'  => $orderId
        ];

        // 3. Execute Payment
        $response = $mmpay->cancel($payload);

        // End Timer
        $endTime = microtime(true);
        $latencyMs = number_format(($endTime - $startTime) * 1000, 3);

        echo "\n--- Transaction Request Successful ---\n";
        echo "Order ID: $orderId\n";
        echo "**Network Latency: $latencyMs ms**\n";
        echo "Response: \n";
        print_r($response);
        echo "\n--------------------------------------\n";

    } catch (Exception $e) {
        $endTime = microtime(true);
        $latencyMs = number_format(($endTime - $startTime) * 1000, 3);

        echo "\n--- Transaction Request Failed ---\n";
        echo "Order ID: $orderId\n";
        echo "**Network Latency: $latencyMs ms**\n";
        echo "Error Message: " . $e->getMessage() . "\n";
        echo "----------------------------------\n";
    }
}

// Execute
pay();
// get('Your Order ID');
// cancel('Your Order ID');