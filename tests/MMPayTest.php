<?php

use PHPUnit\Framework\TestCase;
use MMPay\MMPay;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;

class MMPayTest extends TestCase
{
    private $options;

    protected function setUp(): void
    {
        $this->options = [
            'appId'          => 'test_app_id',
            'publishableKey' => 'test_pub_key',
            'secretKey'      => 'test_secret_key',
            'apiBaseUrl'     => 'https://api.mmpay.com'
        ];
    }

    /**
     * Helper to inject a Mock Client into the private property of the SDK
     */
    private function injectMockClient(MMPay $sdk, array $responses, &$container = [])
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        
        // Add history middleware to inspect sent requests
        $history = Middleware::history($container);
        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack]);

        // Use Reflection to access the private $client property
        $reflection = new ReflectionClass($sdk);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($sdk, $client);
    }

    public function testSandboxPayFlow()
    {
        $sdk = new MMPay($this->options);
        $container = []; // To store request history

        // We expect 2 requests:
        // 1. Handshake (returns token)
        // 2. Create Payment (returns order data)
        $this->injectMockClient($sdk, [
            new Response(200, [], json_encode(['token' => 'mock_btoken'])), // Handshake Response
            new Response(200, [], json_encode(['url' => 'https://pay.com', 'status' => 'PENDING'])) // Pay Response
        ], $container);

        $params = [
            'orderId' => 'ORD-123',
            'amount'  => 1000,
            'items'   => [['name' => 'Item 1', 'amount' => 1000, 'quantity' => 1]]
        ];

        $result = $sdk->sandboxPay($params);

        // Assertions
        $this->assertEquals('PENDING', $result['status']);
        $this->assertEquals('https://pay.com', $result['url']);

        // Verify that the correct headers were sent in the second request (the payment create)
        $paymentRequest = $container[1]['request'];
        $this->assertEquals('mock_btoken', $paymentRequest->getHeaderLine('X-Mmpay-Btoken'));
        $this->assertEquals('Bearer test_pub_key', $paymentRequest->getHeaderLine('Authorization'));
    }

    public function testVerifyCallbackSignature()
    {
        $sdk = new MMPay($this->options);

        // Mock Data
        $payload = '{"status":"SUCCESS","amount":1000}';
        $nonce = '123456789';
        
        // Manually calculate expected signature using the secret key
        $expectedSignature = hash_hmac('sha256', "$nonce.$payload", $this->options['secretKey']);

        // Test True
        $this->assertTrue($sdk->verifyCb($payload, $nonce, $expectedSignature));

        // Test False (Tampered Payload)
        $this->assertFalse($sdk->verifyCb('{"status":"FAILED"}', $nonce, $expectedSignature));
    }
}