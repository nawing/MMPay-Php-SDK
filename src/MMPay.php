<?php

namespace MMPay;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class MMPay
{
    private $appId;
    private $publishableKey;
    private $secretKey;
    private $apiBaseUrl;
    private $client;
    private $btoken;

    /**
     * @param array $options ['appId' => string, 'publishableKey' => string, 'secretKey' => string, 'apiBaseUrl' => string]
     */
    public function __construct(array $options)
    {
        $this->appId = $options['appId'];
        $this->publishableKey = $options['publishableKey'];
        $this->secretKey = $options['secretKey'];
        $this->apiBaseUrl = rtrim($options['apiBaseUrl'], '/');
        
        $this->client = new Client([
            'base_uri' => $this->apiBaseUrl,
            'timeout'  => 30.0,
        ]);
    }

    /**
     * Generates an HMAC SHA256 signature.
     */
    private function generateSignature(string $bodyString, string $nonce): string
    {
        $stringToSign = "{$nonce}.{$bodyString}";
        return hash_hmac('sha256', $stringToSign, $this->secretKey);
    }

    /**
     * Mimics JS JSON.stringify exactly for signature consistency.
     */
    private function jsonStringify($data): string
    {
        // JSON_UNESCAPED_SLASHES is critical for URLs to match JS behavior
        // JSON_UNESCAPED_UNICODE is recommended for consistency
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Helper to get current timestamp in milliseconds
     */
    private function getNonce(): string
    {
        return (string) round(microtime(true) * 1000);
    }

    // --- Sandbox Methods ---

    /**
     * @throws \Exception
     */
    public function sandboxHandShake(array $payload)
    {
        $endpoint = '/payments/sandbox-handshake';
        $nonce = $this->getNonce();
        
        $bodyString = $this->jsonStringify($payload);
        $signature = $this->generateSignature($bodyString, $nonce);

        try {
            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Authorization'     => 'Bearer ' . $this->publishableKey,
                    'X-Mmpay-Nonce'     => $nonce,
                    'X-Mmpay-Signature' => $signature,
                    'Content-Type'      => 'application/json',
                ],
                'body' => $bodyString
            ]);

            $data = json_decode($response->getBody(), true);
            if (isset($data['token'])) {
                $this->btoken = $data['token'];
            }
            return $data;

        } catch (GuzzleException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    public function sandboxPay(array $params)
    {
        $endpoint = '/payments/sandbox-create';
        $nonce = $this->getNonce();

        // Construct payload structure strictly
        $xpayload = [
            'appId'   => $this->appId,
            'nonce'   => $nonce,
            'amount'  => $params['amount'],
            'orderId' => $params['orderId'],
            'items'   => $params['items'],
        ];
        
        if (isset($params['callbackUrl'])) {
            $xpayload['callbackUrl'] = $params['callbackUrl'];
        }
        if (isset($params['currency'])) {
            $xpayload['currency'] = $params['currency'];
        }

        $bodyString = $this->jsonStringify($xpayload);
        $signature = $this->generateSignature($bodyString, $nonce);

        // Perform handshake first
        $this->sandboxHandShake([
            'orderId' => $xpayload['orderId'], 
            'nonce'   => $xpayload['nonce']
        ]);

        try {
            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Authorization'     => 'Bearer ' . $this->publishableKey,
                    'X-Mmpay-Btoken'    => $this->btoken,
                    'X-Mmpay-Nonce'     => $nonce,
                    'X-Mmpay-Signature' => $signature,
                    'Content-Type'      => 'application/json',
                ],
                'body' => $bodyString
            ]);

            return json_decode($response->getBody(), true);

        } catch (GuzzleException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    // --- Production Methods ---

    /**
     * @throws \Exception
     */
    public function handShake(array $payload)
    {
        $endpoint = '/payments/handshake';
        $nonce = $this->getNonce();
        
        $bodyString = $this->jsonStringify($payload);
        $signature = $this->generateSignature($bodyString, $nonce);

        try {
            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Authorization'     => 'Bearer ' . $this->publishableKey,
                    'X-Mmpay-Nonce'     => $nonce,
                    'X-Mmpay-Signature' => $signature,
                    'Content-Type'      => 'application/json',
                ],
                'body' => $bodyString
            ]);

            $data = json_decode($response->getBody(), true);
            if (isset($data['token'])) {
                $this->btoken = $data['token'];
            }
            return $data;

        } catch (GuzzleException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    public function pay(array $params)
    {
        $endpoint = '/payments/create';
        $nonce = $this->getNonce();

        $xpayload = [
            'appId'   => $this->appId,
            'nonce'   => $nonce,
            'amount'  => $params['amount'],
            'orderId' => $params['orderId'],
            'items'   => $params['items'],
        ];

        if (isset($params['callbackUrl'])) {
            $xpayload['callbackUrl'] = $params['callbackUrl'];
        }
        if (isset($params['currency'])) {
            $xpayload['currency'] = $params['currency'];
        }

        $bodyString = $this->jsonStringify($xpayload);
        $signature = $this->generateSignature($bodyString, $nonce);

        // Perform handshake
        $this->handShake([
            'orderId' => $xpayload['orderId'], 
            'nonce'   => $xpayload['nonce']
        ]);

        try {
            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Authorization'     => 'Bearer ' . $this->publishableKey,
                    'X-Mmpay-Btoken'    => $this->btoken,
                    'X-Mmpay-Nonce'     => $nonce,
                    'X-Mmpay-Signature' => $signature,
                    'Content-Type'      => 'application/json',
                ],
                'body' => $bodyString
            ]);

            return json_decode($response->getBody(), true);

        } catch (GuzzleException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    // --- Verification ---

    /**
     * Verify Callback Signature
     * @param string $payload Raw request body
     * @param string $nonce Header X-Mmpay-Nonce
     * @param string $expectedSignature Header X-Mmpay-Signature
     * @return bool
     */
    public function verifyCb(string $payload, string $nonce, string $expectedSignature): bool
    {
        if (empty($payload) || empty($nonce) || empty($expectedSignature)) {
            return false;
        }

        $stringToSign = "{$nonce}.{$payload}";
        $generatedSignature = hash_hmac('sha256', $stringToSign, $this->secretKey);

        return hash_equals($generatedSignature, $expectedSignature);
    }
}